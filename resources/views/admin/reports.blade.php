@extends($canViewReportBookings ? 'layouts.admin' : 'layouts.dashboard')
@section('title','Tổng quan · Rosaliza Hotel')
@section('page-title','Tổng quan')
@section('content')
<form class="report-filter-bar" method="GET" action="{{ route('admin.reports') }}">
  <div class="report-filter-inputs">
    <label class="report-filter-field">
      <span>Từ ngày</span>
      <input type="date" name="start_date" class="report-input-pill" value="{{ $filters['start_date'] ?? '' }}">
    </label>
    <label class="report-filter-field">
      <span>Đến ngày</span>
      <input type="date" name="end_date" class="report-input-pill" value="{{ $filters['end_date'] ?? '' }}">
    </label>
    <label class="report-filter-field">
      <span>Hạng phòng</span>
      <select name="room_type_id" class="report-select-pill">
        <option value="">Tất cả hạng phòng</option>
        @foreach($roomTypes as $rt)
          <option value="{{ $rt->id }}" @selected(($filters['room_type_id'] ?? '') == $rt->id)>{{ $rt->type_name }}</option>
        @endforeach
      </select>
    </label>
    <label class="report-filter-field">
      <span>Trạng thái</span>
      <select name="status" class="report-select-pill">
        <option value="">Mọi trạng thái</option>
        <option value="pending" @selected(($filters['status'] ?? '')==='pending')>Chờ xác nhận</option>
        <option value="confirmed" @selected(($filters['status'] ?? '')==='confirmed')>Đã xác nhận</option>
        <option value="checked_in" @selected(($filters['status'] ?? '')==='checked_in')>Đang lưu trú</option>
        <option value="completed" @selected(($filters['status'] ?? '')==='completed')>Hoàn thành</option>
        <option value="cancelled" @selected(($filters['status'] ?? '')==='cancelled')>Đã hủy</option>
      </select>
    </label>
  </div>
  <div class="report-filter-actions">
    <button type="submit" class="btn-report-apply">
      <i class="bi bi-funnel"></i>
      <span>Áp dụng</span>
    </button>
    <a href="{{ route('admin.reports') }}" class="btn-report-reset" aria-label="Xóa bộ lọc" title="Xóa bộ lọc">
      <i class="bi bi-arrow-counterclockwise"></i>
    </a>
  </div>
</form>

