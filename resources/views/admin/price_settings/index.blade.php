@extends('layouts.admin')
@section('title','Điều chỉnh giá · Posh Boutique')
@section('page-title','Chiến lược giá')
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1" style="color: #0f172a; letter-spacing: -0.03em;">Điều chỉnh giá theo mùa</h1>
        <p class="text-muted small mb-0" style="color: #64748b;">Thiết lập mức giá cho lễ, sự kiện và giai đoạn cao điểm.</p>
    </div>
    <a class="btn btn-dark d-inline-flex align-items-center gap-2 px-3 py-2 fw-medium" href="{{ route('admin.price-settings.create') }}" style="border-radius: 999px; background: #070709; font-size: 0.82rem; border: none;">
        <i class="bi bi-plus-lg"></i>
        <span>Thêm chính sách</span>
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Tổng chính sách</span>
                <i class="bi bi-sliders text-muted" style="font-size: 1rem;"></i>
            </div>
            <div class="fs-4 fw-bold text-dark" style="letter-spacing: -0.02em;">{{ $settings->count() }}</div>
            <small class="text-muted" style="font-size: 0.72rem;">Cấu hình trong hệ thống</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Đang áp dụng</span>
                <i class="bi bi-check-circle text-success" style="font-size: 1rem;"></i>
            </div>
            <div class="fs-4 fw-bold text-success" style="letter-spacing: -0.02em;">{{ $settings->where('status',1)->count() }}</div>
            <small class="text-muted" style="font-size: 0.72rem;">Chính sách đang bật</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 p-3 h-100" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.06) !important; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.01);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold" style="font-size: 0.75rem;">Sắp diễn ra</span>
                <i class="bi bi-calendar-event text-primary" style="font-size: 1rem;"></i>
            </div>
            <div class="fs-4 fw-bold text-primary" style="letter-spacing: -0.02em;">{{ $settings->where('start_date','>=',now()->toDateString())->count() }}</div>
            <small class="text-muted" style="font-size: 0.72rem;">Theo ngày bắt đầu</small>
        </div>
    </div>
</div>

<section class="card border-0 mb-4" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.06) !important; box-shadow: 0 1px 3px rgba(0,0,0,0.01); background: #ffffff; overflow: hidden;">
    <header class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06); background: #ffffff;">
        <h2 class="h6 fw-bold m-0" style="color: #0f172a;">Lịch điều chỉnh giá</h2>
        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 500; font-size: 0.72rem; padding: 5px 10px; border-radius: 999px;">Giá gốc được giữ ngoài thời gian áp dụng</span>
    </header>
    <div class="table-responsive">
        <table class="table align-middle m-0" style="border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc;">
                    <th class="ps-4 py-3 text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Tên chính sách</th>
                    <th class="py-3 text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Thời gian</th>
                    <th class="py-3 text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Mức điều chỉnh</th>
                    <th class="py-3 text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Trạng thái</th>
                    <th class="pe-4 py-3 text-end text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.04); transition: background-color 0.15s ease;">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f0f7ff; color: #0284c7; flex-shrink: 0;">
                                <i class="bi bi-calendar2-event" style="font-size: 0.85rem;"></i>
                            </span>
                            <div>
                                <strong class="d-block text-dark fw-bold" style="font-size: 0.85rem; color: #0f172a;">{{ $setting->name }}</strong>
                                <small class="text-muted" style="font-size: 0.72rem;">#{{ $setting->id }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <div class="fw-medium text-dark" style="font-size: 0.82rem;">{{ date('d/m/Y',strtotime($setting->start_date)) }} &rarr; {{ date('d/m/Y',strtotime($setting->end_date)) }}</div>
                    </td>
                    <td class="py-3">
                        <span class="fw-bold" style="color: #0284c7; font-size: 0.88rem;">+{{ $setting->adjustment_type==='percent' ? rtrim(rtrim(number_format($setting->adjustment_value,2,'.',''),'0'),'.').'%' : number_format($setting->adjustment_value,0,',','.').'đ' }}</span>
                    </td>
                    <td class="py-3">
                        @if($setting->status)
                            <span class="badge" style="background: #ecfdf5; color: #047857; font-size: 0.74rem; font-weight: 600; padding: 5px 10px; border-radius: 999px;">Đang bật</span>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.74rem; font-weight: 600; padding: 5px 10px; border-radius: 999px;">Đang tắt</span>
                        @endif
                    </td>
                    <td class="pe-4 py-3 text-end">
                        <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('admin.price-settings.edit',$setting->id) }}" class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center p-0" title="Chỉnh sửa" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); background: #ffffff; color: #475569;">
                                <i class="bi bi-pencil" style="font-size: 0.8rem;"></i>
                            </a>
                            <form action="{{ route('admin.price-settings.delete',$setting->id) }}" method="POST" data-confirm="Xóa chính sách giá này? Giá phòng sẽ trở về mức áp dụng của các chính sách còn lại." class="d-inline m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center p-0" title="Xóa" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.06); background: #ffffff; color: #ef4444;">
                                    <i class="bi bi-trash3" style="font-size: 0.8rem;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted" style="font-size: 0.88rem;">Chưa có chính sách giá. Hãy tạo cấu hình đầu tiên.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
