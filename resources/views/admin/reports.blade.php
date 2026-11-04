@extends($canViewReportBookings ? 'layouts.admin' : 'layouts.dashboard')
@section('title', 'Dashboard · Rosaliza Hotel')
@section('page-title', 'Dashboard')

@push('styles')
<style>
/* DashStack Theme Foundations */
:root {
  --ds-bg: #F5F6FA;
  --ds-white: #FFFFFF;
  --ds-blue: #4880FF;
  --ds-blue-light: rgba(72, 128, 255, 0.14);
  --ds-dark: #202224;
  --ds-gray: #606060;
  --ds-border: #D5D5D5;
  --ds-table-head: #F1F4F9;
  --ds-green: #00B69B;
  --ds-red: #F93C65;
  --ds-reset-red: #EA0234;
  --ds-font: 'Nunito Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body.internal-shell {
  background: var(--ds-bg) !important;
  font-family: var(--ds-font) !important;
  color: var(--ds-dark);
}

.internal-main {
  background: var(--ds-bg) !important;
  padding: 30px !important;
  font-family: var(--ds-font) !important;
}

.dashstack-container {
  display: flex;
  flex-direction: column;
  gap: 26px;
  max-width: 1440px;
  margin: 0 auto;
}

.ds-dashboard-title {
  font-family: var(--ds-font);
  font-size: 32px;
  font-weight: 700;
  color: var(--ds-dark);
  margin: 0 0 4px 0;
  line-height: 1.2;
}

/* 4 DashStack Flagship KPI Cards */
.ds-kpi-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 24px;
}

.ds-kpi-card {
  background: var(--ds-white);
  border-radius: 14px;
  padding: 22px 24px;
  box-shadow: 6px 6px 54px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid transparent;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  min-height: 155px;
}

.ds-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 6px 12px 48px rgba(0, 0, 0, 0.08);
}

.ds-kpi-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.ds-kpi-label {
  font-size: 15px;
  font-weight: 600;
  color: var(--ds-gray);
  margin: 0;
}

.ds-kpi-icon {
  width: 56px;
  height: 56px;
  border-radius: 20px;
  display: grid;
  place-items: center;
  flex: none;
}

.ds-kpi-icon img {
  width: 56px;
  height: 56px;
  display: block;
}

