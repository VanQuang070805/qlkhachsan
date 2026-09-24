@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 admin-sidebar">
            <div class="list-group shadow-sm">
                
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-people me-2"></i> Quản lý người dùng
                </a>
                <a href="{{ route('admin.price-settings.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i> Cài đặt điều chỉnh giá
                </a>
                
                <a href="{{ route('admin.reports') }}" class="list-group-item list-group-item-action active">
                    <i class="bi bi-bar-chart-line me-2"></i> Báo cáo thống kê
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="col-md-9">
            
            <!-- Bộ lọc (Filters) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-funnel me-2"></i> Bộ lọc Báo cáo</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Từ ngày (Check-in)</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Đến ngày (Check-in)</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Loại phòng</label>
                                <select name="room_type_id" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    @foreach ($roomTypes as $rt)
                                        <option value="{{ $rt->id }}" {{ ($filters['room_type_id'] ?? '') == $rt->id ? 'selected' : '' }}>
                                            {{ $rt->type_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">-- Tất cả --</option>
                                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Chờ xác nhận</option>
                                    <option value="confirmed" {{ ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                </select>
                            </div>
                            <div class="col-12 mt-4 text-end">
                                <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary me-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Làm mới
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Lọc dữ liệu
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tổng quan (KPIs) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">Tổng doanh thu</h6>
                            <h3 class="fw-bold mb-0">{{ number_format($stats['total_revenue'] ?? 0, 0, ',', '.') }}đ</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">Tổng lượt đặt phòng</h6>
                            <h3 class="fw-bold mb-0">{{ number_format($stats['total_bookings'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">Khách hàng đăng ký</h6>
                            <h3 class="fw-bold mb-0">{{ number_format($stats['total_customers'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                        <div class="card-body py-2">
                            <p class="text-muted small mb-1">Tổng số đêm</p>
                            <h5 class="fw-bold mb-0">{{ number_format($stats['total_nights'] ?? 0, 0, ',', '.') }} đêm</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                        <div class="card-body py-2">
                            <p class="text-muted small mb-1">DT trung bình/đơn</p>
                            <h5 class="fw-bold mb-0">{{ number_format($stats['avg_revenue'] ?? 0, 0, ',', '.') }}đ</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                        <div class="card-body py-2">
                            <p class="text-muted small mb-1">Tỷ lệ hủy</p>
                            <h5 class="fw-bold mb-0">{{ number_format($stats['cancel_rate'] ?? 0, 1, ',', '.') }}%</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary">
                        <div class="card-body py-2">
                            <p class="text-muted small mb-1">Trạng thái (C/X/H)</p>
                            <h5 class="fw-bold mb-0">
                                <span class="text-warning">{{ $stats['pending_count'] ?? 0 }}</span> / 
                                <span class="text-success">{{ $stats['confirmed_count'] ?? 0 }}</span> / 
                                <span class="text-danger">{{ $stats['cancelled_count'] ?? 0 }}</span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-4 text-dark"><i class="bi bi-graph-up me-2"></i>Biểu đồ Doanh thu theo Ngày Check-in</h6>
                    <div style="position: relative; height: 350px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bảng chi tiết -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-stars me-2"></i>Chi tiết Đặt phòng</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Mã ĐP</th>
                                    <th>Khách hàng</th>
                                    <th>Loại phòng</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $b)
                                    <tr>
                                        <td class="ps-4 fw-bold">#{{ $b->id }}</td>
                                        <td>
                                            {{ $b->customer_name }}<br>
                                            <small class="text-muted">{{ $b->customer_phone }}</small>
                                        </td>
                                        <td>{{ $b->type_name ?? 'N/A' }}</td>
                                        <td>{{ date('d/m/Y', strtotime($b->check_in)) }}</td>
                                        <td>{{ date('d/m/Y', strtotime($b->check_out)) }}</td>
                                        <td class="fw-bold text-success">{{ number_format($b->total_price, 0, ',', '.') }}đ</td>
                                        <td>
                                            @if ($b->status === 'pending')
                                                <span class="badge bg-warning text-dark">Chờ XN</span>
                                            @elseif ($b->status === 'confirmed')
                                                <span class="badge bg-success">Đã XN</span>
                                            @elseif ($b->status === 'cancelled')
                                                <span class="badge bg-danger">Đã hủy</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($b->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Không có dữ liệu phù hợp với bộ lọc.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const rawData = {!! $chartData !!};
        
        const labels = rawData.map(item => item.date);
        const data = rawData.map(item => item.revenue);
 
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0d6efd',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' đ';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN').format(value);
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