<div class="admin-reports-shell">
  <section class="metrics-strip">
    <button type="button" class="kpi-card is-clickable is-active" data-chart-metric="revenue" aria-pressed="true">
      <div class="kpi-card-label">
        <span>Doanh thu</span>
        <i class="bi bi-graph-up text-primary"></i>
      </div>
      <div class="kpi-card-value" data-kpi="revenue">{{ number_format($stats['total_revenue'] ?? 0,0,',','.') }} đ</div>
      <div class="kpi-card-subtext">Nhấn để phân tích xu hướng</div>
    </button>

    <button type="button" class="kpi-card is-clickable" data-chart-metric="bookings" aria-pressed="false">
      <div class="kpi-card-label">
        <span>Lượt đặt phòng</span>
        <i class="bi bi-calendar-check text-success"></i>
      </div>
      <div class="kpi-card-value" data-kpi="bookings">{{ number_format($stats['total_bookings'] ?? 0) }}</div>
      <div class="kpi-card-subtext"><span data-kpi="nights">{{ number_format($stats['total_nights'] ?? 0) }}</span> đêm lưu trú</div>
    </button>

    <article class="kpi-card">
      <div class="kpi-card-label">
        <span>ADR (Bình quân)</span>
        <i class="bi bi-tag text-info"></i>
      </div>
      <div class="kpi-card-value" data-kpi="adr">{{ number_format($stats['adr'] ?? 0,0,',','.') }} đ</div>
      <div class="kpi-card-subtext">Giá bình quân / đêm</div>
    </article>

    <article class="kpi-card">
      <div class="kpi-card-label">
        <span>RevPAR</span>
        <i class="bi bi-buildings text-warning"></i>
      </div>
      <div class="kpi-card-value" data-kpi="revpar">{{ number_format($stats['revpar'] ?? 0,0,',','.') }} đ</div>
      <div class="kpi-card-subtext">Doanh thu / phòng có sẵn</div>
    </article>

    <article class="kpi-card">
      <div class="kpi-card-label">
        <span>Tỉ lệ hoàn tất</span>
        <i class="bi bi-pie-chart text-secondary"></i>
      </div>
      <div class="kpi-card-value" data-kpi="completion">{{ number_format((($stats['completed_count'] ?? 0) / max(1, $stats['total_bookings'] ?? 0)) * 100,1,',','.') }}%</div>
      <div class="kpi-card-subtext">{{ $stats['completed_count'] ?? 0 }} / {{ $stats['total_bookings'] ?? 0 }} đơn thành công</div>
    </article>
  </section>

  <section class="charts-grid" aria-label="Biểu đồ phân tích">
    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title" id="trend-chart-title">Doanh thu theo ngày</h2>
        <div class="chart-range-switcher" aria-label="Khoảng thời gian">
          <button type="button" class="chart-range-btn" data-chart-range="7" aria-pressed="false">7 ngày</button>
          <button type="button" class="chart-range-btn is-active" data-chart-range="30" aria-pressed="true">30 ngày</button>
          <button type="button" class="chart-range-btn" data-chart-range="all" aria-pressed="false">Tất cả</button>
        </div>
      </header>
      <div class="chart-wrap">
        <canvas id="trendChart"></canvas>
      </div>
    </article>

    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title">Theo hạng phòng</h2>
        <span class="panel-note">{{ $canViewReportBookings ? 'Bấm cột để lọc' : 'Thống kê tổng hợp' }}</span>
      </header>
      <div class="chart-wrap chart-wrap--compact">
        <canvas id="roomTypeChart"></canvas>
      </div>
    </article>

    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title">Trạng thái đặt</h2>
        <span class="panel-note">Hiện tại · bấm để lọc</span>
      </header>
      <div class="chart-wrap chart-wrap--compact">
        <canvas id="statusChart"></canvas>
      </div>
    </article>

    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title">Doanh thu và lượt đặt theo tháng</h2>
        <span class="panel-note">So sánh hai chỉ số</span>
      </header>
      <div class="chart-wrap">
        <canvas id="monthlyTrendChart"></canvas>
      </div>
    </article>

    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title">Trạng thái đặt phòng theo tháng</h2>
        <span class="panel-note">Cột chồng · bấm để lọc</span>
      </header>
      <div class="chart-wrap chart-wrap--compact">
        <canvas id="statusTrendChart"></canvas>
      </div>
    </article>

    <article class="chart-panel-card">
      <header class="chart-panel-header">
        <h2 class="chart-panel-title">Nhu cầu đặt theo ngày trong tuần</h2>
        <span class="panel-note">Lượt nhận phòng</span>
      </header>
      <div class="chart-wrap">
        <canvas id="weekdayChart"></canvas>
      </div>
    </article>
  </section>

  @if($canViewReportBookings)
  <section class="recent-bookings-card bookings-panel">
    <header class="recent-bookings-header">
      <div class="d-flex align-items-center gap-2">
        <h2 class="chart-panel-title">Đặt phòng gần đây</h2>
        <span class="badge bg-light text-dark rounded-pill border" data-visible-bookings>{{ count($bookings) }} bản ghi</span>
      </div>
    </header>
    <div class="table-responsive">
      <table class="table recent-bookings-table align-middle">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Khách hàng</th>
            <th>Hạng phòng</th>
            <th>Lưu trú</th>
            <th>Giá trị</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookings as $b)
            <tr data-report-booking="{{ $b->id }}">
              <td><strong class="font-monospace">#{{ $b->id }}</strong></td>
              <td>
                <div class="fw-semibold text-dark">{{ $b->customer_name }}</div>
                <small class="text-muted">{{ $b->customer_phone }}</small>
              </td>
              <td><span class="badge bg-light text-dark border">{{ $b->type_name ?? 'Chưa xếp' }}</span></td>
              <td>
                <div class="text-dark">{{ date('d/m/Y', strtotime($b->check_in)) }}</div>
                <small class="text-muted">đến {{ date('d/m/Y', strtotime($b->check_out)) }}</small>
              </td>
              <td><strong class="text-dark font-monospace">{{ number_format($b->total_price, 0, ',', '.') }} đ</strong></td>
              <td>
                <span class="status-pill status-{{ $b->status }}">
                  {{ ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','checked_in'=>'Đang lưu trú','cancelled'=>'Đã hủy','completed'=>'Hoàn thành'][$b->status] ?? ucfirst($b->status) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="empty-state">Không có dữ liệu phù hợp.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  @else
  <p class="text-muted small">Báo cáo tổng hợp không hiển thị thông tin định danh khách hàng.</p>
  @endif
</div>
@endsection

@push('scripts')
<script type="module">
document.addEventListener('DOMContentLoaded', () => {
const trendRows = @json($chartData);
const typeRows = @json($typeChartData);
const statusRows = @json($statusChartData);
const statusTrendRows = @json($statusTrendData);
const monthlyTrendRows = @json($monthlyTrendData);
const weekdayRows = @json($weekdayChartData);
const bookingRows = @json($bookings);
const canFilterBookingRows = @json($canViewReportBookings);
const roomInventory = @json($roomInventory);
const colors = ['#6ea8df','#55b89a','#e6b85c','#ad91e8','#e78383'];
const money = value => new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Math.round(Number(value) || 0)) + ' đ';
const chartMuted = () => getComputedStyle(document.body).getPropertyValue('--muted').trim() || '#697780';
const chartLine = () => getComputedStyle(document.body).getPropertyValue('--line').trim() || '#e4eaee';
const statusValues = ['pending','confirmed','checked_in','completed','cancelled'];
const statusLabels = ['Chờ xác nhận','Đã xác nhận','Đang lưu trú','Hoàn thành','Đã hủy'];
const trendStatusValues = ['confirmed','checked_in','completed','cancelled'];
const trendStatusLabels = ['Đã xác nhận','Đang lưu trú','Hoàn thành','Đã hủy'];
const statusColor = Object.fromEntries(statusValues.map((status,index) => [status, colors[index]]));
let selectedType = Number(@json($filters['room_type_id'] ?? 0)) || null;
let selectedStatus = @json($filters['status'] ?? null);
const typeIds = row => String(row.room_type_ids || '').split(',').filter(Boolean).map(Number);
const visibleRows = () => bookingRows.filter(row => (!selectedType || typeIds(row).includes(selectedType)) && (!selectedStatus || row.status === selectedStatus));
const groupTrend = rows => Object.values(rows.reduce((grouped, row) => {
  const date = String(row.check_in).slice(0,10);
  grouped[date] ||= { date, revenue:0, bookings:0 };
  grouped[date].bookings++;
  if (row.payment_status === 'paid' && row.status !== 'cancelled') grouped[date].revenue += Number(row.total_price || 0);
  return grouped;
}, {})).sort((a,b) => a.date.localeCompare(b.date));
const trendCanvas = document.getElementById('trendChart');
if (trendCanvas && window.Chart) {
  let metric = 'revenue';
  let range = 30;
  const trendChart = new Chart(trendCanvas, {
    type: 'line',
    data: { labels: [], datasets: [{ data: [], borderColor: colors[0], backgroundColor: 'rgba(110,168,223,.14)', borderWidth: 2.5, fill: true, tension: .34, pointRadius: 2, pointHoverRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false }, tooltip: { displayColors: false, callbacks: { label: item => metric === 'revenue' ? money(item.parsed.y) : `${item.parsed.y} lượt` } } }, scales: { x: { grid: { display: false }, ticks: { color: chartMuted(), maxRotation: 0, maxTicksLimit: 10 } }, y: { beginAtZero: true, grid: { color: chartLine() }, ticks: { color: chartMuted(), callback: value => metric === 'revenue' ? new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) : value } } } }
  });
  const updateTrend = () => {
    const filteredTrend = canFilterBookingRows ? groupTrend(visibleRows()) : trendRows;
    const rows = range === 'all' ? filteredTrend : filteredTrend.slice(-range);
    trendChart.data.labels = rows.map(row => row.date.slice(5));
    trendChart.data.datasets[0].data = rows.map(row => Number(row[metric] || 0));
    trendChart.data.datasets[0].borderColor = metric === 'revenue' ? colors[0] : colors[1];
    trendChart.data.datasets[0].backgroundColor = metric === 'revenue' ? 'rgba(110,168,223,.14)' : 'rgba(85,184,154,.14)';
    document.getElementById('trend-chart-title').textContent = metric === 'revenue' ? 'Doanh thu theo ngày' : 'Lượt đặt theo ngày';
    trendChart.update();
  };
  document.querySelectorAll('[data-chart-metric]').forEach(button => button.addEventListener('click', () => {
    metric = button.dataset.chartMetric;
    document.querySelectorAll('[data-chart-metric]').forEach(item => { const active = item === button; item.classList.toggle('is-active', active); item.setAttribute('aria-pressed', String(active)); });
    updateTrend();
  }));
  document.querySelectorAll('[data-chart-range]').forEach(button => button.addEventListener('click', () => {
    range = button.dataset.chartRange === 'all' ? 'all' : Number(button.dataset.chartRange);
    document.querySelectorAll('[data-chart-range]').forEach(item => { const active = item === button; item.classList.toggle('is-active', active); item.setAttribute('aria-pressed', String(active)); });
    updateTrend();
  }));
  updateTrend();
}

const typeCanvas = document.getElementById('roomTypeChart');
if (typeCanvas && window.Chart) new Chart(typeCanvas, {
  type: 'bar',
  data: { labels: typeRows.map(row => row.type_name), datasets: [{ data: typeRows.map(row => Number(row.bookings)), backgroundColor: colors, borderRadius: 7, maxBarThickness: 34 }] },
  options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: item => `${item.parsed.x} lượt đặt` } } }, scales: { x: { beginAtZero: true, ticks: { precision: 0, color: chartMuted() }, grid: { color: chartLine() } }, y: { ticks: { color: chartMuted() }, grid: { display: false } } }, onClick: (_event, hits) => { if (!canFilterBookingRows || !hits.length) return; const id = Number(typeRows[hits[0].index].id); selectedType = selectedType === id ? null : id; applyCrossFilter(); } }
});

