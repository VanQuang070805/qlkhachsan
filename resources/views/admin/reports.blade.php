@extends('layouts.admin')
@section('title','Tổng quan · Royal Hotel')
@section('page-title','Tổng quan')
@section('content')
<form class="report-filter" method="GET" action="{{ route('admin.reports') }}">
  <label>Từ ngày<input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></label>
  <label>Đến ngày<input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></label>
  <label>Loại phòng<select name="room_type_id"><option value="">Tất cả</option>@foreach($roomTypes as $rt)<option value="{{ $rt->id }}" @selected(($filters['room_type_id'] ?? '') == $rt->id)>{{ $rt->type_name }}</option>@endforeach</select></label>
  <label>Trạng thái<select name="status"><option value="">Tất cả</option><option value="pending" @selected(($filters['status'] ?? '')==='pending')>Chờ xác nhận</option><option value="confirmed" @selected(($filters['status'] ?? '')==='confirmed')>Đã xác nhận</option><option value="checked_in" @selected(($filters['status'] ?? '')==='checked_in')>Đang lưu trú</option><option value="completed" @selected(($filters['status'] ?? '')==='completed')>Hoàn thành</option><option value="cancelled" @selected(($filters['status'] ?? '')==='cancelled')>Đã hủy</option></select></label>
  <button type="submit"><i class="bi bi-funnel"></i> Áp dụng</button><a href="{{ route('admin.reports') }}" aria-label="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></a>
</form>

<div class="operations-overview">
  <section class="metric-grid">
    <button type="button" class="metric metric-primary is-active" data-chart-metric="revenue" aria-pressed="true"><span>Doanh thu</span><strong data-kpi="revenue">{{ number_format($stats['total_revenue'] ?? 0,0,',','.') }} ₫</strong><small>Chạm để phân tích</small></button>
    <button type="button" class="metric" data-chart-metric="bookings" aria-pressed="false"><span>Lượt đặt</span><strong data-kpi="bookings">{{ number_format($stats['total_bookings'] ?? 0) }}</strong><small data-kpi="nights">{{ number_format($stats['total_nights'] ?? 0) }} đêm</small></button>
    <article class="metric"><span>ADR</span><strong data-kpi="adr">{{ number_format($stats['adr'] ?? 0,0,',','.') }} ₫</strong><small>Giá bình quân / đêm</small></article>
    <article class="metric"><span>RevPAR</span><strong data-kpi="revpar">{{ number_format($stats['revpar'] ?? 0,0,',','.') }} ₫</strong><small>Doanh thu / phòng</small></article>
    <article class="metric"><span>Tỉ lệ hoàn tất</span><strong data-kpi="completion">{{ number_format((($stats['completed_count'] ?? 0) / max(1, $stats['total_bookings'] ?? 0)) * 100,1,',','.') }}%</strong><small>Cập nhật theo lựa chọn</small></article>
  </section>

  <section class="report-charts" aria-label="Biểu đồ báo cáo">
    <article class="data-panel chart-panel chart-panel--trend"><header><h2 id="trend-chart-title">Doanh thu theo ngày</h2><div class="chart-tools" aria-label="Khoảng thời gian"><button type="button" data-chart-range="7" aria-pressed="false">7 ngày</button><button type="button" data-chart-range="30" class="is-active" aria-pressed="true">30 ngày</button><button type="button" data-chart-range="all" aria-pressed="false">Tất cả</button></div></header><div class="chart-wrap"><canvas id="trendChart"></canvas></div></article>
    <article class="data-panel"><header><h2>Đặt theo hạng phòng</h2><span class="panel-note">Bấm cột để lọc</span></header><div class="chart-wrap chart-wrap--compact"><canvas id="roomTypeChart"></canvas></div></article>
    <article class="data-panel"><header><h2>Trạng thái đặt phòng</h2><span class="panel-note">Theo dữ liệu hiện tại</span></header><div class="chart-wrap chart-wrap--compact"><canvas id="statusChart"></canvas></div></article>
  </section>
</div>

