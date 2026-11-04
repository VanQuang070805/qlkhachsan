<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RoomType;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Hiển thị form đánh giá phòng.
     */
    public function create(Request $request)
    {
        $bookingId = $request->query('booking_id');
        $roomTypeId = $request->query('room_type_id');

        if (!$bookingId || !$roomTypeId) {
            return redirect()->route('home')->with('error', 'Thông tin đánh giá không hợp lệ.');
        }

        $booking = Booking::with('rooms.roomType')->findOrFail($bookingId);
        
        // Kiểm tra bảo mật: Phải là khách hàng sở hữu booking này
        $userId = session('customer_user_id') ?: auth()->id() ?: session('user_id');
        if ((int) $booking->user_id !== (int) $userId) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền đánh giá phòng của booking này.');
        }

        // Booking phải ở trạng thái completed mới được đánh giá
        if ($booking->status !== 'completed') {
            return redirect()->route('home')->with('error', 'Chỉ có thể đánh giá sau khi đã hoàn tất thủ tục trả phòng.');
        }

        // Kiểm tra loại phòng có thuộc booking hay không
        $roomType = RoomType::findOrFail($roomTypeId);
        $hasRoomType = $booking->rooms->contains(fn($room) => $room->room_type_id == $roomTypeId);

        if (!$hasRoomType) {
            return redirect()->route('home')->with('error', 'Bạn không sử dụng loại phòng này trong booking.');
        }

        // Mỗi kỳ lưu trú chỉ có một đánh giá cho mỗi hạng phòng.
        $existing = Review::where('user_id', $userId)
            ->where('booking_id', $booking->id)
            ->where('room_type_id', $roomTypeId)
            ->first();
        
        if ($existing) {
            return redirect()->route('rooms.detail', $roomTypeId)->with('info', 'Bạn đã gửi đánh giá cho loại phòng này trước đó.');
        }

        return view('review.create', compact('booking', 'roomType'));
    }

    /**
     * Lưu đánh giá mới.
     */
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'room_type_id' => 'required|integer|exists:room_types,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $userId = session('customer_user_id') ?: auth()->id() ?: session('user_id');

        try {
            $created = DB::transaction(function () use ($request, $userId): bool {
                $booking = Booking::query()->lockForUpdate()->findOrFail($request->booking_id);

                if ((int) $booking->user_id !== (int) $userId) {
                    abort(403, 'Bạn không có quyền đánh giá phòng của booking này.');
                }
                if ($booking->status !== 'completed') {
                    return false;
                }
                if (! $booking->rooms()->where('room_type_id', $request->room_type_id)->exists()) {
                    abort(422, 'Loại phòng này không thuộc kỳ nghỉ của bạn.');
                }

                Review::create([
                    'user_id' => $userId,
                    'booking_id' => $booking->id,
                    'room_type_id' => $request->room_type_id,
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                ]);

                return true;
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return redirect()->route('rooms.detail', $request->room_type_id)
                    ->with('info', 'Bạn đã gửi đánh giá cho hạng phòng này trong kỳ lưu trú này.');
            }

            throw $exception;
        }

        if (! $created) {
            return redirect()->route('home')->with('error', 'Chỉ có thể đánh giá sau khi đã hoàn tất thủ tục trả phòng.');
        }

        return redirect()->route('rooms.detail', $request->room_type_id)
            ->with('success', 'Cảm ơn quý khách đã gửi đánh giá! Ý kiến của quý khách sẽ giúp chúng tôi hoàn thiện dịch vụ tốt hơn.');
    }
}