const statusCanvas = document.getElementById('statusChart');
if (statusCanvas && window.Chart) new Chart(statusCanvas, {
  type: 'doughnut',
  data: { labels: statusRows.map(row => row[0]), datasets: [{ data: statusRows.map(row => Number(row[1])), backgroundColor: colors, borderWidth: 0, hoverOffset: 7 }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { color: chartMuted(), usePointStyle: true, boxWidth: 7, padding: 13, font: { size: 11 } } }, tooltip: { callbacks: { label: item => `${item.label}: ${item.parsed}` } } }, onClick: (_event, hits) => { if (!canFilterBookingRows || !hits.length) return; const value = statusValues[hits[0].index]; selectedStatus = selectedStatus === value ? null : value; applyCrossFilter(); } }
});

const statusTrendCanvas = document.getElementById('statusTrendChart');
if (statusTrendCanvas && window.Chart) new Chart(statusTrendCanvas, {
  type: 'bar',
  data: {
    labels: statusTrendRows.map(row => row.period),
    datasets: trendStatusValues.map((status, index) => ({
      label: trendStatusLabels[index],
      data: statusTrendRows.map(row => Number(row[status] || 0)),
      backgroundColor: statusColor[status],
      borderRadius: 4,
      borderSkipped: false,
      maxBarThickness: 44,
      stack: 'booking-status',
    }))
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'bottom', labels: { color: chartMuted(), usePointStyle: true, boxWidth: 8, padding: 14, font: { size: 11 } } },
      tooltip: { callbacks: { label: item => `${item.dataset.label}: ${item.parsed.y} lượt` } },
    },
    scales: {
      x: { stacked: true, grid: { display: false }, ticks: { color: chartMuted(), maxRotation: 0, maxTicksLimit: 10 } },
      y: { stacked: true, beginAtZero: true, grid: { color: chartLine() }, ticks: { precision: 0, color: chartMuted() } },
    },
    onClick: (_event, hits) => {
      if (!canFilterBookingRows || !hits.length) return;
      const value = trendStatusValues[hits[0].datasetIndex];
      selectedStatus = selectedStatus === value ? null : value;
      applyCrossFilter();
    },
  }
});

