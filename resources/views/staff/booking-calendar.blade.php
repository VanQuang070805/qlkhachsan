@extends('layouts.dashboard')

@section('title', 'Lịch đặt phòng · Rosaliza Hotel')

@section('content')
@php
    $routePrefix = request()->is('admin*') ? 'admin' : 'staff';
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đang lưu trú',
        'completed' => 'Đã trả phòng',
        'cancelled' => 'Đã hủy',
    ];
@endphp

<main class="booking-calendar-page">
    <header class="module-head booking-calendar-head">
        <div>
            <h1>Lịch đặt phòng</h1>
            <p>Tìm theo khách, mã booking hoặc số phòng và xử lý khách đến trong ngày.</p>
        </div>
        <a class="schedule-room-map-link" href="{{ route($routePrefix.'.bookings') }}">
            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
            Sơ đồ phòng
        </a>
    </header>

    <form class="booking-calendar-filter" method="GET" action="{{ route($routePrefix.'.booking-calendar') }}" role="search">
        <label class="schedule-search-field" for="schedule-search">
            <span>Tìm kiếm</span>
            <span class="schedule-input-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="schedule-search" name="q" type="search" value="{{ $search }}" placeholder="Tên, SĐT, email, mã booking, số phòng">
            </span>
        </label>
        <label for="schedule-from">
            <span>Từ ngày</span>
            <input id="schedule-from" name="from" type="date" value="{{ $from }}">
        </label>
        <label for="schedule-to">
            <span>Đến ngày</span>
            <input id="schedule-to" name="to" type="date" value="{{ $to }}">
        </label>
        <label for="schedule-status">
            <span>Trạng thái</span>
            <select id="schedule-status" name="status">
                <option value="">Tất cả trạng thái</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="schedule-filter-actions">
            <button type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Lọc</span></button>
            <a href="{{ route($routePrefix.'.booking-calendar') }}" aria-label="Xóa bộ lọc" title="Xóa bộ lọc"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        </div>
    </form>

    @if($errors->any())
        <div class="schedule-alert" role="alert">Khoảng ngày hoặc nội dung tìm kiếm chưa hợp lệ.</div>
    @endif

    <section class="booking-schedule-panel" aria-labelledby="booking-schedule-results">
        <header>
            <div>
                <h2 id="booking-schedule-results">Danh sách lưu trú</h2>
                <span>{{ $bookings->total() }} booking trong khoảng {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
            </div>
            <span class="schedule-today"><i class="bi bi-calendar-check" aria-hidden="true"></i>{{ now('Asia/Ho_Chi_Minh')->format('d/m/Y') }}</span>
        </header>

        @if($bookings->isEmpty())
            <div class="schedule-empty">
                <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                <strong>Không tìm thấy lịch đặt phòng</strong>
                <span>Thử đổi từ khóa, trạng thái hoặc khoảng ngày.</span>
            </div>
        @else
            <div class="schedule-table-wrap">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Khách hàng</th>
                            <th>Phòng</th>
                            <th>Kỳ nghỉ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($bookings as $booking)
                        <tr class="schedule-clickable-row" data-booking-url="{{ route($routePrefix.'.booking-calendar.show', $booking) }}" tabindex="0" aria-label="Mở booking #{{ $booking->id }} của {{ $booking->customer_name }}">
                            <td><a class="schedule-booking-link" href="{{ route($routePrefix.'.booking-calendar.show', $booking) }}">#{{ $booking->id }}</a><small>{{ ucfirst($booking->payment_method ?: '—') }} · {{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</small></td>
                            <td><strong>{{ $booking->customer_name }}</strong><small>{{ $booking->customer_phone ?: $booking->customer_email ?: 'Chưa có liên hệ' }}</small></td>
                            <td>
                                <div class="schedule-room-list">
                                    @forelse($booking->rooms as $room)
                                        <span class="schedule-room {{ $room->status === \App\Models\Room::STATUS_AVAILABLE ? 'is-ready' : '' }}">{{ $room->room_number }}</span>
                                    @empty
                                        <span>—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td><strong>{{ $booking->check_in?->format('d/m/Y') }}</strong><small>đến {{ $booking->check_out?->format('d/m/Y') }}</small></td>
                            <td><span class="schedule-status is-{{ $booking->status }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</span></td>
                            <td>@include('staff.partials.booking-checkin-action', ['booking' => $booking, 'canCheckIn' => $canCheckIn, 'routePrefix' => $routePrefix])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="schedule-mobile-list">
                @foreach($bookings as $booking)
                    <article class="schedule-clickable-row" data-booking-url="{{ route($routePrefix.'.booking-calendar.show', $booking) }}" tabindex="0" aria-label="Mở booking #{{ $booking->id }} của {{ $booking->customer_name }}">
                        <header>
                            <div><a class="schedule-booking-link" href="{{ route($routePrefix.'.booking-calendar.show', $booking) }}">#{{ $booking->id }} · {{ $booking->customer_name }}</a><span>{{ $booking->customer_phone ?: $booking->customer_email ?: 'Chưa có liên hệ' }}</span></div>
                            <span class="schedule-status is-{{ $booking->status }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>
                        </header>
                        <dl>
                            <div><dt>Phòng</dt><dd>{{ $booking->rooms->pluck('room_number')->join(', ') ?: '—' }}</dd></div>
                            <div><dt>Nhận</dt><dd>{{ $booking->check_in?->format('d/m/Y') }}</dd></div>
                            <div><dt>Trả</dt><dd>{{ $booking->check_out?->format('d/m/Y') }}</dd></div>
                            <div><dt>Thanh toán</dt><dd>{{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</dd></div>
                        </dl>
                        <footer>@include('staff.partials.booking-checkin-action', ['booking' => $booking, 'canCheckIn' => $canCheckIn, 'routePrefix' => $routePrefix])</footer>
                    </article>
                @endforeach
            </div>

            <footer class="schedule-pagination">
                {{ $bookings->links('pagination.royal') }}
            </footer>
        @endif
    </section>
</main>
@endsection
