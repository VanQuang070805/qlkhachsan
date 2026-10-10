@extends('layouts.admin')

@section('title', 'Phòng & hạng phòng · Rosaliza Hotel')
@section('page-title', 'Phòng & hạng phòng')

@push('styles')
<style>
/* Layout Shell */
.room-admin-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
    padding-bottom: 36px;
}

/* Header */
.room-admin-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 18px;
    padding: 18px 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.01);
}
.room-admin-head__info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.room-admin-head__title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}
.room-admin-head__actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.btn-room-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 38px;
    padding: 0 14px;
    border-radius: 11px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.16s ease;
    white-space: nowrap;
}
.btn-room-action--primary {
    background: #0f172a;
    color: #ffffff !important;
    border: 1px solid #0f172a;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
}
.btn-room-action--primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}
.btn-room-action--ghost {
    background: #ffffff;
    color: #1e293b !important;
    border: 1px solid #cbd5e1;
}
.btn-room-action--ghost:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    transform: translateY(-1px);
}
.btn-room-action--secondary {
    background: #f8fafc;
    color: #475569 !important;
    border: 1px solid #e2e8f0;
}
.btn-room-action--secondary:hover {
    background: #f1f5f9;
    color: #0f172a !important;
}

/* Main Panels */
.room-panel {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.01);
}
.room-panel__head {
    padding: 16px 22px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
}
.room-panel__head-info h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}
.room-panel__head-info p {
    font-size: 0.76rem;
    color: #64748b;
    margin: 2px 0 0 0;
}
.room-panel-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #334155;
    font-size: 0.74rem;
    font-weight: 600;
}

/* Cupertino Floor Navigation Rail */
.room-floor-rail-wrap {
    background: #f8fafc;
    border-bottom: 1px solid #edf2f7;
    padding: 10px 18px;
}
.room-floor-rail {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: thin;
    padding-bottom: 2px;
}
.room-floor-tab {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    color: #475569;
    text-decoration: none;
    white-space: nowrap;
    scroll-snap-align: start;
    transition: all 0.16s ease;
    cursor: pointer;
    font-size: 0.8rem;
}
.room-floor-tab:hover {
    color: #0f172a;
    border-color: #cbd5e1;
    background: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}
.room-floor-tab.is-active,
.room-floor-tab[aria-current="page"] {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.16);
}
.room-floor-tab__num {
    font-weight: 700;
    font-size: 0.92rem;
    font-variant-numeric: tabular-nums;
}
.room-floor-tab__badge {
    padding: 2px 7px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 600;
}
.room-floor-tab.is-active .room-floor-tab__badge,
.room-floor-tab[aria-current="page"] .room-floor-tab__badge {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
}

/* Cupertino Room Table (Desktop) */
.room-table-desktop {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.room-table-desktop th {
    padding: 12px 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #edf2f7;
    text-align: left;
}
.room-table-desktop td {
    padding: 13px 20px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    font-size: 0.84rem;
    vertical-align: middle;
}
.room-table-desktop tr:last-child td {
    border-bottom: none;
}
.room-table-desktop tr:hover td {
    background: #fafbfc;
}
.room-num-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 9px;
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    font-size: 0.9rem;
    font-variant-numeric: tabular-nums;
    border: 1px solid #e2e8f0;
}
.room-type-meta {
    display: flex;
    flex-direction: column;
}
.room-type-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.85rem;
    white-space: nowrap;
}
.room-type-sub {
    font-size: 0.72rem;
    color: #64748b;
}