const monthlyTrendCanvas = document.getElementById('monthlyTrendChart');
if (monthlyTrendCanvas && window.Chart) new Chart(monthlyTrendCanvas, {
  type: 'line',
  data: {
    labels: monthlyTrendRows.map(row => row.period),
    datasets: [
      { label: 'Doanh thu', data: monthlyTrendRows.map(row => Number(row.revenue || 0)), yAxisID: 'yRevenue', borderColor: colors[0], backgroundColor: 'rgba(110,168,223,.12)', borderWidth: 2.5, fill: true, tension: .3, pointRadius: 2 },
      { label: 'Lượt đặt', data: monthlyTrendRows.map(row => Number(row.bookings || 0)), yAxisID: 'yBookings', borderColor: colors[1], backgroundColor: colors[1], borderWidth: 2, fill: false, tension: .3, pointRadius: 3 },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { position: 'bottom', labels: { color: chartMuted(), usePointStyle: true, boxWidth: 8, padding: 14, font: { size: 11 } } }, tooltip: { callbacks: { label: item => `${item.dataset.label}: ${item.dataset.yAxisID === 'yRevenue' ? money(item.parsed.y) : `${item.parsed.y} lượt`}` } } },
    scales: {
      x: { grid: { display: false }, ticks: { color: chartMuted(), maxRotation: 0 } },
      yRevenue: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: chartLine() }, ticks: { color: chartMuted(), callback: value => new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) } },
      yBookings: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0, color: chartMuted() } },
    },
  },
});

