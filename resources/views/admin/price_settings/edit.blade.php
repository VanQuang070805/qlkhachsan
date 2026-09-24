@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex align-items-center">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Sửa cài đặt điều chỉnh giá</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.price-settings.update', $setting->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên dịp lễ / sự kiện</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $setting->name) }}" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Từ ngày</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $setting->start_date) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Đến ngày</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $setting->end_date) }}" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kiểu điều chỉnh</label>
                                <select name="adjustment_type" class="form-select">
                                    <option value="percent" {{ old('adjustment_type', $setting->adjustment_type) == 'percent' ? 'selected' : '' }}>Tăng theo phần trăm (%)</option>
                                    <option value="fixed" {{ old('adjustment_type', $setting->adjustment_type) == 'fixed' ? 'selected' : '' }}>Tăng số tiền cố định (VNĐ)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Giá trị</label>
                                <input type="number" step="0.01" name="adjustment_value" class="form-control" value="{{ old('adjustment_value', $setting->adjustment_value) }}" required>
                            </div>
                        </div>
                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', $setting->status) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="status">Kích hoạt</label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.price-settings.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
