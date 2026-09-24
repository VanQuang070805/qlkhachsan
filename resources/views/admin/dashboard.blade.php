@extends('layouts.admin')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-3 admin-sidebar">
            <div class="list-group shadow-sm">
                
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-people me-2"></i> Quản lý người dùng
                </a>
                <a href="{{ route('admin.price-settings.index') }}"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i> Cài đặt điều chỉnh giá
                </a>
               
                
                <a href="{{ route('admin.reports') }}"
                   class="list-group-item list-group-item-action">
                    <i class="bi bi-bar-chart-line me-2"></i> Báo cáo thống kê
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-speedometer2 me-2"></i> Tổng quan
                    </h5>
                </div>
                <div class="card-body">
                    <p>Chào mừng, <strong>{{ session('user.fullname') }}</strong>! Đây là trang quản trị nội bộ.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection