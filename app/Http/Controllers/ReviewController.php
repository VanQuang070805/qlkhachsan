<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RoomType;
use App\Models\Review;
use Illuminate\Http\Request;

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
        $userId = session('user_id');
        if ($booking->user_id !== $userId) {
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

        // Đảm bảo người dùng chưa đánh giá loại phòng này
        $existing = Review::where('user_id', $userId)
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

        $booking = Booking::findOrFail($request->booking_id);
        $userId = session('user_id');

        // Kiểm tra bảo mật
        if ($booking->user_id !== $userId) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền đánh giá phòng của booking này.');
        }

        if ($booking->status !== 'completed') {
            return redirect()->route('home')->with('error', 'Chỉ có thể đánh giá sau khi đã hoàn tất thủ tục trả phòng.');
        }

        // Kiểm tra trùng lặp
        $existing = Review::where('user_id', $userId)
            ->where('room_type_id', $request->room_type_id)
            ->first();

        if ($existing) {
            return redirect()->route('rooms.detail', $request->room_type_id)->with('info', 'Bạn đã gửi đánh giá cho loại phòng này trước đó.');
        }

        Review::create([
            'user_id' => $userId,
            'room_type_id' => $request->room_type_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->route('rooms.detail', $request->room_type_id)
            ->with('success', 'Cảm ơn quý khách đã gửi đánh giá! Ý kiến của quý khách sẽ giúp chúng tôi hoàn thiện dịch vụ tốt hơn.');
    }
}
