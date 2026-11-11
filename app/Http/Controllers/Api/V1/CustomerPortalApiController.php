<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\PriceSetting;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ExpirePendingBookingHolds;
use App\Services\SessionRevocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CustomerPortalApiController extends ApiController
{
    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'regex:/^0[0-9]{9,10}$/'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Thông tin đăng ký chưa hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $otp = $this->newOtp();
        $user = User::create([
            'username' => $data['email'],
            'fullname' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'verified' => false,
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        if (! $this->sendOtpEmail($user, $otp, 'Xác thực tài khoản Rosaliza Hotel')) {
            return $this->fail('EMAIL_UNAVAILABLE', 'Chưa thể gửi email xác thực. Vui lòng thử gửi lại OTP.', 503);
        }

        return $this->ok([
            'id' => (int) $user->id,
            'fullname' => (string) $user->fullname,
            'email' => (string) $user->email,
            'phone' => (string) $user->phone,
            'verified' => (bool) $user->verified,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Vui lòng nhập email và mật khẩu.', 422, $validator->errors()->toArray());
        }

        $identity = trim((string) $request->input('email'));
        $user = User::query()
            ->where('role', 'customer')
            ->where(function ($q) use ($identity) {
                $q->where('email', $identity)->orWhere('username', $identity);
            })
            ->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return $this->fail('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không chính xác.', 401);
        }

        if (! $user->verified) {
            return $this->fail('EMAIL_UNVERIFIED', 'Tài khoản chưa xác thực email.', 403);
        }

        $request->session()->regenerate();
        Auth::guard('customer')->login($user);
        Auth::guard('web')->setUser($user);

        $customerPayload = [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => 'customer',
            'verified' => (bool) $user->verified,
        ];

        $request->session()->put([
            'customer_user_id' => $user->id,
            'customer_user' => $customerPayload,
            'user_id' => $user->id,
            'auth_user_id' => $user->id,
            'user' => $customerPayload,
        ]);

        return $this->ok($customerPayload);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->forget([
            'customer_user_id',
            'customer_user',
            'user_id',
            'auth_user_id',
            'user',
        ]);

        return response()->noContent()->withHeaders([
            'X-Correlation-ID' => $this->correlationId(),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Mã OTP hoặc email không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $user = User::where('email', $request->input('email'))->where('role', 'customer')->first();
        if (! $user || ! $user->otp_code || ! $user->otp_expires_at || now()->greaterThan($user->otp_expires_at) || ! Hash::check((string) $request->input('otp'), $user->otp_code)) {
            return $this->fail('INVALID_OTP', 'Mã OTP không chính xác hoặc đã hết hạn.', 422);
        }

        $updated = User::query()
            ->whereKey($user->id)
            ->where('otp_code', $user->otp_code)
            ->where('otp_expires_at', '>', now())
            ->where('verified', false)
            ->update(['verified' => true, 'otp_code' => null, 'otp_expires_at' => null]);
        if ($updated !== 1) {
            return $this->fail('INVALID_OTP', 'Mã OTP không chính xác hoặc đã hết hạn.', 422);
        }

        return $this->ok(['verified' => true]);
    }

    public function resendOtp(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Email không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $user = User::query()->where('role', 'customer')->where('email', $request->input('email'))
            ->where('verified', false)->whereNotNull('otp_code')->first();
        if ($user) {
            $this->issueOtp($user, 'Xác thực tài khoản Rosaliza Hotel');
        }

        return $this->ok(['accepted' => true]);
    }

    public function forgotPassword(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Email không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $user = User::query()->where('role', 'customer')->where('email', $request->input('email'))
            ->where('verified', true)->first();
        if ($user) {
            $this->issueOtp($user, 'Đặt lại mật khẩu Rosaliza Hotel');
        }

        return $this->ok(['accepted' => true]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:190'],
            'otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Thông tin đặt lại mật khẩu chưa hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $reset = DB::transaction(function () use ($data): bool {
            $user = User::query()->where('role', 'customer')->where('email', $data['email'])
                ->where('verified', true)->lockForUpdate()->first();
            if (! $user || ! $user->otp_code || ! $user->otp_expires_at
                || now()->greaterThanOrEqualTo($user->otp_expires_at)
                || ! Hash::check($data['otp'], $user->otp_code)) {
                return false;
            }

            $user->update([
                'password' => Hash::make($data['password']),
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);
            app(SessionRevocationService::class)->revoke($user);

            return true;
        });

        if (! $reset) {
            return $this->fail('INVALID_OTP', 'Mã OTP không chính xác hoặc đã hết hạn.', 422);
        }

        Auth::guard('customer')->logout();
        $request->session()->forget(['customer_user_id', 'customer_user', 'user_id', 'auth_user_id', 'user']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return $this->ok(['reset' => true]);
    }

    private function newOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function issueOtp(User $user, string $subject): void
    {
        $previousCode = $user->otp_code;
        $previousExpiry = $user->otp_expires_at;
        $otp = $this->newOtp();
        $newHash = Hash::make($otp);
        $user->update(['otp_code' => $newHash, 'otp_expires_at' => now()->addMinutes(10)]);

        if (! $this->sendOtpEmail($user, $otp, $subject)) {
            User::query()->whereKey($user->id)->where('otp_code', $newHash)
                ->update(['otp_code' => $previousCode, 'otp_expires_at' => $previousExpiry]);
        }
    }

    private function sendOtpEmail(User $user, string $otp, string $subject): bool
    {
        try {
            Mail::raw(
                "Mã OTP của bạn là: {$otp}\n\nMã có hiệu lực trong 10 phút.\nNếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.",
                fn ($message) => $message->to($user->email)->subject($subject)
            );

            return true;
        } catch (\Throwable $exception) {
            Log::error('OTP email delivery failed.', ['user_id' => $user->id, 'exception' => get_class($exception)]);

            return false;
        }
    }

    public function me(Request $request)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        return $this->ok([
            'id' => (int) $customer->id,
            'fullname' => (string) $customer->fullname,
            'email' => (string) $customer->email,
            'phone' => (string) ($customer->phone ?? ''),
            'role' => 'customer',
            'verified' => (bool) $customer->verified,
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function updateMe(Request $request)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => ['sometimes', 'string', 'min:2', 'max:100'],
            'phone' => ['sometimes', 'regex:/^0[0-9]{9,10}$/'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu cập nhật không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $customer->update($validator->validated());

        return $this->ok([
            'id' => (int) $customer->id,
            'fullname' => (string) $customer->fullname,
            'email' => (string) $customer->email,
            'phone' => (string) ($customer->phone ?? ''),
            'role' => 'customer',
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function indexBookings(Request $request)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $paginator = Booking::query()
            ->with('rooms.roomType:id,type_name,price')
            ->where('user_id', $customer->id)
            ->latest('id')
            ->paginate($perPage);

        return $this->paginated($paginator, fn (Booking $b) => $this->formatBooking($b), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function storeBooking(Request $request)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $validator = Validator::make($request->all(), [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:20'],
            'child_count' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'room_count' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['sometimes', 'in:cash,vietqr,momo,zalopay,vnpay'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Thông tin đặt phòng chưa hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $roomCount = (int) ($data['room_count'] ?? 1);
        $adults = (int) $data['adult_count'];
        $children = (int) ($data['child_count'] ?? 0);
        $roomType = RoomType::findOrFail((int) $data['room_type_id']);

        if ((int) ceil(($adults + $children) / $roomCount) > (int) $roomType->max_guests) {
            return $this->fail('CAPACITY_EXCEEDED', 'Số lượng khách vượt quá sức chứa của hạng phòng.', 422);
        }

        $createdBooking = DB::transaction(function () use ($customer, $roomType, $data, $roomCount, $adults, $children) {
            $reservedIds = Booking::reservedRoomIds($data['check_in'], $data['check_out']);

            $availableRooms = Room::query()
                ->where('room_type_id', $roomType->id)
                ->where('status', Room::STATUS_AVAILABLE)
                ->whereNotIn('id', $reservedIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($roomCount)
                ->get();

            if ($availableRooms->count() < $roomCount) {
                return null;
            }

            $perRoomPrice = (int) round((float) PriceSetting::calculateTotalPrice((float) $roomType->price, $data['check_in'], $data['check_out']));
            $totalPrice = $perRoomPrice * $roomCount;
            $depositAmount = (int) round($totalPrice / 2);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'customer_name' => $customer->fullname,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adult_count' => $adults,
                'child_count' => $children,
                'total_price' => $totalPrice,
                'deposit_amount' => $depositAmount,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => 'pending',
                'status' => ($data['payment_method'] ?? null) === 'cash' ? 'confirmed' : 'pending',
            ]);

            $booking->rooms()->attach($availableRooms->pluck('id')->all());

            return $booking->load('rooms.roomType');
        });

        if (! $createdBooking) {
            return $this->fail('BOOKING_ROOM_UNAVAILABLE', 'Phòng vừa được khách khác đặt hoặc không đủ số lượng phòng trống.', 409);
        }

        return $this->ok($this->formatBooking($createdBooking), 201, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function showBooking(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::with('rooms.roomType:id,type_name,price')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }

        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền truy cập đơn đặt phòng của khách khác.', 403);
        }

        return $this->ok($this->formatBooking($record), 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function cancelBooking(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền hủy đơn đặt phòng của khách khác.', 403);
        }

        if (! in_array($record->status, ['pending', 'confirmed'], true)) {
            return $this->fail('BOOKING_CANNOT_BE_CANCELLED', 'Đơn đặt phòng ở trạng thái hiện tại không thể hủy.', 409);
        }

        $record->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => trim((string) $request->input('reason', '')) ?: null,
        ]);

        return $this->ok($this->formatBooking($record->fresh('rooms.roomType')), 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function extendBooking(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::with('rooms.roomType')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền gia hạn đơn của khách khác.', 403);
        }

        $validator = Validator::make($request->all(), [
            'new_check_out' => ['required', 'date_format:Y-m-d'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Ngày trả phòng mới không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $newCheckOut = (string) $request->input('new_check_out');
        if ($newCheckOut <= $record->check_out->toDateString()) {
            return $this->fail('INVALID_EXTENSION_DATE', 'Ngày gia hạn phải sau ngày trả phòng hiện tại.', 422);
        }

        $conflicts = Booking::reservedRoomIds($record->check_out->toDateString(), $newCheckOut, $record->id)
            ->intersect($record->rooms->pluck('id'));
        if ($conflicts->isNotEmpty()) {
            return $this->fail('EXTENSION_ROOM_CONFLICT', 'Phòng đã có lịch đặt kế tiếp trong khoảng thời gian gia hạn.', 409);
        }

        $record->update(['check_out' => $newCheckOut]);

        return $this->ok($this->formatBooking($record->fresh('rooms.roomType')), 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function listPayments(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền xem lịch sử thanh toán của khách khác.', 403);
        }

        $logs = PaymentLog::where('booking_id', $record->id)->latest('id')->get()->map(fn (PaymentLog $log) => [
            'id' => (int) $log->id,
            'gateway' => (string) $log->gateway,
            'purpose' => (string) ($log->purpose ?? 'deposit'),
            'amount_vnd' => (int) round((float) $log->amount),
            'status' => (string) $log->status,
            'reference_code' => (string) ($log->reference_code ?? ''),
            'created_at' => $log->created_at?->toIso8601String(),
        ])->values()->all();

        return $this->ok($logs, 200, ['Cache-Control' => 'private, no-store']);
    }

    public function initiatePayment(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền thanh toán đơn của khách khác.', 403);
        }

        app(ExpirePendingBookingHolds::class)->expireIfDue($record);
        $record->refresh();

        if (! in_array($record->status, ['pending', 'confirmed'], true) || ($record->status === 'pending' && $record->paymentHoldExpired())) {
            return $this->fail('BOOKING_HOLD_EXPIRED', 'Đặt phòng đã hết thời gian giữ chỗ hoặc đã bị hủy.', 409);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', 'in:cash,vietqr,momo,zalopay,vnpay'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Phương thức thanh toán không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $method = (string) $request->input('payment_method');
        $record->update([
            'payment_method' => $method,
            'status' => $method === 'cash' ? 'confirmed' : $record->status,
        ]);

        return $this->ok([
            'booking_id' => (int) $record->id,
            'payment_method' => $method,
            'status' => (string) $record->status,
            'payment_status' => (string) $record->payment_status,
            'deposit_amount_vnd' => (int) round((float) $record->deposit_amount),
            'payment_url' => route('payment.show', $record->id),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function storeReview(Request $request, int $booking)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Booking::with('rooms')->find($booking);
        if (! $record) {
            return $this->fail('BOOKING_NOT_FOUND', 'Không tìm thấy đơn đặt phòng.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_BOOKING_ACCESS', 'Bạn không có quyền đánh giá đơn của khách khác.', 403);
        }
        if ($record->status !== 'completed') {
            return $this->fail('BOOKING_NOT_COMPLETED', 'Chỉ có thể đánh giá sau khi hoàn tất lưu trú.', 409);
        }

        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Nội dung đánh giá không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $roomTypeId = (int) ($record->rooms->first()?->room_type_id ?? 0);
        $review = Review::updateOrCreate([
            'booking_id' => $record->id,
            'user_id' => $customer->id,
        ], [
            'room_type_id' => $roomTypeId,
            'rating' => (int) $request->input('rating'),
            'comment' => trim((string) $request->input('comment')),
        ]);

        return $this->ok([
            'id' => (int) $review->id,
            'booking_id' => (int) $review->booking_id,
            'rating' => (int) $review->rating,
            'comment' => (string) $review->comment,
        ], 201);
    }

    public function updateReview(Request $request, int $review)
    {
        $customer = $this->requireCustomer($request);
        if (! $customer) {
            return $this->fail('UNAUTHENTICATED', 'Vui lòng đăng nhập tài khoản khách hàng.', 401);
        }

        $record = Review::find($review);
        if (! $record) {
            return $this->fail('REVIEW_NOT_FOUND', 'Không tìm thấy đánh giá.', 404);
        }
        if ((int) $record->user_id !== (int) $customer->id) {
            return $this->fail('FORBIDDEN_REVIEW_ACCESS', 'Bạn không có quyền chỉnh sửa đánh giá của khách khác.', 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Nội dung đánh giá không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $record->update($validator->validated());

        return $this->ok([
            'id' => (int) $record->id,
            'booking_id' => (int) $record->booking_id,
            'rating' => (int) $record->rating,
            'comment' => (string) $record->comment,
        ]);
    }

    private function requireCustomer(Request $request): ?User
    {
        $id = (int) ($request->session()->get('customer_user_id') ?: Auth::guard('customer')->id() ?: 0);
        if ($id <= 0) {
            return null;
        }

        $user = User::find($id);

        return ($user && $user->role === 'customer') ? $user : null;
    }

    private function formatBooking(Booking $booking): array
    {
        return [
            'id' => (int) $booking->id,
            'user_id' => (int) $booking->user_id,
            'customer_name' => (string) $booking->customer_name,
            'customer_email' => (string) $booking->customer_email,
            'customer_phone' => (string) $booking->customer_phone,
            'check_in' => $booking->check_in?->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'adult_count' => (int) $booking->adult_count,
            'child_count' => (int) $booking->child_count,
            'status' => (string) $booking->status,
            'payment_status' => (string) $booking->payment_status,
            'payment_method' => $booking->payment_method,
            'total_price_vnd' => (int) round((float) $booking->total_price),
            'deposit_amount_vnd' => (int) round((float) $booking->deposit_amount),
            'rooms' => $booking->relationLoaded('rooms') ? $booking->rooms->map(fn (Room $r) => [
                'id' => (int) $r->id,
                'room_number' => (string) $r->room_number,
                'room_type' => (string) ($r->roomType?->type_name ?? ''),
            ])->values()->all() : [],
        ];
    }
}