.ds-kpi-icon-purple { background: rgba(130, 128, 255, 0.12); color: #8280FF; }
.ds-kpi-icon-amber  { background: rgba(254, 197, 61, 0.12);  color: #FEC53D; }
.ds-kpi-icon-mint   { background: rgba(74, 217, 145, 0.12);  color: #4AD991; }
.ds-kpi-icon-coral  { background: rgba(255, 144, 102, 0.12); color: #FF9066; }

.ds-kpi-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--ds-dark);
  margin: 8px 0 0 0;
  line-height: 1.2;
}

.ds-kpi-foot {
  margin-top: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.ds-trend-up {
  color: var(--ds-green);
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.ds-trend-down {
  color: var(--ds-red);
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.ds-trend-desc {
  color: var(--ds-gray);
  font-weight: 600;
}

/* =========================================================================
   FIGMA FILTER BAR (EXACT CSS FROM FIGMA SNIPPET)
   ========================================================================= */
.ds-filter-bar {
  box-sizing: border-box;
  background: #F9F9FB;
  border: 0.6px solid #D5D5D5;
  border-radius: 10px;
  padding: 10px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  font-family: 'Nunito Sans', sans-serif;
}

.ds-filter-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.ds-filter-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #000000;
  flex: none;
}

.ds-filter-divider {
  width: 1px;
  height: 22px;
  background: #979797;
  opacity: 0.69;
  flex: none;
}

.ds-filter-label {
  font-family: 'Nunito Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  line-height: 19px;
  color: #202224;
  white-space: nowrap;
}

.ds-filter-item {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-family: 'Nunito Sans', sans-serif;
  font-size: 14px;
  color: #202224;
}

.ds-filter-item select {
  appearance: none;
  background: transparent;
  border: none;
  font-family: 'Nunito Sans', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #202224;
  padding-right: 22px;
  cursor: pointer;
  outline: none;
}

.ds-filter-item .ds-chevron {
  position: absolute;
  right: 0;
  pointer-events: none;
  font-size: 12px;
  color: #000000;
}

.ds-filter-date-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: transparent;
  font-family: 'Nunito Sans', sans-serif;
  font-size: 14px;
  font-weight: 700;
  color: #202224;
  padding: 0;
  cursor: pointer;
}

.ds-filter-date-item {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.ds-date-display {
  font-family: 'Nunito Sans', sans-serif;
  font-weight: 700;
  font-size: 14px;
  line-height: 19px;
  color: #202224;
  cursor: pointer;
  padding-right: 20px;
  white-space: nowrap;
}

.ds-date-popup {
  display: none;
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  background: #FFFFFF;
  border: 1px solid #D5D5D5;
  border-radius: 8px;
  padding: 10px 14px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 50;
  align-items: center;
  gap: 8px;
}

.ds-filter-date-item:hover .ds-date-popup,
.ds-filter-date-item:focus-within .ds-date-popup {
  display: flex;
}

.ds-date-popup input[type="date"] {
  border: 1px solid #D5D5D5;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 13px;
  font-family: 'Nunito Sans', sans-serif;
  color: #202224;
  outline: none;
}

.ds-filter-reset {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'Nunito Sans', sans-serif;
  font-size: 14px;
  font-weight: 700;
  line-height: 19px;
  color: #EA0234;
  text-decoration: none;
  cursor: pointer;
  white-space: nowrap;
  background: transparent;
  border: none;
  padding: 0;
  transition: opacity 0.2s;
}

.ds-filter-reset:hover {
  color: #EA0234;
  opacity: 0.8;
}

.ds-filter-reset svg path {
  fill: #EA0234;
}

/* DashStack Card (Sales Details & Deals Details) */
.ds-card {
  background: var(--ds-white);
  border-radius: 14px;
  box-shadow: 6px 6px 54px rgba(0, 0, 0, 0.05);
  border: 1px solid transparent;
  padding: 26px 30px;
}

.ds-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 22px;
}

.ds-card-title {
  font-family: var(--ds-font);
  font-size: 22px;
  font-weight: 700;
  color: var(--ds-dark);
  margin: 0;
}

.ds-card-select-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.ds-card-select {
  appearance: none;
  background: #FFFFFF;
  border: 0.6px solid #D5D5D5;
  border-radius: 6px;
  padding: 6px 28px 6px 14px;
  font-family: var(--ds-font);
  font-size: 13px;
  font-weight: 600;
  color: var(--ds-gray);
  cursor: pointer;
  outline: none;
}

.ds-card-select-wrap .bi-chevron-down {
  position: absolute;
  right: 10px;
  font-size: 11px;
  color: var(--ds-gray);
  pointer-events: none;
}

/* Sales Details Chart */
.ds-chart-container {
  position: relative;
  height: 360px;
  width: 100%;
}

.ds-analytics-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}

.ds-analytics-grid .ds-chart-container { height: 300px; }

/* Deals Details Table */
.ds-deals-table-wrap {
  width: 100%;
  overflow-x: auto;
}

.ds-deals-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin: 0;
}

.ds-deals-table thead th {
  background: var(--ds-table-head);
  color: var(--ds-dark);
  font-family: var(--ds-font);
  font-size: 14px;
  font-weight: 700;
  padding: 16px 20px;
  border: none;
  white-space: nowrap;
}

.ds-deals-table thead th:first-child {
  border-radius: 8px 0 0 8px;
}

.ds-deals-table thead th:last-child {
  border-radius: 0 8px 8px 0;
}

.ds-deals-table tbody tr {
  transition: background 0.15s ease;
}

.ds-deals-table tbody tr:hover {
  background: #FAFAFC;
}

.ds-deals-table tbody td {
  padding: 18px 20px;
  vertical-align: middle;
  border-bottom: 1px solid #F1F4F9;
  font-family: var(--ds-font);
  font-size: 14px;
  color: var(--ds-dark);
}

.ds-deals-table tbody tr:last-child td {
  border-bottom: none;
}

.ds-product-cell {
  display: flex;
  align-items: center;
  gap: 14px;
}

.ds-product-thumb {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #F1F4F9;
  display: grid;
  place-items: center;
  color: #4880FF;
  font-size: 20px;
  overflow: hidden;
  flex: none;
}

.ds-product-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ds-product-info {
  display: flex;
  flex-direction: column;
}

.ds-product-name {
  font-weight: 700;
  color: var(--ds-dark);
}

.ds-product-sub {
  font-size: 12px;
  color: var(--ds-gray);
}

.ds-loc-cell {
  display: flex;
  flex-direction: column;
}

.ds-loc-name {
  font-weight: 600;
  color: var(--ds-dark);
}

.ds-loc-sub {
  font-size: 12px;
  color: var(--ds-gray);
}

.ds-date-cell {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ds-dark);
}

