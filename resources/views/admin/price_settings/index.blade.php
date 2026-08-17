@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-3 admin-sidebar">
            <div class="list-group shadow-sm">
               
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-people me-2"></i> Quản lý người dùng
                </a>
                <a href="{{ route('admin.price-settings.index') }}" class="list-group-item list-group-item-action active">
                    <i class="bi bi-tags me-2"></i> Cài đặt điều chỉnh giá
                </a>
                
                <a href="{{ route('admin.reports') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-bar-chart-line me-2"></i> Báo cáo thống kê
                </a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cài đặt điều chỉnh giá (Lễ/Tết)</h5>
                    <a href="{{ route('admin.price-settings.create') }}" class="btn btn-sm btn-light">Thêm mới</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Tên dịp</th>
                                    <th>Từ ngày</th>
                                    <th>Đến ngày</th>
                                    <th>Điều chỉnh</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($settings as $setting)
                                <tr>
                                    <td>{{ $setting->id }}</td>
                                    <td>{{ $setting->name }}</td>
                                    <td>{{ date('d/m/Y', strtotime($setting->start_date)) }}</td>
                                    <td>{{ date('d/m/Y', strtotime($setting->end_date)) }}</td>
                                    <td>
                                        @if($setting->adjustment_type == 'percent')
                                            Tăng {{ $setting->adjustment_value }}%
                                        @else
                                            Tăng {{ number_format($setting->adjustment_value, 0, ',', '.') }} VNĐ
                                        @endif
                                    </td>
                                    <td>
                                        @if($setting->status)
                                            <span class="badge bg-success">Bật</span>
                                        @else
                                            <span class="badge bg-secondary">Tắt</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.price-settings.edit', $setting->id) }}" class="btn btn-sm btn-primary">Sửa</a>
                                        <form action="{{ route('admin.price-settings.delete', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa cài đặt này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Chưa có cài đặt nào.</td>
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
@endsection