/* Apple Status Indicators */
.apple-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}
.apple-status-pill .apple-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.apple-status-pill--available { background: #ecfdf5; color: #047857; }
.apple-status-pill--available .apple-dot { background: #10b981; }
.apple-status-pill--occupied { background: #eff6ff; color: #1d4ed8; }
.apple-status-pill--occupied .apple-dot { background: #3b82f6; }
.apple-status-pill--reserved { background: #fef3c7; color: #b45309; }
.apple-status-pill--reserved .apple-dot { background: #f59e0b; }
.apple-status-pill--cleaning { background: #f1f5f9; color: #475569; }
.apple-status-pill--cleaning .apple-dot { background: #94a3b8; }
.apple-status-pill--maintenance { background: #fee2e2; color: #b91c1c; }
.apple-status-pill--maintenance .apple-dot { background: #ef4444; }

.btn-edit-room {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 32px;
    padding: 0 11px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 600;
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
    transition: all 0.16s ease;
    cursor: pointer;
    white-space: nowrap;
}
.btn-edit-room:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    transform: translateY(-1px);
}

/* Mobile Room Cards */
.room-cards-mobile {
    display: none;
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 14px;
}
.room-card-mob {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.room-card-mob__top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.room-card-mob__body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.room-card-mob .btn-edit-room {
    width: 100%;
    justify-content: center;
    height: 36px;
}

/* Room Types Bento Grid */
.room-type-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    padding: 20px;
}
.room-type-card-bento {
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.07);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
}
.room-type-card-bento:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.room-type-card-bento__thumb {
    position: relative;
    width: 100%;
    height: 154px;
    overflow: hidden;
    background: #f1f5f9;
}
.room-type-card-bento__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}
.room-type-card-bento:hover .room-type-card-bento__thumb img {
    transform: scale(1.04);
}
.thumb-overlay-badge {
    position: absolute;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}
.thumb-overlay-badge--left {
    top: 10px;
    left: 10px;
    background: rgba(15, 23, 42, 0.75);
    color: #ffffff;
}
.thumb-overlay-badge--right {
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    color: #0f172a;
}
.room-type-card-bento__body {
    padding: 16px 18px 18px;
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    gap: 11px;
}
.room-type-card-bento__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}
.room-type-card-bento__name {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    margin: 0;
}
.room-type-card-bento__price {
    text-align: right;
    white-space: nowrap;
}
.room-type-card-bento__price .price-num {
    font-size: 1.02rem;
    font-weight: 700;
    color: #0071e3;
    letter-spacing: -0.01em;
}
.room-type-card-bento__price .price-unit {
    font-size: 0.72rem;
    color: #64748b;
    font-weight: 500;
}
.room-type-specs {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.74rem;
    color: #64748b;
    font-weight: 500;
}
.room-type-amenities-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: auto;
}
.room-type-amenity-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.69rem;
    font-weight: 500;
}
.room-type-amenity-chip.is-empty {
    color: #94a3b8;
    background: #f8fafc;
}
.room-type-card-bento__actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.btn-edit-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 33px;
    padding: 0 13px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 600;
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    transition: all 0.16s ease;
    cursor: pointer;
}
.btn-edit-type:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    transform: translateY(-1px);
}

/* Modal styles preserved exactly */
.room-modal .modal-content{border:1px solid #dfe5eb;border-radius:16px;overflow:hidden;box-shadow:0 24px 72px rgba(15,23,42,.2)}.room-modal__head{display:grid;grid-template-columns:62px 1fr 28px;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #edf0f3}.room-modal__head h2{margin:0;text-align:center;color:#17212b;font-size:.98rem;font-weight:700}.room-modal__lights{display:flex;align-items:center;gap:7px;width:max-content}.room-modal__lights .ctrl-dot{width:12px;height:12px;border-radius:50%;display:inline-block;flex:0 0 12px;border:1px solid rgba(0,0,0,.12)}.room-modal__close{width:26px;height:26px;padding:0;border:0;border-radius:50%;background:#f1f5f9;color:#526171;font-size:1.15rem;line-height:1;display:grid;place-items:center;justify-self:end}.room-modal__close:hover{background:#e7edf2;color:#17212b}.room-modal__body{padding:20px}.room-modal__footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;background:#f8fafc;border-top:1px solid #edf0f3}.room-field label{display:block;font-size:.75rem;font-weight:600;color:#475569;margin-bottom:5px}.room-field input,.room-field select,.room-field textarea{width:100%;border:1px solid #d1d5db;border-radius:9px;padding:9px 10px;background:#fff;color:#111827;font-size:.84rem}.room-field textarea{min-height:74px}.room-field-error{display:block;margin-top:5px;color:#b42318;font-size:.75rem}.room-empty{padding:32px 16px;text-align:center;color:#64748b}.room-form-grid{display:grid;gap:12px;align-items:end}.room-submit{height:39px;border:0;border-radius:10px;background:#111827;color:white;padding:0 14px;font-weight:600;font-size:.82rem;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:transform .18s ease,background .18s ease}.room-submit:hover{background:#26394e;color:#fff;transform:translateY(-1px)}.room-hint{color:#64748b;font-size:.76rem}.room-image-upload{display:grid;gap:8px}.room-image-upload__preview{height:132px;display:grid;place-items:center;overflow:hidden;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#64748b;font-size:.78rem}.room-image-upload__preview img{width:100%;height:100%;object-fit:cover}.room-image-upload input[type=file]{padding:8px;background:#fff}.room-image-upload input[type=file]::file-selector-button{margin-right:10px;padding:6px 10px;border:1px solid #d1d5db;border-radius:7px;background:#f8fafc;color:#334155;font-size:.78rem}.room-gallery{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.room-gallery__image{width:100%;aspect-ratio:4/3;object-fit:contain;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px}.room-gallery__preview-list{display:contents}.room-gallery__empty{grid-column:1/-1;min-height:110px;display:grid;place-items:center;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;background:#f8fafc;font-size:.8rem}.room-gallery__error{display:block;color:#b42318;font-size:.75rem}.room-gallery__hint{color:#64748b;font-size:.76rem}.room-gallery__preview-item{position:relative;min-width:0}.room-gallery__remove{position:absolute;top:7px;right:7px;width:30px;height:30px;display:grid;place-items:center;padding:0;border:1px solid rgba(15,23,42,.12);border-radius:50%;background:rgba(255,255,255,.96);color:#334155;font-size:20px;line-height:1;box-shadow:0 2px 8px rgba(15,23,42,.18);cursor:pointer;transition:background .15s ease,color .15s ease,transform .15s ease}.room-gallery__remove:hover{background:#b42318;color:#fff;transform:scale(1.06)}.room-gallery__remove:focus-visible{outline:2px solid #2563eb;outline-offset:2px}

.room-modal .modal-dialog{max-height:calc(100vh - 2rem)}.room-modal .modal-content{display:flex;max-height:calc(100vh - 2rem)}.room-modal .modal-content>form{display:flex;flex:1 1 auto;flex-direction:column;min-height:0;overflow:hidden}.room-modal__head,.room-modal__footer{flex:0 0 auto}.room-modal__body{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}.room-type-form{display:grid;gap:18px}.room-type-form__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}.room-type-form .room-field{min-width:0}.room-type-image-upload .room-image-upload__preview{height:clamp(150px,24vh,230px);padding:10px}.room-type-image-upload .room-image-upload__preview img{object-fit:contain}.room-type-form .room-amenities{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:9px 12px;padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}.room-type-form .room-amenity{display:flex;align-items:flex-start;gap:9px;min-width:0;min-height:40px;padding:9px 10px;border:1px solid #e5e7eb;border-radius:9px;background:#fff;color:#475569;font-size:.8rem;line-height:1.4;overflow-wrap:anywhere}.room-type-form .room-amenity input{width:16px;height:16px;margin:1px 0 0;flex:none;accent-color:#111827}
@media(max-width:640px){.room-type-form__grid{grid-template-columns:1fr}.room-type-form__grid>.room-field[style*="grid-column"]{grid-column:auto!important}.room-modal__body{padding:14px}.room-modal__footer{padding:12px 14px}.room-modal__footer .room-submit{white-space:normal}}

/* Responsive Rules */
@media (min-width: 1920px) {
    .room-type-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 1024px) {
    .room-type-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        padding: 16px;
    }
}
@media (max-width: 768px) {
    .room-admin-container {
        gap: 16px;
    }
    .room-admin-head {
        flex-direction: column;
        align-items: stretch;
        padding: 16px;
        gap: 14px;
    }
    .room-admin-head__actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .room-admin-head__actions .btn-room-action--primary {
        grid-column: 1 / -1;
        justify-content: center;
        height: 42px;
    }
    .room-admin-head__actions .btn-room-action--secondary,
    .room-admin-head__actions .btn-room-action--ghost {
        justify-content: center;
        height: 38px;
    }
    .room-table-desktop {
        display: none !important;
    }
    .room-cards-mobile {
        display: grid !important;
    }
    .room-type-grid {
        grid-template-columns: 1fr;
        gap: 14px;
        padding: 14px;
    }
}
</style>
@endpush

@section('content')
<div class="room-admin-container">
    {{-- Header --}}
    <header class="room-admin-head">
        <div class="room-admin-head__info">
            <h1 class="room-admin-head__title">Phòng &amp; hạng phòng</h1>
        </div>
        <div class="room-admin-head__actions">
            <a class="btn-room-action btn-room-action--secondary" href="{{ route('admin.price-settings.index') }}">
                <i class="bi bi-calendar2-range"></i> <span>Điều chỉnh giá theo dịp</span>
            </a>
            <button class="btn-room-action btn-room-action--ghost" type="button" data-bs-toggle="modal" data-bs-target="#addRoomTypeModal">
                <i class="bi bi-tags"></i> <span>Thêm hạng phòng</span>
            </button>
            <button class="btn-room-action btn-room-action--primary" type="button" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="bi bi-plus-lg"></i> <span>Thêm phòng mới</span>
            </button>
        </div>
    </header>

    @if($errors->getBag('default')->any())
        <div class="alert alert-danger mb-0" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Section 1: Quản lý phòng theo tầng --}}
    <section class="room-panel" id="admin-room-inventory">
        <header class="room-panel__head">
            <div class="room-panel__head-info">
                <h2>Danh sách phòng · Tầng {{ $selectedFloor }}</h2>
                <p>Quản lý trạng thái và hạng phòng</p>
            </div>
            <span class="room-panel-badge"><i class="bi bi-door-closed"></i> {{ $rooms->count() }} phòng hoạt động</span>
        </header>

        {{-- Floor Navigation Rail --}}
        <div class="room-floor-rail-wrap">
            <nav class="room-floor-rail" aria-label="Chọn tầng">
                @foreach($availableFloors as $floor)
                    <a class="room-floor-tab @if($selectedFloor === $floor) is-active @endif" href="{{ route('admin.rooms.index', ['floor' => $floor]) }}" @if($selectedFloor === $floor) aria-current="page" @endif>
                        <span class="room-floor-tab__num">{{ str_pad((string) $floor, 2, '0', STR_PAD_LEFT) }}</span>
                        <span>Tầng {{ $floor }}</span>
                        <span class="room-floor-tab__badge">{{ (int) ($floorCounts[$floor] ?? 0) }}p</span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Desktop Room Table --}}
        <div class="table-responsive d-none d-md-block">
            <table class="room-table-desktop">
                <thead>
                    <tr>
                        <th style="width: 140px;">Số phòng</th>
                        <th>Hạng phòng</th>
                        <th style="width: 160px;">Trạng thái</th>
                        <th style="width: 140px; text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        @php
                            $isReservedToday = $room->status === 'available' && $reservedToday->contains($room->id);
                            $statusKey = $isReservedToday ? 'reserved' : $room->status;
                            $statusLabel = match($statusKey) {
                                'available' => 'Trống',
                                'occupied' => 'Đang ở',
                                'cleaning' => 'Đang dọn',
                                'maintenance' => 'Bảo trì',
                                'overdue' => 'Quá giờ trả',
                                'reserved' => 'Đã đặt',
                                default => 'Đã đặt'
                            };
                            $statusClass = match($statusKey) {
                                'available' => 'apple-status-pill--available',
                                'occupied' => 'apple-status-pill--occupied',
                                'reserved' => 'apple-status-pill--reserved',
                                'cleaning' => 'apple-status-pill--cleaning',
                                'maintenance' => 'apple-status-pill--maintenance',
                                default => 'apple-status-pill--reserved'
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="room-num-badge">P.{{ $room->room_number }}</span>
                            </td>
                            <td>
                                <div class="room-type-meta">
                                    <span class="room-type-name">{{ $room->roomType?->type_name ?? 'Chưa gán hạng' }}</span>
                                    <span class="room-type-sub">Tối đa {{ $room->roomType?->max_guests ?? 2 }} khách · {{ $room->roomType?->max_adults ?? 1 }} người lớn</span>
                                </div>
                            </td>
                            <td>
                                <span class="apple-status-pill {{ $statusClass }}">
                                    <span class="apple-dot"></span>
                                    <span>{{ $statusLabel }}</span>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn-edit-room" type="button" data-bs-toggle="modal" data-bs-target="#editRoomModal-{{ $room->id }}">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> <span>Chỉnh sửa</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="room-empty">Tầng {{ $selectedFloor }} chưa có phòng. Bạn có thể thêm phòng vào tầng này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Room Cards --}}
        <div class="room-cards-mobile d-md-none">
            @forelse($rooms as $room)
                @php
                    $isReservedToday = $room->status === 'available' && $reservedToday->contains($room->id);
                    $statusKey = $isReservedToday ? 'reserved' : $room->status;
                    $statusLabel = match($statusKey) {
                        'available' => 'Trống',
                        'occupied' => 'Đang ở',
                        'cleaning' => 'Đang dọn',
                        'maintenance' => 'Bảo trì',
                        'overdue' => 'Quá giờ trả',
                        'reserved' => 'Đã đặt',
                        default => 'Đã đặt'
                    };
                    $statusClass = match($statusKey) {
                        'available' => 'apple-status-pill--available',
                        'occupied' => 'apple-status-pill--occupied',
                        'reserved' => 'apple-status-pill--reserved',
                        'cleaning' => 'apple-status-pill--cleaning',
                        'maintenance' => 'apple-status-pill--maintenance',
                        default => 'apple-status-pill--reserved'
                    };
                @endphp
                <div class="room-card-mob">
                    <div class="room-card-mob__top">
                        <span class="room-num-badge">P.{{ $room->room_number }}</span>
                        <span class="apple-status-pill {{ $statusClass }}">
                            <span class="apple-dot"></span>
                            <span>{{ $statusLabel }}</span>
                        </span>
                    </div>
                    <div class="room-card-mob__body">
                        <div class="room-type-meta">
                            <span class="room-type-name">{{ $room->roomType?->type_name ?? 'Chưa gán hạng' }}</span>
                            <span class="room-type-sub">Tối đa {{ $room->roomType?->max_guests ?? 2 }} khách</span>
                        </div>
                    </div>
                    <button class="btn-edit-room" type="button" data-bs-toggle="modal" data-bs-target="#editRoomModal-{{ $room->id }}">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i> <span>Chỉnh sửa phòng</span>
                    </button>
                </div>
            @empty
                <div class="room-empty">Tầng {{ $selectedFloor }} chưa có phòng.</div>
            @endforelse
        </div>
    </section>

    @foreach($rooms as $room)
        <div class="modal fade room-modal" id="editRoomModal-{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalTitle-{{ $room->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <form action="{{ route('admin.rooms.update', $room) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        <div class="room-modal__head">
                            <div class="room-modal__lights macos-traffic-lights" aria-hidden="true"><span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span></div>
                            <h2 id="editRoomModalTitle-{{ $room->id }}">Chỉnh sửa phòng {{ $room->room_number }}</h2>
                            <button class="room-modal__close" type="button" data-bs-dismiss="modal" aria-label="Đóng popup chỉnh sửa phòng"><span aria-hidden="true">×</span></button>
                        </div>
                        <div class="room-modal__body">
                            <div class="room-form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                                <div class="room-field">
                                    <label for="room-number-{{ $room->id }}">Số phòng <span class="text-danger">*</span></label>
                                    <input id="room-number-{{ $room->id }}" name="room_number" inputmode="numeric" pattern="[0-9]*" maxlength="4" data-digits-only value="{{ old('room_number', $room->room_number) }}" data-room-number-input required>
                                    @error('room_number', 'editRoom'.$room->id)<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                                </div>
                                <div class="room-field">
                                    <label for="room-floor-{{ $room->id }}">Tầng <span class="text-danger">*</span></label>
                                    <select id="room-floor-{{ $room->id }}" name="floor" data-room-floor-select required>
                                        @for($floor = 1; $floor <= 10; $floor++)<option value="{{ $floor }}" @selected((int) old('floor', $room->floor) === $floor)>Tầng {{ $floor }}</option>@endfor
                                    </select>
                                    @error('floor', 'editRoom'.$room->id)<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                                </div>
                                <div class="room-field" style="grid-column:1/-1">
                                    <label for="room-type-{{ $room->id }}">Hạng phòng / nâng hạng <span class="text-danger">*</span></label>
                                    <select id="room-type-{{ $room->id }}" name="room_type_id" required>
                                        @foreach($roomTypes as $type)<option value="{{ $type->id }}" @selected((int) old('room_type_id', $room->room_type_id) === $type->id)>{{ $type->type_name }}</option>@endforeach
                                    </select>
                                    @error('room_type_id', 'editRoom'.$room->id)<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="room-field mt-4" data-room-gallery-upload data-current-count="{{ count($room->galleryImagePaths()) }}">
                                <label for="room-images-{{ $room->id }}">Ảnh riêng của phòng</label>
                                <div class="room-gallery" aria-label="Ảnh hiện tại và ảnh xem trước">
                                    @forelse($room->galleryImageUrls() as $imageUrl)
                                        <img class="room-gallery__image" src="{{ $imageUrl }}" alt="Ảnh phòng {{ $room->room_number }}" loading="lazy">
                                    @empty
                                        <div class="room-gallery__empty" data-room-gallery-empty>Chưa có ảnh riêng · đang dùng ảnh hạng phòng</div>
                                    @endforelse
                                    <div class="room-gallery__preview-list" data-room-gallery-preview></div>
                                </div>
                                <input id="room-images-{{ $room->id }}" class="form-control mt-2" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-room-gallery-input>
                                <small class="room-gallery__hint">Tối đa 3 ảnh · 5 MB/ảnh</small>
                                <span class="room-gallery__error" data-room-gallery-client-error hidden role="alert"></span>
                                @error('images', 'editRoom'.$room->id)<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                                @error('images.*', 'editRoom'.$room->id)<span class="room-field-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <p class="room-hint mt-3 mb-0">Số phòng, tầng và hạng phòng không thể đổi khi có đặt chỗ đang hoạt động. Ảnh riêng không thay ảnh đại diện của hạng phòng.</p>
                        </div>
                        <div class="room-modal__footer">
                            <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Hủy</button>
                            <button class="room-submit" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> Lưu phòng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade room-modal" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.rooms.store') }}" method="POST">
                    @csrf
                    <div class="room-modal__head">
                        <div class="room-modal__lights macos-traffic-lights" aria-hidden="true"><span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span></div>
                        <h2 id="addRoomModalTitle">Thêm phòng mới</h2>
                        <button class="room-modal__close" type="button" data-bs-dismiss="modal" aria-label="Đóng popup thêm phòng"><span aria-hidden="true">×</span></button>
                    </div>
                    <div class="room-modal__body">
                        <div class="room-form-grid" style="grid-template-columns:1fr 1fr">
                            <div class="room-field">
                                <label for="room-number-new">Số phòng</label>
                                <input id="room-number-new" name="room_number" inputmode="numeric" pattern="[0-9]*" maxlength="4" data-digits-only value="{{ old('room_number') }}" data-room-number-input required @if($errors->getBag('addRoom')->has('room_number')) aria-invalid="true" aria-describedby="new-room-number-error" @endif>
                                @error('room_number', 'addRoom')<span class="room-field-error" id="new-room-number-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="room-field">
                                <label for="room-type-new">Hạng phòng</label>
                                <select id="room-type-new" name="room_type_id" required @if($errors->getBag('addRoom')->has('room_type_id')) aria-invalid="true" aria-describedby="new-room-type-error" @endif>
                                    @foreach($roomTypes as $type)<option value="{{ $type->id }}" @selected((string) old('room_type_id') === (string) $type->id)>{{ $type->type_name }}</option>@endforeach
                                </select>
                                @error('room_type_id', 'addRoom')<span class="room-field-error" id="new-room-type-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                            <div class="room-field">
                                <label for="room-floor-new">Tầng</label>
                                <select id="room-floor-new" name="floor" data-room-floor-select required @if($errors->getBag('addRoom')->has('floor')) aria-invalid="true" aria-describedby="new-room-floor-error" @endif>
                                    @for($floor = 1; $floor <= 10; $floor++)<option value="{{ $floor }}" @selected((int) old('floor', $selectedFloor) === $floor)>Tầng {{ $floor }}</option>@endfor
                                </select>
                                @error('floor', 'addRoom')<span class="room-field-error" id="new-room-floor-error" role="alert">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="room-modal__footer">
                        <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Hủy</button>
                        <button class="room-submit" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Thêm phòng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Section 2: Hạng phòng và tiện nghi --}}
    <section class="room-panel" id="admin-room-catalog">
        <header class="room-panel__head">
            <div class="room-panel__head-info">
                <h2>Hạng phòng &amp; Bảng giá niêm yết</h2>
                <p>Cấu hình bảng giá niêm yết, sức chứa tối đa và danh mục tiện nghi theo từng hạng</p>
            </div>
            <span class="room-panel-badge"><i class="bi bi-collection"></i> {{ $roomTypes->count() }} hạng tiêu chuẩn</span>
        </header>
        <div class="room-type-grid">
            @foreach($roomTypes as $type)
                <article class="room-type-card-bento">
                    <div class="room-type-card-bento__thumb">
                        <img src="{{ $type->image_url }}" alt="{{ $type->type_name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}';">
                        <span class="thumb-overlay-badge thumb-overlay-badge--left">
                            <i class="bi bi-door-closed"></i> {{ $type->rooms_count }} phòng
                        </span>
                        <span class="thumb-overlay-badge thumb-overlay-badge--right">
                            <i class="bi bi-people"></i> Tối đa {{ $type->max_guests }} khách
                        </span>
                    </div>
                    <div class="room-type-card-bento__body">
                        <div class="room-type-card-bento__header">
                            <h3 class="room-type-card-bento__name">{{ $type->type_name }}</h3>
                            <div class="room-type-card-bento__price">
                                <span class="price-num">{{ number_format((float) $type->price, 0, ',', '.') }} đ</span>
                                <span class="price-unit">/đêm</span>
                            </div>
                        </div>
                        <div class="room-type-specs">
                            <span><i class="bi bi-person"></i> {{ $type->max_adults }} người lớn</span>
                            @if($type->max_children)
                                <span>· <i class="bi bi-emoji-smile"></i> {{ $type->max_children }} trẻ em</span>
                            @endif
                        </div>
                        <div class="room-type-amenities-wrap" aria-label="Tiện nghi nổi bật">
                            @forelse($type->amenities as $amenity)
                                <span class="room-type-amenity-chip"><i class="bi bi-check2"></i> {{ $amenity->amenity_name }}</span>
                            @empty
                                <span class="room-type-amenity-chip is-empty">Chưa có tiện nghi</span>
                            @endforelse
                        </div>
                        <div class="room-type-card-bento__actions">
                            <button class="btn-edit-type" type="button" data-bs-toggle="modal" data-bs-target="#editRoomTypeModal-{{ $type->id }}">
                                <i class="bi bi-sliders" aria-hidden="true"></i> <span>Chỉnh sửa hạng phòng</span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="modal fade room-modal" id="addRoomTypeModal" tabindex="-1" aria-labelledby="addRoomTypeModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                    <form action="{{ route('admin.room-types.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="room-modal__head">
                        <div class="room-modal__lights macos-traffic-lights" aria-hidden="true"><span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span></div>
                        <h2 id="addRoomTypeModalTitle">Thêm hạng phòng</h2>
                        <button class="room-modal__close" type="button" data-bs-dismiss="modal" aria-label="Đóng popup thêm hạng phòng"><span aria-hidden="true">×</span></button>
                    </div>
                    <div class="room-modal__body">
                        @include('admin.rooms.partials.room-type-fields', ['type' => null, 'fieldPrefix' => 'new-room-type', 'errorBag' => 'addRoomType', 'amenities' => $amenities])
                    </div>
                    <div class="room-modal__footer">
                        <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Hủy</button>
                        <button class="room-submit" type="submit"><i class="bi bi-tags" aria-hidden="true"></i> Thêm hạng phòng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($roomTypes as $type)
        <div class="modal fade room-modal" id="editRoomTypeModal-{{ $type->id }}" tabindex="-1" aria-labelledby="editRoomTypeModalTitle-{{ $type->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <form action="{{ route('admin.room-types.update', $type) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        <div class="room-modal__head">
                            <div class="room-modal__lights macos-traffic-lights" aria-hidden="true"><span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span></div>
                            <h2 id="editRoomTypeModalTitle-{{ $type->id }}">{{ $type->type_name }}</h2>
                            <button class="room-modal__close" type="button" data-bs-dismiss="modal" aria-label="Đóng popup sửa hạng phòng"><span aria-hidden="true">×</span></button>
                        </div>
                        <div class="room-modal__body">
                            @include('admin.rooms.partials.room-type-fields', ['type' => $type, 'fieldPrefix' => 'room-type-'.$type->id, 'errorBag' => 'editRoomType'.$type->id, 'amenities' => $amenities])
                        </div>
                        <div class="room-modal__footer">
                            <button class="btn btn-light border" type="button" data-bs-dismiss="modal">Hủy</button>
                            <button class="room-submit" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> Lưu hạng phòng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const savedScrollTop = sessionStorage.getItem('admin-room-scroll-top');
    if (savedScrollTop !== null) {
        sessionStorage.removeItem('admin-room-scroll-top');
        window.requestAnimationFrame(() => window.scrollTo(0, Number(savedScrollTop) || 0));
    }

    let adminRoomSyncInProgress = false;
    const syncAdminRoomDataWhenSafe = async () => {
        const activeControl = document.activeElement?.matches('input, select, textarea, button, a, [contenteditable="true"]');
        if (adminRoomSyncInProgress || document.hidden || activeControl || document.querySelector('.room-modal.show')) return;
        adminRoomSyncInProgress = true;

        try {
            const response = await fetch(window.location.href, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;

            const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const selectors = ['#admin-room-inventory', '#admin-room-catalog'];
            const changed = selectors.some((selector) => {
                const current = document.querySelector(selector);
                const next = nextDocument.querySelector(selector);
                return current && next && current.outerHTML !== next.outerHTML;
            });

            if (changed) {
                sessionStorage.setItem('admin-room-scroll-top', String(window.scrollY));
                window.location.reload();
            }
        } catch (error) {
            console.error('Room data sync failed:', error);
        } finally {
            adminRoomSyncInProgress = false;
        }
    };

    window.setInterval(syncAdminRoomDataWhenSafe, 15000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) syncAdminRoomDataWhenSafe();
    });

    document.querySelectorAll('[data-room-floor-select]').forEach((floorSelect) => {
        const numberInput = floorSelect.closest('form')?.querySelector('[data-room-number-input]');
        if (!numberInput) return;

        const updateRoomNumberHint = () => {
            const first = Number(floorSelect.value) * 100 + 1;
            numberInput.placeholder = `${first}–${first + 4}`;
        };
        floorSelect.addEventListener('change', updateRoomNumberHint);
        updateRoomNumberHint();
    });

    document.querySelectorAll('[data-room-image-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const wrapper = input.closest('[data-room-image-upload]');
            const preview = wrapper?.querySelector('[data-room-image-preview]');
            const empty = wrapper?.querySelector('[data-room-image-empty]');
            if (!preview || !empty) return;

            if (preview.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
            const file = input.files?.[0];
            if (file) {
                const previewUrl = URL.createObjectURL(file);
                preview.dataset.previewUrl = previewUrl;
                preview.src = previewUrl;
                preview.hidden = false;
                empty.hidden = true;
                return;
            }

            delete preview.dataset.previewUrl;
            const originalSrc = preview.dataset.originalSrc || '';
            if (originalSrc) preview.src = originalSrc;
            else preview.removeAttribute('src');
            preview.hidden = !originalSrc;
            empty.hidden = !!originalSrc;
        });
    });

    document.querySelectorAll('.room-modal').forEach((modal) => {
        const galleryInput = modal.querySelector('[data-room-gallery-input]');
        const galleryPreview = modal.querySelector('[data-room-gallery-preview]');
        const galleryEmpty = modal.querySelector('[data-room-gallery-empty]');
        const galleryError = modal.querySelector('[data-room-gallery-client-error]');
        const galleryWrapper = modal.querySelector('[data-room-gallery-upload]');
        const revokeGalleryPreviews = () => {
            galleryPreview?.querySelectorAll('img[data-preview-url]').forEach((img) => URL.revokeObjectURL(img.dataset.previewUrl));
            if (galleryPreview) galleryPreview.replaceChildren();
        };

        galleryInput?.addEventListener('change', () => {
            revokeGalleryPreviews();
            if (galleryError) { galleryError.hidden = true; galleryError.textContent = ''; }

            const files = [...(galleryInput.files || [])];
            const remaining = Math.max(0, 3 - Number(galleryWrapper?.dataset.currentCount || 0));
            if (files.length > remaining) {
                galleryInput.value = '';
                if (galleryError) {
                    galleryError.textContent = `Phòng này chỉ còn nhận tối đa ${remaining} ảnh.`;
                    galleryError.hidden = false;
                }
                if (galleryEmpty) galleryEmpty.hidden = false;
                return;
            }

            files.forEach((file) => {
                const item = document.createElement('div');
                item.className = 'room-gallery__preview-item';
                const img = document.createElement('img');
                img.className = 'room-gallery__image';
                img.alt = `Xem trước ${file.name}`;
                img.dataset.previewUrl = URL.createObjectURL(file);
                img.src = img.dataset.previewUrl;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'room-gallery__remove';
                remove.textContent = '×';
                remove.title = 'Bỏ ảnh này';
                remove.setAttribute('aria-label', `Bỏ ảnh ${file.name} khỏi danh sách tải lên`);
                remove.addEventListener('click', () => {
                    const selected = Array.from(galleryInput.files || []);
                    const index = Array.from(galleryPreview.children).indexOf(item);
                    if (index < 0 || index >= selected.length) return;

                    selected.splice(index, 1);
                    const nextFiles = new DataTransfer();
                    selected.forEach((selectedFile) => nextFiles.items.add(selectedFile));
                    galleryInput.files = nextFiles.files;
                    URL.revokeObjectURL(img.dataset.previewUrl);
                    item.remove();
                    if (!selected.length && galleryEmpty) galleryEmpty.hidden = false;
                    if (galleryError) { galleryError.textContent = ''; galleryError.hidden = true; }
                });
                item.append(img, remove);
                galleryPreview?.appendChild(item);
            });
            if (files.length && galleryEmpty) galleryEmpty.hidden = true;
        });

        modal.addEventListener('hidden.bs.modal', () => {
            revokeGalleryPreviews();
            if (galleryInput) galleryInput.value = '';
            if (galleryError) { galleryError.hidden = true; galleryError.textContent = ''; }
            if (galleryEmpty) galleryEmpty.hidden = false;
            modal.querySelectorAll('[data-room-image-input]').forEach((input) => {
                const wrapper = input.closest('[data-room-image-upload]');
                const preview = wrapper?.querySelector('[data-room-image-preview]');
                const empty = wrapper?.querySelector('[data-room-image-empty]');
                if (!preview || !empty) return;

                if (preview.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
                delete preview.dataset.previewUrl;
                input.value = '';
                const originalSrc = preview.dataset.originalSrc || '';
                if (originalSrc) preview.src = originalSrc;
                else preview.removeAttribute('src');
                preview.hidden = !originalSrc;
                empty.hidden = !!originalSrc;
            });
        });
    });

    @if($errors->getBag('addRoom')->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addRoomModal')).show();
    @endif
    @if($errors->getBag('addRoomType')->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addRoomTypeModal')).show();
    @endif
    @foreach($roomTypes as $type)
        @if($errors->getBag('editRoomType'.$type->id)->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editRoomTypeModal-{{ $type->id }}')).show();
        @endif
    @endforeach
    @foreach($rooms as $room)
        @if($errors->getBag('editRoom'.$room->id)->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editRoomModal-{{ $room->id }}')).show();
        @endif
    @endforeach
});
</script>
@endpush