.ds-piece-cell {
  font-weight: 600;
  color: var(--ds-dark);
}

.ds-amount-cell {
  font-weight: 700;
  color: var(--ds-dark);
}

/* Status Badges */
.ds-status-pill {
  display: inline-block;
  padding: 6px 18px;
  border-radius: 999px;
  font-family: var(--ds-font);
  font-size: 12px;
  font-weight: 700;
  text-align: center;
  color: #FFFFFF;
  white-space: nowrap;
}

.ds-status-delivered { background: #00B69B; }
.ds-status-pending   { background: #FCBE2D; }
.ds-status-cancelled { background: #FD5454; }
.ds-status-confirmed { background: #4880FF; }
.ds-status-inhouse   { background: #8280FF; }

.ds-filter-bar { align-items: end; background: #fff; padding: 20px; gap: 16px; }
.ds-filter-fields { display: grid; grid-template-columns: repeat(4, minmax(135px, 1fr)); gap: 14px; flex: 1 1 700px; min-width: 0; }
.ds-filter-field { display: grid; gap: 7px; min-width: 0; color: #495466; font-size: 13px; font-weight: 700; }
.ds-filter-field :is(input, select) { width: 100%; min-width: 0; min-height: 42px; padding: 8px 10px; background: #fff; color: #202224; border: 1px solid #d5d9e2; border-radius: 8px; }
.ds-filter-field :is(input, select):focus-visible, .ds-filter-actions :is(button, a):focus-visible { outline: 3px solid #4880ff; outline-offset: 2px; }
.ds-filter-actions { display: flex; align-items: center; gap: 12px; }
.ds-filter-apply { min-height: 42px; padding: 0 18px; border: 0; border-radius: 8px; background: #3569de; color: #fff; font-weight: 700; cursor: pointer; }
.ds-filter-error { flex-basis: 100%; margin: 0; color: #b42318; font-size: 13px; }
.ds-data-note { margin: -12px 0 0; color: #5e6877; font-size: 13px; line-height: 1.5; }
.ds-filter-reset { min-height: 42px; }
@media (max-width: 1000px) { .ds-filter-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .ds-filter-fields { grid-template-columns: minmax(0, 1fr); } .ds-filter-actions { width: 100%; } }

@media (max-width: 1200px) {
  .ds-kpi-row { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .ds-kpi-row { grid-template-columns: 1fr; }
  .ds-card { padding: 18px 16px; }
  .ds-chart-container { height: 260px; }
  .ds-analytics-grid { grid-template-columns: minmax(0, 1fr); }
  .ds-analytics-grid .ds-chart-container { height: 260px; }
}
</style>
@endpush

@section('content')
<div class="dashstack-container">
  <h1 class="ds-dashboard-title">Dashboard</h1>

  <!-- 4 DashStack KPI Metric Cards (Real Database Data) -->
  <div class="ds-kpi-row">
    <!-- Total User -->
    <div class="ds-kpi-card">
      <div class="ds-kpi-head">
        <div>
          <p class="ds-kpi-label">Tổng khách hàng</p>
          <div class="ds-kpi-value">{{ number_format($stats['total_customers'] ?? 0) }}</div>
        </div>
        <div class="ds-kpi-icon ds-kpi-icon-purple">
          <img src="{{ asset('images/dashstack/icon-users.svg') }}" alt="" aria-hidden="true" width="56" height="56">
        </div>
      </div>
      <div class="ds-kpi-foot"><span class="ds-trend-desc">Tất cả tài khoản khách hàng</span></div>
    </div>

    <!-- Total Order -->
    <div class="ds-kpi-card">
      <div class="ds-kpi-head">
        <div>
          <p class="ds-kpi-label">Tổng đơn đặt phòng</p>
          <div class="ds-kpi-value">{{ number_format($stats['total_bookings'] ?? 0) }}</div>
        </div>
        <div class="ds-kpi-icon ds-kpi-icon-amber">
          <img src="{{ asset('images/dashstack/icon-order.svg') }}" alt="" aria-hidden="true" width="56" height="56">
        </div>
      </div>
      @include('admin.partials.report-comparison', ['metric' => 'total_bookings'])
    </div>

    <!-- Total Sales -->
    <div class="ds-kpi-card">
      <div class="ds-kpi-head">
        <div>
          <p class="ds-kpi-label">Doanh thu đã thu</p>
          <div class="ds-kpi-value">{{ number_format($stats['total_revenue'] ?? 0, 0, ',', '.') }} đ</div>
        </div>
        <div class="ds-kpi-icon ds-kpi-icon-mint">
          <img src="{{ asset('images/dashstack/icon-sales.svg') }}" alt="" aria-hidden="true" width="56" height="56">
        </div>
      </div>
      @include('admin.partials.report-comparison', ['metric' => 'total_revenue', 'currency' => true])
    </div>

    <!-- Total Pending -->
    <div class="ds-kpi-card">
      <div class="ds-kpi-head">
        <div>
          <p class="ds-kpi-label">Đơn đang chờ</p>
          <div class="ds-kpi-value">{{ number_format($stats['pending_count'] ?? 0) }}</div>
        </div>
        <div class="ds-kpi-icon ds-kpi-icon-coral">
          <img src="{{ asset('images/dashstack/icon-pending.svg') }}" alt="" aria-hidden="true" width="56" height="56">
        </div>
      </div>
      <div class="ds-kpi-foot"><span class="ds-trend-desc">Trạng thái hiện tại của các đơn phù hợp bộ lọc</span></div>
    </div>
  </div>

  <!-- DashStack Figma Filter Bar -->
<form class="ds-filter-bar" method="GET" action="{{ route($canViewReportBookings ? 'admin.reports' : 'staff.reports') }}" id="dsFilterForm">
  <div class="ds-filter-fields">
    <label class="ds-filter-field"><span>Nhận phòng từ ngày</span><input type="date" name="start_date" value="{{ old('start_date', $filters['start_date'] ?? '') }}"></label>
    <label class="ds-filter-field"><span>Nhận phòng đến ngày</span><input type="date" name="end_date" value="{{ old('end_date', $filters['end_date'] ?? '') }}"></label>
    <label class="ds-filter-field"><span>Hạng phòng</span><select name="room_type_id"><option value="">Tất cả hạng phòng</option>@foreach($roomTypes as $rt)<option value="{{ $rt->id }}" @selected(($filters['room_type_id'] ?? '') == $rt->id)>{{ $rt->type_name }}</option>@endforeach</select></label>
    <label class="ds-filter-field"><span>Trạng thái đơn</span><select name="status"><option value="">Tất cả trạng thái</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ xác nhận</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Đã xác nhận</option><option value="checked_in" @selected(($filters['status'] ?? '') === 'checked_in')>Đang lưu trú</option><option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Hoàn thành</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Đã hủy</option></select></label>
  </div>
  <div class="ds-filter-actions"><button type="submit" class="ds-filter-apply">Áp dụng</button><a href="{{ route($canViewReportBookings ? 'admin.reports' : 'staff.reports') }}" class="ds-filter-reset">Đặt lại</a></div>
  @if($errors->any())<p class="ds-filter-error" role="alert">{{ $errors->first() }}</p>@endif
</form>
  <p class="ds-data-note">Bộ lọc theo ngày nhận phòng. Tiền đã thu dựa trên giao dịch thành công hoặc tiền cọc của đơn đã thanh toán, không tính đơn đã hủy và chưa trừ các khoản hoàn tiền. Khách hàng là tổng số tài khoản, không theo bộ lọc.</p>

  <!-- Sales Details Line Chart Card -->
  <div class="ds-card">
    <div class="ds-card-head">
      <h2 class="ds-card-title">Thực thu theo ngày nhận phòng</h2>
      <div class="ds-card-select-wrap">
        <select class="ds-card-select" id="salesRangeSelect" aria-label="Khoảng ngày hiển thị trên biểu đồ">
          <option value="30" data-chart-range="30">30 ngày cuối trong phạm vi</option>
          <option value="7" data-chart-range="7">7 ngày cuối trong phạm vi</option>
          <option value="all" data-chart-range="all">Tối đa 365 ngày</option>
        </select>
        <i class="bi bi-chevron-down"></i>
      </div>
    </div>
    <div class="ds-chart-container">
      <canvas id="trendChart" role="img" aria-label="Biểu đồ thực thu theo ngày nhận phòng"></canvas>
    </div>
  </div>

  <section class="ds-analytics-grid" aria-label="Các biểu đồ phân tích từ dữ liệu đặt phòng">
    <article class="ds-card">
      <div class="ds-card-head"><h2 class="ds-card-title">Lượt đặt theo hạng phòng</h2></div>
      <div class="ds-chart-container"><canvas id="roomTypeChart" role="img" aria-label="Biểu đồ lượt đặt theo hạng phòng"></canvas></div>
    </article>
    <article class="ds-card">
      <div class="ds-card-head"><h2 class="ds-card-title">Cơ cấu trạng thái đặt phòng</h2></div>
      <div class="ds-chart-container"><canvas id="statusChart" role="img" aria-label="Biểu đồ cơ cấu trạng thái đặt phòng"></canvas></div>
    </article>
    <article class="ds-card">
      <div class="ds-card-head"><h2 class="ds-card-title">Doanh thu và lượt đặt theo tháng</h2></div>
      <div class="ds-chart-container"><canvas id="monthlyTrendChart" role="img" aria-label="Biểu đồ doanh thu và lượt đặt theo tháng"></canvas></div>
    </article>
    <article class="ds-card">
      <div class="ds-card-head"><h2 class="ds-card-title">Trạng thái đặt phòng theo tháng</h2></div>
      <div class="ds-chart-container"><canvas id="statusTrendChart" role="img" aria-label="Biểu đồ trạng thái đặt phòng theo tháng"></canvas></div>
    </article>
    <article class="ds-card">
      <div class="ds-card-head"><h2 class="ds-card-title">Lượt đặt theo ngày trong tuần</h2></div>
      <div class="ds-chart-container"><canvas id="weekdayChart" role="img" aria-label="Biểu đồ lượt đặt theo ngày trong tuần"></canvas></div>
    </article>
  </section>

  <!-- Deals Details Table Card -->
  @if($canViewReportBookings)
  <div class="ds-card">
    <div class="ds-card-head">
      <h2 class="ds-card-title">Đặt phòng gần đây</h2>
    </div>
    <div class="ds-deals-table-wrap">
      <table class="ds-deals-table">
        <thead>
          <tr>
            <th>Hạng phòng</th>
            <th>Khách hàng</th>
            <th>Nhận phòng</th>
            <th>Số đêm</th>
            <th>Giá đặt phòng</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookings as $b)
          @php
            $nights = max(1, (int) round((strtotime($b->check_out) - strtotime($b->check_in)) / 86400));
            $pillClass = match($b->status) {
              'completed' => 'ds-status-delivered',
              'pending' => 'ds-status-pending',
              'cancelled' => 'ds-status-cancelled',
              'confirmed' => 'ds-status-confirmed',
              default => 'ds-status-inhouse'
            };
            $statusName = config('statuses.booking.'.$b->status, $b->status);
          @endphp
          <tr>
            <td>
              <div class="ds-product-cell">
                <div class="ds-product-thumb">
                  <i class="bi bi-door-closed"></i>
                </div>
                <div class="ds-product-info">
                  <span class="ds-product-name">{{ $b->type_name ?? 'Phòng Khách Sạn' }}</span>
                  <span class="ds-product-sub">#{{ $b->id }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="ds-loc-cell">
                <span class="ds-loc-name">{{ $b->customer_name }}</span>
                <span class="ds-loc-sub">{{ $b->customer_phone }}</span>
              </div>
            </td>
            <td>
              <span class="ds-date-cell">{{ date('d.m.Y', strtotime($b->check_in)) }}</span>
            </td>
            <td>
              <span class="ds-piece-cell">{{ $nights }} đêm</span>
            </td>
            <td>
              <span class="ds-amount-cell">{{ number_format($b->total_price, 0, ',', '.') }} đ</span>
            </td>
            <td>
              <span class="ds-status-pill {{ $pillClass }}">{{ $statusName }}</span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" style="text-align: center; padding: 36px; color: #979797;">
              Chưa có dữ liệu giao dịch trong khoảng thời gian này.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    {{ $bookings->links('pagination.royal') }}
  </div>
  @else
    <p class="ds-data-note">Báo cáo tổng hợp không hiển thị thông tin định danh khách hàng.</p>
  @endif
</div>
@endsection

@push('scripts')
<script type="module">
document.addEventListener('DOMContentLoaded', () => {
  const chartData = @json($chartData);
  const canvas = document.getElementById('trendChart');
  if (!canvas || !window.Chart) return;

  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 320);
  gradient.addColorStop(0, 'rgba(72, 128, 255, 0.28)');
  gradient.addColorStop(1, 'rgba(72, 128, 255, 0.00)');

  let activeRange = 30;

  const getFilteredRows = () => {
    if (activeRange === 'all') return chartData;
    return chartData.slice(-activeRange);
  };

  const initialRows = getFilteredRows();

  const salesChart = new Chart(canvas, {
    type: 'line',
    data: {
      labels: initialRows.map(r => r.date),
      datasets: [{
        data: initialRows.map(r => Number(r.revenue || 0)),
        borderColor: '#4880FF',
        borderWidth: 2.8,
        backgroundColor: gradient,
        fill: true,
        tension: 0.38,
        pointRadius: 3.5,
        pointBackgroundColor: '#4880FF',
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointHoverRadius: 6.5,
        pointHoverBackgroundColor: '#4880FF',
        pointHoverBorderColor: '#FFFFFF',
        pointHoverBorderWidth: 2.5,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#4880FF',
          titleFont: { family: 'Nunito Sans', size: 12, weight: '700' },
          bodyFont: { family: 'Nunito Sans', size: 13, weight: '700' },
          titleColor: '#FFFFFF',
          bodyColor: '#FFFFFF',
          padding: 10,
          cornerRadius: 6,
          displayColors: false,
          callbacks: {
            title: items => `Ngày: ${items[0].label}`,
            label: item => new Intl.NumberFormat('vi-VN').format(Math.round(item.parsed.y)) + ' đ'
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: '#979797',
            font: { family: 'Nunito Sans', size: 12 },
            maxRotation: 0,
            maxTicksLimit: 12,
            callback(value) { return String(this.getLabelForValue(value)).slice(5); }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: '#F1F4F9',
            drawBorder: false
          },
          ticks: {
            color: '#979797',
            font: { family: 'Nunito Sans', size: 12 },
            callback: value => new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value)
          }
        }
      }
    }
  });

  const rangeSelect = document.getElementById('salesRangeSelect');
  if (rangeSelect) {
    rangeSelect.addEventListener('change', (e) => {
      activeRange = e.target.value === 'all' ? 'all' : Number(e.target.value);
      const rows = getFilteredRows();
      salesChart.data.labels = rows.map(r => r.date);
      salesChart.data.datasets[0].data = rows.map(r => Number(r.revenue || 0));
      salesChart.update();
    });
  }

  const typeRows = @json($typeChartData);
  const statusRows = @json($statusChartData);
  const monthlyRows = @json($monthlyTrendData);
  const statusTrendRows = @json($statusTrendData);
  const weekdayRows = @json($weekdayChartData);
  const palette = ['#4880FF', '#00B69B', '#FEC53D', '#8280FF', '#FD5454', '#FF9066', '#5C6BC0'];
  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
  };

  const roomTypeCanvas = document.getElementById('roomTypeChart');
  if (roomTypeCanvas) new Chart(roomTypeCanvas, {
    type: 'bar',
    data: { labels: typeRows.map(row => row.type_name), datasets: [{ label: 'Lượt đặt', data: typeRows.map(row => Number(row.bookings || 0)), backgroundColor: typeRows.map((_, index) => palette[index % palette.length]), borderRadius: 7 }] },
    options: baseOptions
  });

  const statusCanvas = document.getElementById('statusChart');
  if (statusCanvas) new Chart(statusCanvas, {
    type: 'doughnut',
    data: { labels: statusRows.map(row => row[0]), datasets: [{ data: statusRows.map(row => Number(row[1] || 0)), backgroundColor: palette }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '64%', plugins: baseOptions.plugins }
  });

  const monthlyCanvas = document.getElementById('monthlyTrendChart');
  if (monthlyCanvas) new Chart(monthlyCanvas, {
    type: 'line',
    data: { labels: monthlyRows.map(row => row.period), datasets: [
      { label: 'Doanh thu', data: monthlyRows.map(row => Number(row.revenue || 0)), borderColor: palette[0], backgroundColor: 'rgba(72,128,255,.12)', fill: true, tension: .3, yAxisID: 'revenue' },
      { label: 'Lượt đặt', data: monthlyRows.map(row => Number(row.bookings || 0)), borderColor: palette[1], backgroundColor: palette[1], tension: .3, yAxisID: 'bookings' }
    ] },
    options: { responsive: true, maintainAspectRatio: false, plugins: baseOptions.plugins, scales: {
      x: { grid: { display: false } },
      revenue: { beginAtZero: true, position: 'left', ticks: { callback: value => new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) } },
      bookings: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
    } }
  });

  const statusTrendCanvas = document.getElementById('statusTrendChart');
  if (statusTrendCanvas) {
    const states = ['confirmed', 'checked_in', 'completed', 'cancelled'];
    const stateLabels = ['Đã xác nhận', 'Đang lưu trú', 'Hoàn thành', 'Đã hủy'];
    new Chart(statusTrendCanvas, {
      type: 'bar',
      data: { labels: statusTrendRows.map(row => row.period), datasets: states.map((state, index) => ({
        label: stateLabels[index], data: statusTrendRows.map(row => Number(row[state] || 0)), backgroundColor: palette[index], stack: 'booking-status', borderRadius: 4
      })) },
      options: { ...baseOptions, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }

  const weekdayCanvas = document.getElementById('weekdayChart');
  if (weekdayCanvas) new Chart(weekdayCanvas, {
    type: 'bar',
    data: { labels: weekdayRows.map(row => row.label), datasets: [{ label: 'Lượt đặt', data: weekdayRows.map(row => Number(row.bookings || 0)), backgroundColor: weekdayRows.map((_, index) => palette[index % palette.length]), borderRadius: 7 }] },
    options: baseOptions
  });
});
</script>
@endpush