<section class="data-panel bookings-panel"><header><h2>Đặt phòng gần đây</h2><span class="panel-note" data-visible-bookings>{{ count($bookings) }} bản ghi</span></header><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Hạng phòng</th><th>Lưu trú</th><th>Giá trị</th><th>Trạng thái</th></tr></thead><tbody>@forelse($bookings as $b)<tr data-report-booking="{{ $b->id }}"><td><strong>#{{ $b->id }}</strong></td><td>{{ $b->customer_name }}<small>{{ $b->customer_phone }}</small></td><td>{{ $b->type_name ?? 'Chưa xếp' }}</td><td>{{ date('d/m/Y',strtotime($b->check_in)) }}<small>đến {{ date('d/m/Y',strtotime($b->check_out)) }}</small></td><td><strong>{{ number_format($b->total_price,0,',','.') }} ₫</strong></td><td><span class="status-pill status-{{ $b->status }}">{{ ['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','checked_in'=>'Đang lưu trú','cancelled'=>'Đã hủy','completed'=>'Hoàn thành'][$b->status] ?? ucfirst($b->status) }}</span></td></tr>@empty<tr><td colspan="6" class="empty-state">Không có dữ liệu phù hợp.</td></tr>@endforelse</tbody></table></div></section>
@endsection

@push('scripts')
<script type="module">
document.addEventListener('DOMContentLoaded', () => {
const trendRows = @json($chartData);
const typeRows = @json($typeChartData);
const statusRows = @json($statusChartData);
const bookingRows = @json($bookings);
const roomInventory = @json($roomInventory);
const colors = ['#6ea8df','#55b89a','#e6b85c','#ad91e8','#e78383'];
const money = value => new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
const chartMuted = () => getComputedStyle(document.body).getPropertyValue('--muted').trim() || '#697780';
const chartLine = () => getComputedStyle(document.body).getPropertyValue('--line').trim() || '#e4eaee';
const statusValues = ['pending','confirmed','checked_in','completed','cancelled'];
let selectedType = null;
let selectedStatus = null;
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
    const filteredTrend = groupTrend(visibleRows());
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
  options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: item => `${item.parsed.x} lượt đặt` } } }, scales: { x: { beginAtZero: true, ticks: { precision: 0, color: chartMuted() }, grid: { color: chartLine() } }, y: { ticks: { color: chartMuted() }, grid: { display: false } } }, onClick: (_event, hits) => { if (!hits.length) return; const id = Number(typeRows[hits[0].index].id); selectedType = selectedType === id ? null : id; applyCrossFilter(); } }
});

const statusCanvas = document.getElementById('statusChart');
if (statusCanvas && window.Chart) new Chart(statusCanvas, {
  type: 'doughnut',
  data: { labels: statusRows.map(row => row[0]), datasets: [{ data: statusRows.map(row => Number(row[1])), backgroundColor: colors, borderWidth: 0, hoverOffset: 7 }] },
  options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { color: chartMuted(), usePointStyle: true, boxWidth: 7, padding: 13, font: { size: 11 } } }, tooltip: { callbacks: { label: item => `${item.label}: ${item.parsed}` } } }, onClick: (_event, hits) => { if (!hits.length) return; const value = statusValues[hits[0].index]; selectedStatus = selectedStatus === value ? null : value; applyCrossFilter(); } }
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
    const base = bookingRows.filter(row => !selectedType || typeIds(row).includes(selectedType));
    stateChart.data.datasets[0].data = statusValues.map(value => base.filter(row => row.status === value).length);
    stateChart.data.datasets[0].backgroundColor = colors.map((color,index) => !selectedStatus || statusValues[index] === selectedStatus ? color : `${color}33`);
    stateChart.update();
  }
  const url = new URL(location.href);
  selectedType ? url.searchParams.set('room_type_id', selectedType) : url.searchParams.delete('room_type_id');
  selectedStatus ? url.searchParams.set('status', selectedStatus) : url.searchParams.delete('status');
  history.replaceState({}, '', url);
  document.querySelector('[data-chart-range].is-active')?.click();
}
applyCrossFilter();
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