const weekdayCanvas = document.getElementById('weekdayChart');
const weekdayOrder = [1,2,3,4,5,6,0];
const weekdayLabels = weekdayRows.map(row => row.label);
if (weekdayCanvas && window.Chart) new Chart(weekdayCanvas, {
  type: 'bar',
  data: { labels: weekdayLabels, datasets: [{ label: 'Lượt đặt', data: weekdayRows.map(row => Number(row.bookings || 0)), backgroundColor: weekdayOrder.map((_, index) => colors[index % colors.length]), borderRadius: 7, maxBarThickness: 46 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: item => `${item.parsed.y} lượt đặt` } } }, scales: { x: { grid: { display: false }, ticks: { color: chartMuted() } }, y: { beginAtZero: true, ticks: { precision: 0, color: chartMuted() }, grid: { color: chartLine() } } } },
});
function applyCrossFilter() {
  const rows = visibleRows();
  const paid = rows.filter(row => row.payment_status === 'paid' && row.status !== 'cancelled');
  const revenue = paid.reduce((sum,row) => sum + Number(row.total_price || 0), 0);
  const nights = rows.reduce((sum,row) => sum + Math.max(1, Math.round((new Date(row.check_out) - new Date(row.check_in)) / 86400000)), 0);
  const completed = rows.filter(row => row.status === 'completed').length;
  const dates = rows.flatMap(row => [new Date(row.check_in), new Date(row.check_out)]).filter(date => !Number.isNaN(date.valueOf()));
  const periodDays = dates.length ? Math.max(1, Math.round((new Date(Math.max(...dates)) - new Date(Math.min(...dates))) / 86400000) + 1) : 1;
  const inventory = selectedType ? Number(roomInventory[selectedType] || 1) : Math.max(1, Object.values(roomInventory).reduce((sum,value) => sum + Number(value), 0));
  document.querySelector('[data-kpi="revenue"]').textContent = money(revenue);
  document.querySelector('[data-kpi="bookings"]').textContent = new Intl.NumberFormat('vi-VN').format(rows.length);
  document.querySelector('[data-kpi="nights"]').textContent = `${new Intl.NumberFormat('vi-VN').format(nights)} đêm`;
  document.querySelector('[data-kpi="adr"]').textContent = money(nights ? revenue / nights : 0);
  document.querySelector('[data-kpi="revpar"]').textContent = money(revenue / (inventory * periodDays));
  document.querySelector('[data-kpi="completion"]').textContent = `${rows.length ? (completed / rows.length * 100).toLocaleString('vi-VN',{maximumFractionDigits:1}) : 0}%`;
  document.querySelector('[data-visible-bookings]').textContent = `${rows.length} bản ghi`;
  document.querySelectorAll('[data-report-booking]').forEach(row => row.hidden = !rows.some(item => String(item.id) === row.dataset.reportBooking));

  const typeChart = Chart.getChart(typeCanvas);
  if (typeChart) {
    typeChart.data.datasets[0].backgroundColor = typeRows.map((row,index) => !selectedType || Number(row.id) === selectedType ? colors[index % colors.length] : `${colors[index % colors.length]}33`);
    typeChart.update();
  }
  const stateChart = Chart.getChart(statusCanvas);
  if (stateChart) {
    const typeFilteredRows = bookingRows.filter(row => !selectedType || typeIds(row).includes(selectedType));
    stateChart.data.datasets[0].data = statusValues.map(status => typeFilteredRows.filter(row => row.status === status).length);
    stateChart.data.datasets[0].backgroundColor = statusValues.map((status,index) => !selectedStatus || status === selectedStatus ? colors[index] : `${colors[index]}33`);
    stateChart.update();
  }
  const statusTrendChart = Chart.getChart(statusTrendCanvas);
  if (statusTrendChart) {
    const typeFilteredRows = bookingRows.filter(row => !selectedType || typeIds(row).includes(selectedType));
    const monthlyCounts = new Map();
    typeFilteredRows.forEach(row => {
      const period = String(row.check_in).slice(0, 7);
      if (!monthlyCounts.has(period)) monthlyCounts.set(period, Object.fromEntries(trendStatusValues.map(status => [status, 0])));
      if (trendStatusValues.includes(row.status)) monthlyCounts.get(period)[row.status]++;
    });
    statusTrendChart.data.labels = statusTrendRows.map(row => row.period);
    statusTrendChart.data.datasets.forEach((dataset,index) => {
      const status = trendStatusValues[index];
      dataset.data = statusTrendChart.data.labels.map(period => monthlyCounts.get(period)?.[status] || 0);
      dataset.backgroundColor = !trendStatusValues.includes(selectedStatus) || status === selectedStatus ? statusColor[status] : `${statusColor[status]}33`;
    });
    statusTrendChart.update();
  }
  const monthlyChart = Chart.getChart(monthlyTrendCanvas);
  if (monthlyChart) {
    const rowsByMonth = new Map();
    (canFilterBookingRows ? rows : []).forEach(row => {
      const period = String(row.check_in).slice(0, 7);
      if (!rowsByMonth.has(period)) rowsByMonth.set(period, { revenue: 0, bookings: 0 });
      const item = rowsByMonth.get(period);
      item.bookings++;
      if (row.payment_status === 'paid' && row.status !== 'cancelled') item.revenue += Number(row.total_price || 0);
    });
    const monthlyRows = canFilterBookingRows
      ? monthlyTrendRows.map(row => ({ ...row, ...(rowsByMonth.get(row.period) || { revenue: 0, bookings: 0 }) }))
      : monthlyTrendRows;
    monthlyChart.data.datasets[0].data = monthlyRows.map(row => Number(row.revenue || 0));
    monthlyChart.data.datasets[1].data = monthlyRows.map(row => Number(row.bookings || 0));
    monthlyChart.update();
  }
  const weekdayChart = Chart.getChart(weekdayCanvas);
  if (weekdayChart && canFilterBookingRows) {
    const weekdayCounts = new Map(weekdayOrder.map(day => [day, 0]));
    rows.forEach(row => {
      const [year, month, day] = String(row.check_in).slice(0, 10).split('-').map(Number);
      if (year && month && day) weekdayCounts.set(new Date(year, month - 1, day).getDay(), (weekdayCounts.get(new Date(year, month - 1, day).getDay()) || 0) + 1);
    });
    weekdayChart.data.datasets[0].data = weekdayOrder.map(day => weekdayCounts.get(day) || 0);
    weekdayChart.update();
  }
  const url = new URL(location.href);
  selectedType ? url.searchParams.set('room_type_id', selectedType) : url.searchParams.delete('room_type_id');
  selectedStatus ? url.searchParams.set('status', selectedStatus) : url.searchParams.delete('status');
  history.replaceState({}, '', url);
  document.querySelector('[data-chart-range].is-active')?.click();
}
if (canFilterBookingRows) applyCrossFilter();
window.addEventListener('internalthemechange', () => {
  Object.values(Chart.instances).forEach(chart => {
    for (const scale of Object.values(chart.options.scales || {})) {
      if (scale.ticks) scale.ticks.color = chartMuted();
      if (scale.grid?.display !== false) scale.grid.color = chartLine();
    }
    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = chartMuted();
    chart.update('none');
  });
});
});
</script>
@endpush
