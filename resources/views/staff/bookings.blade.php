@extends('layouts.dashboard')
@section('title', 'Sơ đồ phòng · Rosaliza Hotel')
@section('page-title', 'Sơ đồ phòng')

@section('content')
@php
    $allFloors = array_keys($floors);
    sort($allFloors, SORT_NUMERIC);

    $uniqueTypes = [];
    $uniqueCapacities = [];
    $counts = [
        'available' => 0,
        'occupied' => 0,
        'cleaning' => 0,
        'booked' => 0,
    ];

    foreach ($floors as $floor => $rooms) {
        foreach ($rooms as $room) {
            $uniqueTypes[$room['room_type_id']] = $room['type_name'];
            $uniqueCapacities[] = $room['max_guests'];
            
            $uiStatus = match(true) {
                                $room['status'] === 'available' && (int) $room['has_today_booking'] > 0 => 'booked',
                                default => $room['status'],
                            };
            if (isset($counts[$uiStatus])) {
                $counts[$uiStatus]++;
            }
            
        }
    }
    asort($uniqueTypes);
    $uniqueCapacities = array_unique($uniqueCapacities);
    sort($uniqueCapacities);
@endphp

<!-- html5-qrcode library for camera scanning -->
<style>
    /* =========================================================================
       STAFF OPERATIONS CONSOLE — MACOS CUPERTINO & QUIET LUXURY
       ========================================================================= */

    /* Quick Operations & Toolbar (Two-Tier Layout) */
    .operations-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .operations-tier-1 {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .operations-search-box {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1 1 320px;
        min-height: 44px;
        padding: 0 16px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 999px;
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .operations-search-box:focus-within {
        border-color: #0071e3;
        box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.16);
    }
    .operations-search-box i {
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .operations-search-box input {
        flex: 1;
        min-width: 0;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        color: #0f172a !important;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: none !important;
        padding: 0;
    }
    .operations-search-box input::placeholder {
        color: #94a3b8;
    }

    .operations-quick-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* macOS Cupertino Action Pills */
    .action-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 18px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
    }
    .action-pill-btn.btn-obsidian {
        background: #070709;
        color: #ffffff !important;
        border: 1px solid #070709;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .action-pill-btn.btn-obsidian:hover {
        background: #1e1e24;
        border-color: #1e1e24;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
    }
    .action-pill-btn.btn-white {
        background: #ffffff;
        color: #0f172a !important;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .action-pill-btn.btn-white:hover {
        background: #f8fafc;
        border-color: rgba(0, 0, 0, 0.14);
        transform: translateY(-1px);
    }
    .action-pill-btn .action-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: #ffffff;
        font-size: 0.68rem;
        font-weight: 700;
    }

    /* Tier 2: Status Pills & Filters Strip */
    .operations-tier-2 {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 8px 12px;
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.01);
    }

    .status-segmented-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .legend-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        transition: all 0.16s ease;
    }
    .legend-badge:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .legend-badge.active-filter {
        background: #070709 !important;
        color: #ffffff !important;
        border-color: #070709 !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
    }
    .legend-badge .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .legend-badge.active-filter .status-dot {
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.4);
    }
    .legend-badge strong {
        font-variant-numeric: tabular-nums;
        font-size: 0.76rem;
        opacity: 0.85;
    }
    .dot-available { background: #10b981; }
    .dot-occupied { background: #0071e3; }
    .dot-cleaning { background: #f59e0b; }

    .filter-dropdowns-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filter-select {
        min-height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #ffffff;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 500;
        outline: none;
        cursor: pointer;
        transition: border-color 0.15s;
    }
    .filter-select:focus {
        border-color: #0071e3;
    }
    .btn-reset-filters {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #ffffff;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-reset-filters:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    /* Floor Section & Scrollable Container */
    .room-grid-container-scrollable {
        max-height: calc(100vh - 230px);
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-right: 6px;
    }
    .floor-section {
        margin-bottom: 24px;
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.01);
    }
    .floor-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .floor-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .floor-title i {
        color: #0071e3;
        font-size: 1rem;
    }
    .floor-counter {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 999px;
    }

    /* =========================================================================
       MACOS CUPERTINO ROOM CARDS
       ========================================================================= */
    .rooms-five-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        width: 100%;
        margin-bottom: 8px;
    }
    @media (max-width: 1399.98px) {
        .rooms-five-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 991.98px) {
        .rooms-five-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575.98px) {
        .rooms-five-grid {
            grid-template-columns: 1fr;
        }
    }
    .room-card-wrapper {
        margin-bottom: 14px;
        width: 100%;
        min-width: 0;
    }
    .room-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 16px;
        padding: 14px 16px;
        min-height: 128px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
    }
    .room-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
        border-color: rgba(0, 0, 0, 0.14);
    }
    .room-card.selected {
        outline: none !important;
        border-color: #0071e3 !important;
        box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.2) !important;
        transform: translateY(-2px);
    }

    /* Card Header */
    .room-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .room-number {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
        margin: 0;
        line-height: 1.1;
    }

    /* High-contrast Cupertino Status Badges */
    .room-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }
    .badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    
    /* Status-specific badge colors */
    .badge-status-available {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.22);
    }
    .badge-status-available .badge-dot { background: #10b981; }

    .room-cleaning-request-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        background: #fff7ed;
        color: #9a3412;
        font-size: .68rem;
        font-weight: 650;
    }

    .badge-status-occupied {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid rgba(59, 130, 246, 0.22);
    }
    .badge-status-occupied .badge-dot { background: #0071e3; }

    .badge-status-booked {
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid rgba(139, 92, 246, 0.22);
    }
    .badge-status-booked .badge-dot { background: #8b5cf6; }

    .badge-status-cleaning {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, 0.22);
    }
    .badge-status-cleaning .badge-dot { background: #f59e0b; }

    .badge-status-booked {
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid rgba(139, 92, 246, 0.22);
    }
    .badge-status-booked .badge-dot { background: #8b5cf6; }

    .badge-status-overdue {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.22);
    }
    .badge-status-overdue .badge-dot { background: #ef4444; }

    .badge-status-maintenance {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid rgba(100, 116, 139, 0.22);
    }
    .badge-status-maintenance .badge-dot { background: #64748b; }

    /* Card Mid: Type & Capacity */
    .room-card-mid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 6px 0 8px;
    }
    .room-type-name {
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 65%;
    }
    .room-capacity {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 0;
    }

    /* Card Bottom: Guest chip or Ready chip */
    .room-card-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .room-guest-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.72rem;
        font-weight: 600;
        color: #1e40af;
        background: #eff6ff;
        padding: 3px 8px;
        border-radius: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .room-ready-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        font-weight: 500;
        color: #059669;
    }
    .room-booking-alert-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.68rem;
        font-weight: 600;
        color: #b91c1c;
        background: #fef2f2;
        padding: 2px 6px;
        border-radius: 6px;
    }

    /* QR Target corners & Camera Animation */
    .qr-scan-viewport-container #qr-reader video {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 14px;
    }
    .qr-target-box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 70%;
        height: 70%;
        pointer-events: none;
        box-sizing: border-box;
    }
    .qr-target-box .corner {
        position: absolute;
        width: 30px;
        height: 30px;
        border: 4px solid #0071e3;
        box-sizing: border-box;
    }
    .qr-target-box .corner.top-left { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 8px; }
    .qr-target-box .corner.top-right { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 8px; }
    .qr-target-box .corner.bottom-left { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 8px; }
    .qr-target-box .corner.bottom-right { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 8px; }
    .qr-scan-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        max-width: 380px;
        margin: 0 auto;
    }
    .qr-scan-action {
        min-height: 44px;
        border: 1px solid rgba(15, 23, 42, 0.18);
        border-radius: 12px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.84rem;
        font-weight: 650;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
    }
    .qr-scan-action:hover,
    .qr-scan-action:focus-visible {
        border-color: #0f172a;
        background: #f8fafc;
    }
    .qr-scan-action:focus-visible {
        outline: 3px solid rgba(37, 151, 208, 0.28);
        outline-offset: 2px;
    }
    .qr-scan-action[aria-pressed="true"] {
        background: #070709;
        border-color: #070709;
        color: #ffffff;
    }
    .qr-scan-action[hidden] {
        display: none;
    }
    .qr-scan-help {
        max-width: 380px;
        margin: 10px auto 0;
        color: #475569;
        font-size: 0.78rem;
        line-height: 1.5;
        text-align: left;
    }
    #qr-scan-status {
        min-height: 20px;
        color: #334155;
        font-size: 0.78rem;
        margin-top: 8px;
    }
    @media (max-width: 420px) {
        .qr-scan-actions {
            grid-template-columns: 1fr;
        }
    }

    /* Right Detail Drawer Styling */
    .staff-workspace .right-panel {
        background: #ffffff !important;
        border-left: 1px solid rgba(0, 0, 0, 0.08) !important;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.05) !important;
        padding: 24px;
    }
    .detail-img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 14px;
        margin-bottom: 20px;
        background: #f1f5f9;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-size: 0.85rem;
    }
    .detail-label {
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .detail-value {
        font-weight: 600;
        color: #0f172a;
        text-align: right;
    }
</style>

<!-- Main Center Column -->
<main class="main-content">
    <div class="operations-container" aria-label="Tìm và quản lý phòng">
        <!-- Tier 1: Search & Quick Action Buttons -->
        <div class="operations-tier-1">
            <div class="operations-search-box">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="search-input" placeholder="Tìm theo số phòng, tên khách, số điện thoại hoặc mã booking…" oninput="debounceFilterRooms()" aria-label="Tìm kiếm phòng và khách hàng">
            </div>

            <div class="operations-quick-actions">
                @if($permissions['bp_checkin_checkout'])
                    <button type="button" id="open-qr-scanner-btn" class="action-pill-btn btn-obsidian" onclick="openQRScannerModal()">
                        <i class="fa-solid fa-qrcode"></i> Quét mã QR
                    </button>
                @endif
                @if($permissions['bp_create_booking'] && !$permissions['bp_checkin_checkout'])
                    <button type="button" class="action-pill-btn btn-white" onclick="openWalkinCheckinModal('hold')">
                        <i class="fa-solid fa-user-plus text-primary"></i>
                        Tạo đặt phòng
                    </button>
                @endif
                @if($permissions['bp_create_booking'])
                    <button type="button" class="action-pill-btn btn-white" id="multi-select-btn" onclick="document.getElementById('multi-select-toggle').click()">
                        <input class="form-check-input me-1" type="checkbox" id="multi-select-toggle" onchange="toggleMultiSelectMode()" style="cursor: pointer;">
                        <span>Chọn nhiều</span>
                    </button>
                @endif
                @if($permissions['tn_refund'])
                    <a href="{{ route('staff.cancellations') }}" class="action-pill-btn btn-white">
                        <i class="fa-solid fa-arrow-rotate-left text-secondary"></i> Hủy phòng &amp; hoàn tiền
                        @if(($pendingRefunds ?? 0) > 0)
                            <span class="action-badge">{{ $pendingRefunds }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        <!-- Tier 2: Segmented Status Pills & Attribute Filters -->
        <div class="operations-tier-2">
            <div class="status-segmented-group" role="tablist" aria-label="Lọc trạng thái phòng">
                <button type="button" class="legend-badge active-filter" data-status-val="Tất cả" onclick="toggleLegendFilter(this)">
                    <span>Tất cả</span>
                    <strong>{{ array_sum(array_map('count', $floors)) }}</strong>
                </button>
                <button type="button" class="legend-badge" data-status-val="available" onclick="toggleLegendFilter(this)">
                    <span class="status-dot dot-available"></span>
                    <span>Phòng trống</span>
                    <strong id="count-available">{{ $counts['available'] }}</strong>
                </button>
                <button type="button" class="legend-badge" data-status-val="occupied" onclick="toggleLegendFilter(this)">
                    <span class="status-dot dot-occupied"></span>
                    <span>Đang ở</span>
                    <strong id="count-occupied">{{ $counts['occupied'] }}</strong>
                </button>
                <button type="button" class="legend-badge" data-status-val="booked" onclick="toggleLegendFilter(this)">
                    <span class="status-dot" style="background: #8b5cf6"></span>
                    <span>Đã đặt</span>
                    <strong id="count-booked">{{ $counts['booked'] ?? 0 }}</strong>
                </button>
                <button type="button" class="legend-badge" data-status-val="cleaning" onclick="toggleLegendFilter(this)">
                    <span class="status-dot dot-cleaning"></span>
                    <span>Dọn phòng</span>
                    <strong id="count-cleaning">{{ $counts['cleaning'] }}</strong>
                </button>
            </div>

            <div class="filter-dropdowns-group">
                <select id="filter-floor" class="filter-select" aria-label="Lọc theo tầng" onchange="filterRooms()">
                    <option value="Tất cả">Tất cả tầng</option>
                    @foreach ($allFloors as $fl)
                        <option value="{{ $fl }}">Tầng {{ $fl }}</option>
                    @endforeach
                </select>

                <select id="filter-type" class="filter-select" aria-label="Lọc theo hạng phòng" onchange="filterRooms()">
                    <option value="Tất cả">Mọi hạng phòng</option>
                    @foreach ($uniqueTypes as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <select id="filter-guests" class="filter-select" aria-label="Lọc theo sức chứa" onchange="filterRooms()">
                    <option value="Tất cả">Mọi sức chứa</option>
                    @foreach ($uniqueCapacities as $cap)
                        <option value="{{ $cap }}">{{ $cap }} người</option>
                    @endforeach
                    <option value="5+">5+ người</option>
                </select>

                <!-- Hidden select for programmatic binding with status buttons -->
                <select id="filter-status" class="d-none" onchange="filterRooms()">
                    <option value="Tất cả">Tất cả</option>
                    <option value="available">Trống</option>
                    <option value="occupied">Đang ở</option>
                    <option value="booked">Đã đặt</option>
                    <option value="cleaning">Dọn phòng</option>
                </select>

                <button type="button" class="btn-reset-filters" onclick="resetFilters()" title="Đặt lại toàn bộ bộ lọc">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Đặt lại</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Floors & Rooms Grid -->
    <div id="room-grid-container" class="room-grid-container-scrollable">
        @foreach ($floors as $floor => $rooms)
            <div class="floor-section" data-floor-num="{{ $floor }}">
                <div class="floor-header">
                    <h6 class="floor-title">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Tầng {{ $floor }}</span>
                    </h6>
                    <span class="floor-counter">{{ count($rooms) }} phòng</span>
                </div>
                <div class="rooms-five-grid">
                    @foreach ($rooms as $room)
                        @php
                            $uiStatus = $room['status'] === 'available' && (int) $room['has_today_booking'] > 0 ? 'booked' : $room['status'];
                            [$statusText, $icon, $badgeClass] = match ($uiStatus) {
                                'occupied' => ['Đang ở', 'fa-user-check', 'badge-status-occupied'],
                                'cleaning' => ['Đang dọn', 'fa-broom', 'badge-status-cleaning'],
                                'maintenance' => ['Bảo trì', 'fa-screwdriver-wrench', 'badge-status-maintenance'],
                                'booked' => ['Đã đặt', 'fa-calendar-check', 'badge-status-booked'],
                                'overdue' => ['Quá hạn', 'fa-triangle-exclamation', 'badge-status-overdue'],
                                default => ['Trống', 'fa-door-open', 'badge-status-available'],
                            };
                            
                            $room['ui_status'] = $uiStatus;
                            $room['status_text'] = $statusText;
                        @endphp
                        <div class="room-card-wrapper">
                            <div class="room-card" role="button" tabindex="0" aria-label="Phòng {{ $room['room_number'] }}" onkeydown="if(event.key === 'Enter' || event.key === ' '){event.preventDefault();selectRoom(this)}"
                                 data-floor="{{ $room['floor'] }}"
                                 data-type="{{ $room['room_type_id'] }}"
                                 data-guests="{{ $room['max_guests'] }}"
                                 data-status="{{ $uiStatus }}"
                                 data-needs-cleaning="{{ !empty($room['needs_cleaning']) ? '1' : '0' }}"
                                 data-has-booking="{{ $room['has_today_booking'] ? '1' : '0' }}"
                                 data-search="{{ htmlspecialchars(strtolower($room['room_number'] . ' ' . $room['type_name'] . ' ' . ($room['customer_name'] ?? '') . ' ' . ($room['customer_phone'] ?? ''))) }}"
                                 id="room-card-{{ $room['id'] }}"
                                 data-room="{{ json_encode($room) }}"
                                 onclick="selectRoom(this)">
                                
                                <div class="room-card-top">
                                    <h5 class="room-number">{{ htmlspecialchars($room['room_number']) }}</h5>
                                    <span class="room-status-badge {{ $badgeClass }}">
                                        <span class="badge-dot"></span>
                                        <span>{{ htmlspecialchars($statusText) }}</span>
                                    </span>
                                </div>

                                <div class="room-card-mid">
                                    <span class="room-type-name" title="{{ $room['type_name'] }}">{{ $room['type_name'] }}</span>
                                    <span class="room-capacity">
                                        <i class="fa-solid fa-user-group"></i> {{ $room['max_guests'] }}
                                    </span>
                                </div>

                                <div class="room-card-bottom">
                                    @if (!empty($room['customer_name']))
                                        <div class="room-guest-chip" title="Khách: {{ $room['customer_name'] }}">
                                            <i class="fa-solid fa-user"></i>
                                            <span>{{ $room['customer_name'] }}</span>
                                        </div>
                                    @elseif (!empty($room['has_today_booking']))
                                        <div class="room-booking-alert-badge" title="Đã có khách đặt hôm nay">
                                            <i class="fa-regular fa-calendar-check"></i>
                                            <span>Đã đặt hôm nay</span>
                                        </div>
                                    @else
                                        <div class="room-ready-chip">
                                            <i class="fa-solid fa-check"></i>
                                            <span>Sẵn sàng</span>
                                        </div>
                                    @endif
                                    @if(!empty($room['needs_cleaning']) && $uiStatus !== 'cleaning')
                                        <span class="room-cleaning-request-tag"><i class="fa-solid fa-bell-concierge"></i>Khách yêu cầu dọn</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</main>

<!-- Right Sidebar (Details & Room Operations) -->
<aside class="right-panel shadow-sm" id="room-detail-panel" style="display: none; flex-direction: column; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch;">
    <!-- macOS Inspector Header -->
    <div class="right-panel-header d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom" style="border-color: rgba(0,0,0,0.06) !important;">
        <div class="window-controls window-controls--panel d-flex align-items-center gap-1.5" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
            <span class="ctrl-dot ctrl-red"></span>
            <span class="ctrl-dot ctrl-yellow"></span>
            <span class="ctrl-dot ctrl-green"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold m-0" id="detail-title" style="font-size: 1.05rem; color: #0f172a; letter-spacing: -0.01em;">Thông tin phòng</h5>
            <div id="detail-status-badge">
                <span class="badge rounded-pill px-2.5 py-1">---</span>
            </div>
        </div>
        <button type="button" class="btn-close m-0" onclick="closeDetailPanel()" aria-label="Đóng thông tin phòng" style="font-size: 0.75rem;"></button>
    </div>
    
    <!-- Single room details -->
    <div id="single-room-container" style="display: block; width: 100%;">
        <div class="position-relative overflow-hidden mb-3" style="border-radius: 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.06); background: #f8fafc;">
            <img src="" class="detail-img w-100" id="detail-img" alt="Room Image" style="height: 145px; object-fit: cover; display: block;" onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}';">
        </div>

        <!-- Room Specs Card -->
        <div class="card border-0 mb-3" id="room-specs-card" style="border-radius: 14px; background: #f8fafc; border: 1px solid rgba(0,0,0,0.06) !important; padding: 6px 14px;">
            <div class="detail-row py-2 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                <div class="detail-label text-muted" style="font-size: 0.8rem;"><i class="bi bi-door-closed me-1.5 text-secondary"></i> Loại phòng</div>
                <div class="detail-value fw-bold text-dark" id="detail-type" style="font-size: 0.84rem;">---</div>
            </div>
            <div class="detail-row py-2 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                <div class="detail-label text-muted" style="font-size: 0.8rem;"><i class="bi bi-people me-1.5 text-secondary"></i> Sức chứa</div>
                <div class="detail-value fw-semibold text-dark" id="detail-capacity" style="font-size: 0.84rem;">--- người</div>
            </div>
            <div class="detail-row py-2 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                <div class="detail-label text-muted" style="font-size: 0.8rem;"><i class="bi bi-tag me-1.5 text-secondary"></i> Giá phòng</div>
                <div class="detail-value fw-bold" id="detail-price" style="font-size: 0.92rem; color: #0071e3;">--- đ / đêm</div>
            </div>
            <div class="detail-row py-2 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                <div class="detail-label text-muted" style="font-size: 0.8rem;"><i class="bi bi-stars me-1.5 text-secondary"></i> Tiện ích</div>
                <div class="detail-value text-muted text-end text-truncate" id="detail-amenities" style="font-size: 0.78rem; max-width: 190px;">---</div>
            </div>
            <div class="detail-row py-2 d-flex justify-content-between align-items-center" id="detail-booking-row" style="display: none;">
                <div class="detail-label text-danger fw-semibold" style="font-size: 0.8rem;"><i class="bi bi-calendar-event me-1.5"></i> Lịch hôm nay</div>
                <div class="detail-value text-danger fw-bold" id="detail-booking-text" style="font-size: 0.8rem;">ĐÃ ĐẶT</div>
            </div>
        </div>

        <!-- Occupied Guest Booking Info -->
        <div id="occupied-booking-info" style="display: none;">
            <div class="card border-0 mb-3" style="background: #f8fafc; border: 1px solid rgba(0, 113, 227, 0.14) !important; border-radius: 14px; padding: 12px 14px;">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-2" style="border-bottom: 1px solid rgba(0, 113, 227, 0.08);">
                    <span class="fw-bold" style="font-size: 0.84rem; color: #0071e3;"><i class="bi bi-person-badge me-1"></i> Khách đang lưu trú</span>
                    <span class="badge" id="occupied-booking-id" style="background: #e0f2fe; color: #0284c7; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 6px;">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Khách hàng</span>
                    <strong class="text-dark" id="occupied-customer-name">---</strong>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Điện thoại</span>
                    <span class="text-dark fw-medium" id="occupied-customer-phone">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Email</span>
                    <span class="text-muted text-truncate" id="occupied-customer-email" style="max-width: 170px;">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Kỳ nghỉ</span>
                    <span class="text-dark fw-medium" style="font-size: 0.76rem;"><span id="occupied-checkin">---</span> &rarr; <span class="text-danger fw-bold" id="occupied-checkout">---</span></span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Số khách</span>
                    <span class="text-dark" id="occupied-guests">---</span>
                </div>
                <div class="detail-row pt-2 mt-1 d-flex justify-content-between align-items-center" style="border-top: 1px dashed rgba(0,0,0,0.08);">
                    <span class="fw-bold text-dark" style="font-size: 0.82rem;">Tổng tiền:</span>
                    <strong class="text-primary fs-6 fw-bold" id="occupied-total-price">---</strong>
                </div>
            </div>
        </div>

        <!-- Today Booking Info -->
        <div id="today-booking-info" style="display: none;">
            <div class="card border-0 mb-3" style="background: #fffdf5; border: 1px solid rgba(245, 158, 11, 0.22) !important; border-radius: 14px; padding: 12px 14px;">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-2" style="border-bottom: 1px solid rgba(245, 158, 11, 0.1);">
                    <span class="fw-bold text-warning-emphasis" style="font-size: 0.84rem;"><i class="bi bi-calendar-check me-1 text-warning"></i> Lịch đặt hôm nay</span>
                    <span class="badge" id="today-booking-id" style="background: #fef3c7; color: #92400e; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 6px;">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Khách đặt</span>
                    <strong class="text-dark" id="today-customer-name">---</strong>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Điện thoại</span>
                    <span class="text-dark fw-medium" id="today-customer-phone">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Email</span>
                    <span class="text-muted text-truncate" id="today-customer-email" style="max-width: 170px;">---</span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Kỳ nghỉ</span>
                    <span class="text-dark fw-medium" style="font-size: 0.76rem;"><span id="today-checkin">---</span> &rarr; <span class="text-danger fw-bold" id="today-checkout">---</span></span>
                </div>
                <div class="detail-row py-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                    <span class="text-muted">Số khách</span>
                    <span class="text-dark" id="today-guests">---</span>
                </div>
                <div class="detail-row pt-2 mt-1 d-flex justify-content-between align-items-center" style="border-top: 1px dashed rgba(0,0,0,0.08);">
                    <span class="fw-bold text-dark" style="font-size: 0.82rem;">Tổng tiền:</span>
                    <strong class="text-primary fs-6 fw-bold" id="today-total-price">---</strong>
                </div>
            </div>
        </div>

        <!-- Operational Business Buttons -->
        @if($permissions['bp_checkin_checkout'] || $permissions['bp_extend_stay'] || $permissions['bp_clean_status'])
        <div class="mt-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">Nghiệp vụ phòng</span>
            </div>
            <div class="d-flex flex-column gap-2">
                @if($permissions['bp_checkin_checkout'])
                    <button class="staff-action-btn staff-action-primary btn-action" id="btn-action-occupied" onclick="handleSingleCheckin()">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Check-in (Nhận phòng)</span>
                    </button>
                @endif
                @if($permissions['bp_checkin_checkout'] && $permissions['tn_collect_payment'])
                    <button class="staff-action-btn staff-action-success btn-action" id="btn-action-available" onclick="triggerStatusUpdate('available')">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Trả phòng</span>
                    </button>
                @endif
                @if($permissions['bp_clean_status'])
                    <button class="staff-action-btn staff-action-done btn-action" id="btn-action-cleaning-done" onclick="triggerStatusUpdate('available')">
                        <i class="bi bi-check2-circle"></i>
                        <span>Đã dọn dẹp xong</span>
                    </button>
                    <button class="staff-action-btn staff-action-warning btn-action" id="btn-action-cleaning-request-done" onclick="completeCleaningRequest()">
                        <i class="bi bi-check2-circle"></i>
                        <span>Hoàn tất yêu cầu dọn</span>
                    </button>
                @endif
                @if($permissions['bp_extend_stay'])
                    <button class="staff-action-btn staff-action-secondary btn-action" id="btn-action-extend" onclick="openExtendStayModal()">
                        <i class="bi bi-calendar-plus"></i>
                        <span>Gia hạn lưu trú</span>
                    </button>
                @endif
            </div>
        </div>
        @endif
        <div style="height: 28px; flex-shrink: 0;" aria-hidden="true"></div>
    </div>

    <!-- Multi-room list for walk-in guest -->
    <div id="multi-room-container" style="display: none; flex-direction: column; width: 100%; height: 100%;">
        <div class="p-3 mb-3 d-flex align-items-center gap-2" style="background: #f0f7ff; border: 1px solid rgba(0,113,227,0.15); border-radius: 12px; font-size: 0.82rem; color: #0071e3;">
            <i class="bi bi-info-circle-fill flex-shrink-0 fs-5"></i>
            <span>Đang chọn nhiều phòng để tạo đặt phòng tại quầy.</span>
        </div>
        
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted small">Phòng đã chọn:</span>
            <span class="badge" style="background: #0f172a; color: #ffffff; border-radius: 999px; padding: 4px 10px; font-weight: 600;"><span id="multi-room-count">0</span> phòng</span>
        </div>
        <div id="multi-room-list" class="mb-4" style="max-height: 250px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; padding: 10px; background: #f8fafc; display: flex; flex-direction: column; gap: 8px;">
            <!-- Render via JS -->
        </div>

        <div class="d-flex flex-column gap-2 mt-auto">
            <button class="staff-action-btn staff-action-primary py-2.5" onclick="openWalkinCheckinModal('hold')">
                <i class="bi bi-calendar-plus me-2"></i>Tạo đặt phòng tại quầy
            </button>
        </div>
    </div>
</aside>

<!-- Right Sidebar Placeholder -->
<div class="right-panel empty-panel-flex text-muted shadow-sm" id="empty-detail-panel">
    <i class="fa-solid fa-bed text-secondary mb-3" style="font-size: 2.8rem; opacity: 0.4;"></i>
    <h6 class="fw-bold text-dark">Chưa chọn phòng</h6>
    <p class="text-center px-4 fs-7 text-muted" style="font-size: 0.8rem;">Hãy chọn một phòng bất kỳ trên sơ đồ để xem thông tin chi tiết và thao tác nghiệp vụ nhanh.</p>
</div>

<!-- Checkout Scope Modal -->
<div class="modal fade" id="checkoutScopeModal" tabindex="-1" aria-labelledby="checkoutScopeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="checkoutScopeModalLabel" style="color: #0f172a;">Phạm vi trả phòng</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng trả phòng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px;">
                    <div class="fw-bold fs-5 mb-1" id="checkout-room-label" style="color: #0f172a;">---</div>
                    <p class="mb-0" style="color: #64748b; font-size: 0.88rem;">Booking này bao gồm nhiều phòng lưu trú cùng đợt.</p>
                </div>
                <p class="small mb-0" style="color: #475569;">Vui lòng chọn phạm vi bạn muốn thực hiện thủ tục trả phòng:</p>
            </div>
            <div class="modal-footer px-4 py-3 d-flex flex-column gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-outline-primary w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" onclick="submitCheckout('room')" style="border-radius: 10px; font-size: 0.9rem;">
                    <i class="fa-solid fa-door-open"></i> Chỉ trả phòng này
                </button>
                <button type="button" class="btn btn-primary w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" onclick="submitCheckout('booking')" style="border-radius: 10px; font-size: 0.9rem;">
                    <i class="fa-solid fa-people-roof"></i> Trả toàn bộ booking
                </button>
                <button type="button" class="btn btn-light w-100 fw-medium py-2" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); color: #64748b; font-size: 0.88rem;">Hủy</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Payment Modal -->
<div class="modal fade" id="checkoutPaymentModal" tabindex="-1" aria-labelledby="checkoutPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="checkoutPaymentModalLabel" style="color: #0f172a;">Trả phòng &amp; Thanh toán</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-3 px-4">
                <!-- Stay & Bill Summary Card -->
                <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px;">
                    <div class="row g-2 mb-2 pb-2" style="border-bottom: 1px dashed rgba(0,0,0,0.08); font-size: 0.82rem;">
                        <div class="col-7">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Khách hàng</span>
                            <strong id="mo-customer" class="text-dark d-block text-truncate">---</strong>
                        </div>
                        <div class="col-5 text-end">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Phòng lưu trú</span>
                            <strong id="mo-room" class="text-dark d-block">---</strong>
                        </div>
                        <div class="col-7">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Thời gian nhận - trả</span>
                            <span class="text-dark fw-medium" style="font-size: 0.76rem;"><span id="mo-checkin">---</span> &rarr; <span id="mo-checkout">---</span></span>
                        </div>
                        <div class="col-5 text-end">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Đã đặt cọc / thu</span>
                            <strong class="text-success" id="mo-paid" style="font-size: 0.82rem;">0 đ</strong>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1 text-warning" id="mo-late-fee-row" style="font-size: 0.8rem;" hidden>
                        <span><i class="fa-solid fa-clock me-1"></i>Phụ thu trả muộn</span>
                        <strong id="mo-late-fee">0 đ</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Tổng cộng: <span id="mo-total" class="fw-semibold text-secondary">0 đ</span></span>
                            <span class="fw-bold text-dark" style="font-size: 0.86rem;">Còn lại cần thu:</span>
                        </div>
                        <div class="text-end">
                            <strong class="text-danger fs-5 fw-bold" id="mo-remaining" style="letter-spacing: -0.02em;">0 đ</strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Section -->
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fw-bold m-0" style="color: #0f172a; font-size: 0.84rem;">Phương thức thanh toán</label>
                        <span class="text-muted" style="font-size: 0.7rem;">Chọn cách thanh toán phần còn lại</span>
                    </div>
                    <div class="staff-payment-grid">
                        @foreach([
                            ['cash',    asset('images/payment-methods/cash.svg'),    'Tiền mặt',            'Thu trực tiếp tại quầy lễ tân', 'Tại quầy', '#f1f5f9', '#475569'],
                            ['vietqr',  asset('images/payment-methods/vietqr.png'),  'Chuyển khoản VietQR', 'Quét mã QR tất cả ngân hàng', 'Tự động', '#e0f2fe', '#0369a1'],
                            ['momo',    asset('images/payment-methods/momo.png'),    'Ví MoMo',             'Ứng dụng Ví MoMo', 'Ví điện tử', '#fdf2f8', '#be185d'],
                            ['zalopay', asset('images/payment-methods/zalopay.png'), 'Ví ZaloPay',          'Ứng dụng Ví ZaloPay', 'Ví điện tử', '#f0fdf4', '#15803d'],
                            ['vnpay',   asset('images/payment-methods/vnpay.png'),   'Cổng VNPay',          'Thẻ ATM nội địa & Quốc tế', 'Thẻ / Cổng', '#fef2f2', '#b91c1c'],
                        ] as [$val, $logo, $name, $desc, $badge, $badgeBg, $badgeFg])
                        <label class="payment-option-staff staff-pay-option" for="staff_method_{{ $val }}">
                            <input type="radio" class="staff-pay-radio" name="staff_payment_method" id="staff_method_{{ $val }}"
                                value="{{ $val }}" onchange="selectStaffMethod(this)">
                            <div class="staff-pay-logo-wrap">
                                <img src="{{ $logo }}" alt="{{ $name }}" class="staff-pay-logo-img" onerror="this.style.display='none'">
                            </div>
                            <div class="staff-pay-meta">
                                <div class="staff-pay-name">{{ $name }}</div>
                                <div class="staff-pay-desc">{{ $desc }}</div>
                            </div>
                            <span class="staff-pay-tag" style="background: {{ $badgeBg }}; color: {{ $badgeFg }};">{{ $badge }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer px-4 py-3 d-flex flex-column align-items-stretch gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <div class="form-check m-0" id="late-fee-waiver" hidden>
                    <input class="form-check-input" type="checkbox" id="waive-late-fee" onchange="toggleWaiveLateFee()">
                    <label class="form-check-label text-muted small" for="waive-late-fee" style="font-size: 0.78rem;">
                        Miễn phụ thu trả muộn cho khách
                    </label>
                </div>
                <div class="d-flex justify-content-end gap-2 w-100">
                    <button type="button" class="btn btn-light px-3 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.84rem;">Hủy</button>
                    <button type="button" class="btn btn-success px-3 fw-bold" onclick="submitCheckoutPayment()" style="border-radius: 10px; background: #10b981; border-color: #10b981; font-size: 0.84rem;">
                        <i class="fa-solid fa-check me-1"></i> Xác nhận thanh toán &amp; Trả phòng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Extend Stay Modal -->
<div class="modal fade" id="extendStayModal" tabindex="-1" aria-labelledby="extendStayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="extendStayModalLabel" style="color: #0f172a;">Gia hạn lưu trú</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px;">
                    <div class="text-muted small fw-semibold mb-1" style="color: #64748b;">Phòng cần gia hạn</div>
                    <div class="fw-bold fs-5" id="extend-room-label" style="color: #0f172a;">---</div>
                </div>
                <div class="mb-3">
                    <label for="extend-mode" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Hình thức gia hạn</label>
                    <select id="extend-mode" class="form-select" onchange="syncExtendMode()" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                        <option value="hours">Theo giờ · 10% giá phòng/giờ</option>
                        <option value="days">Theo ngày · giá phòng hiện tại</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="extend-amount" class="form-label fw-semibold" id="extend-amount-label" style="color: #334155; font-size: 0.85rem;">Số giờ gia hạn</label>
                    <input type="number" id="extend-amount" class="form-control" inputmode="numeric" step="1" min="1" max="12" value="1" required style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                    <div class="form-text" id="extend-help" style="color: #64748b; font-size: 0.8rem;">Tính 10% giá đêm/giờ đến 18:00; vượt mốc này hệ thống tính thành một đêm.</div>
                </div>
                <div class="p-3 rounded d-flex align-items-center gap-2" style="background: #f0f9ff; border: 1px solid #e0f2fe; color: #0369a1; font-size: 0.84rem; border-radius: 10px;">
                    <i class="fa-solid fa-circle-info flex-shrink-0"></i>
                    <span>Hệ thống sẽ kiểm tra lịch trùng trước khi gia hạn theo ngày.</span>
                </div>
            </div>
            <div class="modal-footer px-4 py-3 d-flex justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Hủy</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="submitExtendStay()" style="border-radius: 10px; font-size: 0.88rem;">
                    <i class="fa-solid fa-check me-1"></i> Xác nhận gia hạn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Walk-in Check-in Modal -->
<div class="modal fade" id="walkinCheckinModal" tabindex="-1" aria-labelledby="walkinCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="walkinCheckinModalTitle" style="color: #0f172a;">Check-in Khách Vãng Lai</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4">
                <form id="walkin-checkin-form">
                    <input type="hidden" id="walkin-type" value="now">
                    <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; font-size: 0.9rem;">
                        <span class="fw-bold d-block mb-1" id="modal-rooms-label" style="color: #334155; font-size: 0.85rem;">Các phòng Check-in:</span>
                        <div id="modal-rooms-list" class="mt-2 d-flex flex-wrap gap-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Họ và tên khách hàng <span class="text-danger">*</span></label>
                        <input type="text" id="walkin-name" class="form-control" placeholder="Nguyễn Văn A" required style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" id="walkin-phone" class="form-control" inputmode="numeric" pattern="0[0-9]{9}" maxlength="10" data-digits-only placeholder="0901234567" required style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Email (Không bắt buộc)</label>
                        <input type="email" id="walkin-email" class="form-control" placeholder="nguyenvana@gmail.com" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Số người lớn <span class="text-danger">*</span></label>
                            <input type="number" id="walkin-adults" class="form-control" inputmode="numeric" step="1" value="1" min="1" required style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                        </div>
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Số trẻ em</label>
                            <input type="number" id="walkin-children" class="form-control" inputmode="numeric" step="1" value="0" min="0" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">Ngày trả phòng <span class="text-danger">*</span></label>
                        <input type="date" id="walkin-checkout" class="form-control" required style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.9rem;">
                    </div>
                </form>
            </div>
            <div class="modal-footer px-4 py-3 d-flex justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Hủy</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="submitWalkinCheckin()" style="border-radius: 10px; font-size: 0.88rem;">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- Pre-booked Check-in Confirmation Modal -->
<div class="modal fade" id="prebookCheckinModal" tabindex="-1" aria-labelledby="prebookCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div class="window-controls window-controls--modal d-flex align-items-center gap-1" style="pointer-events: none; margin: 0; padding: 0;" aria-hidden="true">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <h5 class="modal-title fw-bold text-dark m-0 fs-6 text-center flex-grow-1" id="prebookCheckinModalLabel" style="color: #0f172a;">Xác nhận Đặt Phòng Trước</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="prebook-id">
                
                <div class="p-3 mb-3" style="border-radius: 12px; background: #ffffff; border: 1px solid rgba(0,0,0,0.08);">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fa-solid fa-user text-primary fs-3 me-3"></i>
                        <div>
                            <div class="text-muted small fw-medium" style="color: #64748b;">Khách hàng</div>
                            <h5 class="fw-bold mb-0 text-dark" id="prebook-name" style="color: #0f172a;">---</h5>
                        </div>
                    </div>
                    
                    <div class="row g-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <div class="col-6">
                            <span class="text-muted small d-block" style="color: #64748b;">Số điện thoại</span>
                            <strong class="text-dark" id="prebook-phone" style="color: #0f172a;">---</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block" style="color: #64748b;">Email</span>
                            <span class="text-dark fw-semibold" id="prebook-email" style="color: #0f172a; font-size: 0.85rem; word-break: break-all;">---</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-3" style="border-radius: 12px; background: #ffffff; border: 1px solid rgba(0,0,0,0.08);">
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1" style="color: #64748b;"><i class="fa-solid fa-users me-1 text-secondary"></i> Số lượng khách</span>
                        <div class="fw-bold text-dark" id="prebook-guests" style="color: #0f172a;">---</div>
                    </div>
                    
                    <div class="row g-2 border-top pt-3" style="border-color: rgba(0,0,0,0.06) !important;">
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1" style="color: #64748b;"><i class="fa-solid fa-calendar-days me-1 text-secondary"></i> Ngày trả phòng</span>
                            <strong class="text-dark" id="prebook-checkout" style="color: #0f172a;">---</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1" style="color: #64748b;"><i class="fa-solid fa-money-bill-wave me-1 text-secondary"></i> Tổng số tiền</span>
                            <strong class="text-primary fs-5" id="prebook-price">---</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer px-4 py-3 d-flex justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Hủy</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="confirmPrebookCheckin()" style="border-radius: 10px; font-size: 0.88rem;">Xác nhận Check-in</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <h5 class="modal-title fw-bold text-dark m-0 fs-6" id="qrScannerModalLabel" style="color: #0f172a;">Quét QR nhận phòng</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" onclick="closeQRScanner()" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-start">
                    <label for="qr-camera-select" class="form-label small fw-bold" style="color: #475569; font-size: 0.82rem;">Camera</label>
                    <select id="qr-camera-select" class="form-select" style="min-height: 44px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); font-size: 0.85rem;" onchange="changeCamera(this.value)">
                        <option value="">Đang dò tìm camera...</option>
                    </select>
                </div>

                <div class="qr-scan-viewport-container mb-3 position-relative d-inline-block shadow-sm" style="width: 100%; max-width: 380px; aspect-ratio: 1; border-radius: 14px; overflow: hidden; background: #000;">
                    <div id="qr-reader" style="width: 100%; height: 100%;"></div>
                    <div class="qr-target-box">
                        <div class="corner top-left"></div>
                        <div class="corner top-right"></div>
                        <div class="corner bottom-left"></div>
                        <div class="corner bottom-right"></div>
                    </div>
                </div>

                <div class="qr-scan-actions">
                    <button type="button" id="qr-anti-glare-btn" class="qr-scan-action" aria-pressed="false" hidden onclick="toggleAntiGlare()">
                        <i class="bi bi-brightness-low" aria-hidden="true"></i>
                        <span>Giảm chói</span>
                    </button>
                    <label for="qr-image-input" class="qr-scan-action">
                        <i class="bi bi-image" aria-hidden="true"></i>
                        <span>Dùng ảnh QR</span>
                    </label>
                    <input type="file" id="qr-image-input" accept="image/png,image/jpeg,image/webp" hidden>
                </div>
                <p class="qr-scan-help">Giữ mã QR phẳng, nghiêng nhẹ để tránh phản sáng. Nếu màn hình khách quá sáng, hãy giảm độ sáng hoặc chọn ảnh QR đã lưu.</p>
                <div id="qr-scan-status" role="status" aria-live="polite">Căn mã QR vào khung màu xanh.</div>
                <div id="qr-scan-error" class="text-danger small mt-1" role="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-light w-100 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); color: #64748b;" onclick="closeQRScanner()">Hủy bỏ</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Result Modal -->
<div class="modal fade" id="qrResultModal" tabindex="-1" aria-labelledby="qrResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; background: #ffffff;">
            <div class="modal-header d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06);">
                <h5 class="modal-title fw-bold text-dark m-0 fs-6" id="qrResultModalLabel" style="color: #0f172a;">Kết quả quét mã QR</h5>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Đóng" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 h-100" style="border-radius: 12px; background: #ffffff; border: 1px solid rgba(0,0,0,0.08);">
                            <h6 class="fw-bold pb-2 mb-3 border-bottom" style="color: #0f172a; border-color: rgba(0,0,0,0.06) !important;"><i class="fa-solid fa-address-card text-primary me-2"></i>Thông tin khách hàng</h6>
                            <div class="mb-3">
                                <span class="text-muted small d-block" style="color: #64748b;">Họ và Tên</span>
                                <strong class="fs-5" id="qr-guest-name" style="color: #0f172a;">---</strong>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block" style="color: #64748b;">Số điện thoại</span>
                                <strong id="qr-guest-phone" style="color: #0f172a;">---</strong>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block" style="color: #64748b;">Email</span>
                                <span class="fw-semibold" id="qr-guest-email" style="color: #0f172a; font-size: 0.9rem; word-break: break-all;">---</span>
                            </div>
                            <div class="row g-2 border-top pt-3 mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                <div class="col-6">
                                    <span class="text-muted small d-block" style="color: #64748b;">Ngày nhận phòng</span>
                                    <strong id="qr-checkin-date" style="color: #0f172a;">---</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block" style="color: #64748b;">Ngày trả phòng</span>
                                    <strong id="qr-checkout-date" style="color: #0f172a;">---</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 h-100 d-flex flex-column" style="border-radius: 12px; background: #ffffff; border: 1px solid rgba(0,0,0,0.08);">
                            <h6 class="fw-bold pb-2 mb-3 border-bottom" style="color: #0f172a; border-color: rgba(0,0,0,0.06) !important;"><i class="fa-solid fa-bed text-primary me-2"></i>Thông tin phòng &amp; Thanh toán</h6>
                            <div class="mb-3 flex-grow-1">
                                <span class="text-muted small d-block mb-2" style="color: #64748b;">Danh sách phòng đặt:</span>
                                <div id="qr-room-list" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block" style="color: #64748b;">Số lượng khách</span>
                                <strong id="qr-guest-counts" style="color: #0f172a;">---</strong>
                            </div>
                            <div class="border-top pt-3 mt-2" style="border-color: rgba(0,0,0,0.06) !important;">
                                <span class="text-muted small d-block" style="color: #64748b;">Tổng tiền thanh toán</span>
                                <strong class="text-primary fs-4" id="qr-total-price">---</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="qr-status-alert" class="alert mt-3 mb-0 py-3 d-flex align-items-center gap-3" style="display: none; border-radius: 12px; font-weight: 500;"></div>
            </div>
            <div class="modal-footer px-4 py-3 d-flex justify-content-end gap-2" style="background: #f8fafc; border-top: 1px solid rgba(0,0,0,0.06);">
                <button type="button" class="btn btn-light px-4 fw-medium" data-bs-dismiss="modal" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); color: #64748b;">Đóng</button>
                <button type="button" id="btn-qr-action" class="btn btn-primary px-4 fw-bold" style="border-radius: 10px; display: none;">Nhận phòng nhanh</button>
            </div>
        </div>
    </div>
</div>

<!-- Luxury Floating Flash Toast Notification (macOS / Quiet Luxury) -->
<div id="staffFlashToastWrap" class="staff-flash-wrap" aria-live="polite" role="status" style="position: fixed; z-index: 99999; top: 88px; left: 50%; transform: translateX(-50%) translateY(-12px); width: min(540px, calc(100% - 32px)); pointer-events: none; opacity: 0; transition: opacity 0.38s cubic-bezier(0.16, 1, 0.3, 1), transform 0.38s cubic-bezier(0.16, 1, 0.3, 1); display: none;">
    <div id="staffFlashToastCard" class="staff-flash-card" style="pointer-events: auto; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 18px 16px 18px; border-radius: 18px; background: rgba(240, 253, 244, 0.97); border: 1.5px solid rgba(34, 197, 94, 0.32); color: #14532d; box-shadow: 0 20px 48px rgba(0,0,0,0.15), 0 4px 12px rgba(0,0,0,0.06); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
        <div style="display: flex; align-items: center; gap: 12px; font-size: 13.5px; font-weight: 600; line-height: 1.45;">
            <div id="staffFlashIconWrap" style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;">
                <i id="staffFlashIcon" class="bi bi-check-circle-fill"></i>
            </div>
            <span id="staffFlashMessage">Cập nhật thành công!</span>
        </div>
        <button type="button" onclick="dismissStaffFlashToast()" aria-label="Đóng thông báo" style="background: none; border: none; padding: 4px 6px; color: currentColor; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 8px; opacity: 0.65; transition: opacity 0.2s ease;">
            <i class="bi bi-x-lg" style="font-size: 13px;"></i>
        </button>
        <!-- 5s animated countdown progress indicator line at the bottom -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(0,0,0,0.06);">
            <div id="staffFlashProgressBar" style="height: 100%; width: 100%; background: #22c55e; transform-origin: left; transform: scaleX(1);"></div>
        </div>
    </div>
</div>

<script>
    // Laravel Base URL & CSRF Token
    const BASE_URL = "{{ url('/') }}";
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const INTERNAL_CAPABILITIES = @json($permissions);

    let selectedRoomId = null;
    let selectedRoomData = null;
    let currentLateFee = 0;
    let isMultiSelectMode = false;
    let selectedRoomIds = [];
    let selectedRoomsData = [];
    function isOccupiedStatus(status) {
        return ['occupied', 'overdue'].includes(status);
    }


    function setRoomDetailOpen(open) {
        const workspace = document.querySelector('.staff-workspace');
        const mutate = () => workspace?.classList.toggle('has-room-detail', open);
        window.animateInternalRoomLayout ? window.animateInternalRoomLayout(mutate) : mutate();
    }

    const defaultImg = @json(asset('images/rooms/default.jpg'));

    function formatDateVi(value) {
        if (!value) return '---';
        const parts = String(value).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
    }
    function formatCheckinVi(value) {
        if (!value) return '---';
        const parts = String(value).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]} 14:00` : value;
    }
    function formatCheckoutVi(value) {
        if (!value) return '---';
        const parts = String(value).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]} 12:00` : value;
    }

    let boardRefreshController = null;
    let filterDebounceTimer = null;

    function refreshReceptionBoard({ reselectCurrentRoom = true } = {}) {
        const previousSelectedRoomId = selectedRoomId;
        const focusedRoomCardId = document.activeElement?.classList?.contains('room-card')
            ? document.activeElement.id
            : null;
        const previousFilters = {
            search: document.getElementById('search-input')?.value || '',
            floor: document.getElementById('filter-floor')?.value || 'Tất cả',
            type: document.getElementById('filter-type')?.value || 'Tất cả',
            guests: document.getElementById('filter-guests')?.value || 'Tất cả',
            status: document.getElementById('filter-status')?.value || 'Tất cả'
        };

        if (boardRefreshController) boardRefreshController.abort();
        boardRefreshController = new AbortController();

        return fetch(window.location.href, {
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: boardRefreshController.signal
        })
        .then(response => response.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const replacements = [
                ['.legend-row', '.legend-row'],
                ['.filter-card', '.filter-card'],
                ['#room-grid-container', '#room-grid-container']
            ];

            replacements.forEach(([currentSelector, nextSelector]) => {
                const current = document.querySelector(currentSelector);
                const next = doc.querySelector(nextSelector);
                if (current && next) current.innerHTML = next.innerHTML;
            });

            const searchInput = document.getElementById('search-input');
            const floorSelect = document.getElementById('filter-floor');
            const typeSelect = document.getElementById('filter-type');
            const guestsSelect = document.getElementById('filter-guests');
            const statusSelect = document.getElementById('filter-status');

            if (searchInput) searchInput.value = previousFilters.search;
            if (floorSelect) floorSelect.value = previousFilters.floor;
            if (typeSelect) typeSelect.value = previousFilters.type;
            if (guestsSelect) guestsSelect.value = previousFilters.guests;
            if (statusSelect) statusSelect.value = previousFilters.status;

            document.querySelectorAll('.legend-badge').forEach(el => {
                el.classList.toggle('active-filter', el.getAttribute('data-status-val') === previousFilters.status);
            });

            filterRooms();

            if (reselectCurrentRoom && previousSelectedRoomId && !isMultiSelectMode) {
                const card = document.getElementById(`room-card-${previousSelectedRoomId}`);
                if (card && card.style.display !== 'none') {
                    card.click();
                    if (focusedRoomCardId) card.focus({ preventScroll: true });
                } else {
                    closeDetailPanel();
                }
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') console.error('Silent board refresh failed:', error);
        });
    }

    function toggleMultiSelectMode() {
        if (!INTERNAL_CAPABILITIES.bp_create_booking) return;
        const toggle = document.getElementById('multi-select-toggle');
        if (!toggle) return;
        isMultiSelectMode = toggle.checked;

        closeDetailPanel();
        selectedRoomIds = [];
        selectedRoomsData = [];
        document.querySelectorAll('.room-card').forEach(el => el.classList.remove('selected'));
    }

    function selectRoom(element, roomData) {
        if (!roomData) {
            roomData = JSON.parse(element.getAttribute('data-room'));
        }
        if (isMultiSelectMode) {
            const roomId = roomData.id;
            const index = selectedRoomIds.indexOf(roomId);

            if (index > -1) {
                selectedRoomIds.splice(index, 1);
                selectedRoomsData.splice(index, 1);
                element.classList.remove('selected');
            } else {
                if (roomData.ui_status !== 'available' || parseInt(roomData.has_today_booking) > 0) {
                    showToast('Trong chế độ chọn nhiều, chỉ được chọn các phòng đang trống!', 'bg-warning text-dark');
                    return;
                }
                selectedRoomIds.push(roomId);
                selectedRoomsData.push(roomData);
                element.classList.add('selected');
            }

            if (selectedRoomIds.length > 0) {
                setRoomDetailOpen(true);
                document.getElementById('empty-detail-panel').style.display = 'none';
                document.getElementById('room-detail-panel').style.display = 'flex';
                document.getElementById('room-detail-panel').classList.add('is-open');
                document.getElementById('single-room-container').style.display = 'none';
                document.getElementById('multi-room-container').style.display = 'flex';

                document.getElementById('detail-title').innerText = 'Đặt nhiều phòng';
                document.getElementById('multi-room-count').innerText = selectedRoomIds.length;

                const listContainer = document.getElementById('multi-room-list');
                listContainer.innerHTML = '';
                selectedRoomsData.forEach(r => {
                    const item = document.createElement('div');
                    item.className = 'd-flex justify-content-between align-items-center p-2 bg-white border rounded';
                    item.style.fontSize = '0.85rem';
                    item.innerHTML = `
                        <div>
                            <strong>Phòng ${r.room_number}</strong> 
                            <span class="text-muted" style="font-size:0.75rem;">(${r.type_name})</span>
                        </div>
                        <span class="badge bg-success">Đang trống</span>
                    `;
                    listContainer.appendChild(item);
                });
            } else {
                closeDetailPanel();
            }
        } else {
            setRoomDetailOpen(true);
            selectedRoomId = roomData.id;
            selectedRoomData = roomData;

            document.querySelectorAll('.room-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');

            document.getElementById('empty-detail-panel').style.display = 'none';
            document.getElementById('room-detail-panel').style.display = 'flex';
                document.getElementById('room-detail-panel').classList.add('is-open');
            document.getElementById('single-room-container').style.display = 'block';
            document.getElementById('multi-room-container').style.display = 'none';

            document.getElementById('detail-title').innerText = 'Phòng ' + roomData.room_number;
            document.getElementById('detail-type').innerText = roomData.type_name;
            document.getElementById('detail-capacity').innerText = roomData.max_guests + ' người';
            document.getElementById('detail-price').innerText = new Intl.NumberFormat('vi-VN').format(roomData.price) + ' đ / đêm';
            document.getElementById('detail-amenities').innerText = roomData.amenities_list || 'Không có';
            document.getElementById('detail-img').src = roomData.image_url || defaultImg;
            document.getElementById('detail-img').alt = roomData.type_name || ('Phòng ' + roomData.room_number);
            document.getElementById('detail-capacity').innerText = `${roomData.max_guests} khách tối đa (${roomData.max_adults} người lớn, ${roomData.max_children} trẻ em)`;

            let badgeClass = 'bg-secondary';
            let statusText = '';
                switch(roomData.ui_status || roomData.status) {
                case 'available': badgeClass = 'bg-success'; statusText = 'Đang trống'; break;
                case 'occupied': badgeClass = 'bg-primary'; statusText = 'Đang lưu trú'; break;
                case 'cleaning': badgeClass = 'bg-warning text-dark'; statusText = 'Đang dọn dẹp'; break;
                case 'maintenance': badgeClass = 'bg-danger'; statusText = 'Bảo trì'; break;
                case 'booked': badgeClass = 'bg-warning text-dark'; statusText = 'Đã đặt'; break;
                case 'overdue': badgeClass = 'bg-danger'; statusText = 'Quá giờ trả'; break;
            }
            document.getElementById('detail-status-badge').innerHTML = `<span class="badge ${badgeClass} rounded-pill px-3">${statusText}</span>`;

    const isOccupiedLike = isOccupiedStatus(roomData.ui_status || roomData.status) || (parseInt(roomData.has_active_booking) > 0);
    const specsCard = document.getElementById('room-specs-card');
    if (specsCard) {
        specsCard.style.setProperty('display', isOccupiedLike ? 'none' : 'block', 'important');
    }
            ['detail-type', 'detail-capacity', 'detail-price', 'detail-amenities'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.parentElement) {
                    el.parentElement.style.display = isOccupiedLike ? 'none' : 'flex';
                }
            });

            const occupiedInfo = document.getElementById('occupied-booking-info');
            if (occupiedInfo) {
                occupiedInfo.style.display = isOccupiedLike ? 'block' : 'none';
            }

            if (isOccupiedLike) {
                if (parseInt(roomData.has_active_booking) > 0) {
                    document.getElementById('occupied-booking-id').innerText = '# ' + roomData.active_booking_id;
                    document.getElementById('occupied-customer-name').innerText = roomData.customer_name || '---';
                    document.getElementById('occupied-customer-phone').innerText = roomData.customer_phone || '---';
                    document.getElementById('occupied-customer-email').innerText = roomData.active_customer_email || '---';
                    document.getElementById('occupied-checkin').innerText = formatCheckinVi(roomData.active_check_in);
                    document.getElementById('occupied-checkout').innerText = formatCheckoutVi(roomData.active_check_out);

                    const adults = parseInt(roomData.active_adult_count) || 0;
                    const children = parseInt(roomData.active_child_count) || 0;
                    document.getElementById('occupied-guests').innerText = `${adults} người lớn` + (children > 0 ? `, ${children} trẻ em` : '');
                    
                    const total = Number(roomData.active_total_price || 0);
                    document.getElementById('occupied-total-price').innerText = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
                } else {
                    document.getElementById('occupied-booking-id').innerText = 'Chưa có booking';
                    document.getElementById('occupied-customer-name').innerText = 'Phòng đang được sử dụng';
                    document.getElementById('occupied-customer-phone').innerText = '---';
                    document.getElementById('occupied-customer-email').innerText = '---';
                    document.getElementById('occupied-checkin').innerText = '---';
                    document.getElementById('occupied-checkout').innerText = '---';
                    document.getElementById('occupied-guests').innerText = '---';
                    document.getElementById('occupied-total-price').innerText = '---';
                }
            }

            const todayInfo = document.getElementById('today-booking-info');
            if (todayInfo) {
                const hasTodayBooking = parseInt(roomData.has_today_booking) > 0;
                todayInfo.style.display = hasTodayBooking ? 'block' : 'none';
                if (hasTodayBooking) {
                    document.getElementById('today-booking-id').innerText = '# ' + (roomData.today_booking_id || '---');
                    document.getElementById('today-customer-name').innerText = roomData.today_customer_name || '---';
                    document.getElementById('today-customer-phone').innerText = roomData.today_customer_phone || '---';
                    document.getElementById('today-customer-email').innerText = roomData.today_customer_email || 'Chưa cung cấp';
                    document.getElementById('today-checkin').innerText = formatCheckinVi(roomData.today_check_in);
                    document.getElementById('today-checkout').innerText = formatCheckoutVi(roomData.today_check_out);

                    const adults = parseInt(roomData.today_adult_count) || 0;
                    const children = parseInt(roomData.today_child_count) || 0;
                    document.getElementById('today-guests').innerText = `${adults} người lớn` + (children > 0 ? `, ${children} trẻ em` : '');
                    
                    const total = Number(roomData.today_total_price || 0);
                    document.getElementById('today-total-price').innerText = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
                }
            }

            const bookingRow = document.getElementById('detail-booking-row');
            if (bookingRow) {
                bookingRow.style.display = parseInt(roomData.has_today_booking) > 0 ? 'flex' : 'none';
            }

            resetActionButtons(roomData.ui_status || roomData.status);
        }
    }

    function closeDetailPanel() {
        const panel = document.getElementById('room-detail-panel');
        setRoomDetailOpen(false);
        panel.classList.remove('is-open');
        setTimeout(() => panel.style.display = 'none', 220);
        document.getElementById('empty-detail-panel').style.display = 'none';
        document.getElementById('single-room-container').style.display = 'block';
        document.getElementById('multi-room-container').style.display = 'none';
        document.querySelectorAll('.room-card').forEach(el => el.classList.remove('selected'));
        selectedRoomId = null;
        selectedRoomData = null;
        if (isMultiSelectMode) {
            selectedRoomIds = [];
            selectedRoomsData = [];
        }
    }

    function resetActionButtons(currentStatus) {
        const btnAvailable = document.getElementById('btn-action-available');
        const btnOccupied = document.getElementById('btn-action-occupied');
        const btnExtend = document.getElementById('btn-action-extend');
        const btnCleaningDone = document.getElementById('btn-action-cleaning-done');
        const btnCleaningRequestDone = document.getElementById('btn-action-cleaning-request-done');

        if (btnAvailable) { btnAvailable.style.display = 'none'; btnAvailable.disabled = false; }
        if (btnOccupied) { btnOccupied.style.display = 'none'; btnOccupied.disabled = false; }
        if (btnExtend) { btnExtend.style.display = 'none'; btnExtend.disabled = false; }
        if (btnCleaningDone) { btnCleaningDone.style.display = 'none'; btnCleaningDone.disabled = false; }
        if (btnCleaningRequestDone) { btnCleaningRequestDone.style.display = 'none'; btnCleaningRequestDone.disabled = false; }

        const isOccupied = isOccupiedStatus(currentStatus) || (selectedRoomData && parseInt(selectedRoomData.has_active_booking) > 0);

        if (currentStatus === 'available' || currentStatus === 'booked') {
            const hasTodayBooking = selectedRoomData && parseInt(selectedRoomData.has_today_booking) > 0;
            const canCheckInSelectedRoom = INTERNAL_CAPABILITIES.bp_checkin_checkout
                && (hasTodayBooking || INTERNAL_CAPABILITIES.bp_create_booking);
            if (btnOccupied && canCheckInSelectedRoom) btnOccupied.style.display = 'block';
        } else if (isOccupied) {
            if (btnAvailable) btnAvailable.style.display = 'block';
            if (btnExtend) {
                btnExtend.style.display = 'block';
                btnExtend.disabled = !(selectedRoomData && parseInt(selectedRoomData.has_active_booking) > 0);
            }
            if (btnCleaningRequestDone && selectedRoomData?.needs_cleaning) {
                btnCleaningRequestDone.style.display = 'block';
            }
        } else if (currentStatus === 'cleaning') {
            if (btnCleaningDone) btnCleaningDone.style.display = 'block';
        }
    }

    async function completeCleaningRequest() {
        if (!INTERNAL_CAPABILITIES.bp_clean_status) return;
        if (!selectedRoomId || !selectedRoomData?.needs_cleaning) return;
        const button = document.getElementById('btn-action-cleaning-request-done');
        if (button) button.disabled = true;
        const url = @json(route('staff.room.cleaning.complete', ['roomId' => '__ROOM__']))
            .replace('__ROOM__', encodeURIComponent(selectedRoomId));
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                cache: 'no-store',
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Không thể hoàn tất yêu cầu dọn phòng.');
            showToast(result.message, 'bg-success');
            await refreshReceptionBoard();
        } catch (error) {
            showToast(error.message, 'bg-danger');
        } finally {
            if (button) button.disabled = false;
        }
    }

    function openExtendStayModal() {
        if (!INTERNAL_CAPABILITIES.bp_extend_stay) return;
        if (!selectedRoomData) return;

        const currentStatus = selectedRoomData.ui_status || selectedRoomData.status;
        if (!isOccupiedStatus(currentStatus) && parseInt(selectedRoomData.has_active_booking) <= 0) {
            showToast('Chỉ có thể gia hạn phòng đang sử dụng.', 'bg-warning text-dark');
            return;
        }

        if (parseInt(selectedRoomData.has_active_booking) <= 0) {
            showToast('Phòng chưa có booking checked-in để gia hạn.', 'bg-warning text-dark');
            return;
        }

        document.getElementById('extend-room-label').innerText = 'Phòng ' + selectedRoomData.room_number;
        document.getElementById('extend-mode').value = 'hours';
        document.getElementById('extend-amount').value = 1;
        syncExtendMode();

        const modal = new bootstrap.Modal(document.getElementById('extendStayModal'));
        modal.show();
    }

    function syncExtendMode() {
        const mode = document.getElementById('extend-mode').value;
        const amount = document.getElementById('extend-amount');
        document.getElementById('extend-amount-label').textContent = mode === 'hours' ? 'Số giờ gia hạn' : 'Số ngày gia hạn';
        document.getElementById('extend-help').textContent = mode === 'hours' ? 'Tính 10% giá đêm/giờ đến 18:00; vượt mốc này hệ thống tính thành một đêm.' : 'Từ 1 đến 30 ngày, tính theo giá phòng hiện tại.';
        amount.max = mode === 'hours' ? 12 : 30;
    }

    function submitExtendStay() {
        if (!INTERNAL_CAPABILITIES.bp_extend_stay) return;
        if (!selectedRoomId) return;

        const mode = document.getElementById('extend-mode').value;
        const amountInput = document.getElementById('extend-amount');
        const amount = parseInt(amountInput.value, 10);
        const max = mode === 'hours' ? 12 : 30;

        if (!amount || amount < 1 || amount > max) {
            showToast(`Số ${mode === 'hours' ? 'giờ' : 'ngày'} gia hạn phải từ 1 đến ${max}.`, 'bg-warning text-dark');
            amountInput.focus();
            return;
        }

        fetch("{{ route('staff.reception.extend') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ room_id: selectedRoomId, mode, amount })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('extendStayModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                showToast(data.message, 'bg-success');
                refreshReceptionBoard();
            } else {
                showToast('Lỗi: ' + data.message, 'bg-danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Lỗi hệ thống khi gia hạn lưu trú.', 'bg-danger');
        });
    }

    function handleSingleCheckin() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout) return;
        if (!selectedRoomData) return;
        
        if (parseInt(selectedRoomData.has_today_booking) > 0) {
            fetch(`{{ route('staff.reception.today-booking') }}?room_id=${selectedRoomData.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const b = data.booking;
                    document.getElementById('prebook-id').value = b.id;
                    document.getElementById('prebook-name').innerText = b.customer_name;
                    document.getElementById('prebook-phone').innerText = b.customer_phone;
                    document.getElementById('prebook-email').innerText = b.customer_email || 'Chưa cung cấp';
                    document.getElementById('prebook-guests').innerText = `${b.adult_count} người lớn, ${b.child_count} trẻ em`;
                    
                    const parts = b.check_out.split('-');
                    const formattedCheckOut = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : b.check_out;
                    document.getElementById('prebook-checkout').innerText = formattedCheckOut;
                    document.getElementById('prebook-price').innerText = Number(b.total_price).toLocaleString('vi-VN') + ' đ';
                    
                    const modal = new bootstrap.Modal(document.getElementById('prebookCheckinModal'));
                    modal.show();
                } else {
                    showToast('Lỗi: ' + data.message, 'bg-danger');
                }
            })
            .catch(error => {
                console.error(error);
                showToast('Lỗi hệ thống khi lấy thông tin lịch đặt trước.', 'bg-danger');
            });
        } else {
            openWalkinCheckinModal('now');
        }
    }

    function confirmPrebookCheckin() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout) return;
        checkInSelectedRoom();
    }

    async function checkInSelectedRoom() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !selectedRoomId) return;

        const url = @json(route('staff.room.checkin', ['id' => '__ROOM__']))
            .replace('__ROOM__', encodeURIComponent(selectedRoomId));
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                showToast(data.message || 'Không thể nhận phòng.', 'bg-danger');
                return;
            }

            const modalEl = document.getElementById('prebookCheckinModal');
            const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
            modal?.hide();
            showToast(data.message, 'bg-success');
            refreshReceptionBoard();
        } catch (error) {
            console.error(error);
            showToast('Lỗi hệ thống khi nhận phòng.', 'bg-danger');
        }
    }

    function openWalkinCheckinModal(walkinType = null) {
        if (!INTERNAL_CAPABILITIES.bp_create_booking) return;
        walkinType = walkinType === 'now' && !INTERNAL_CAPABILITIES.bp_checkin_checkout
            ? 'hold'
            : (walkinType || (INTERNAL_CAPABILITIES.bp_checkin_checkout ? 'now' : 'hold'));

        let rooms = [];
        if (isMultiSelectMode) {
            rooms = [...selectedRoomsData];
        } else {
            if (selectedRoomData) rooms.push(selectedRoomData);
        }

        if (rooms.length === 0) {
            showToast('Vui lòng chọn ít nhất một phòng.', 'bg-danger');
            return;
        }

        const occupied = rooms.some(r => r.ui_status !== 'available');
        if (occupied) {
            showToast('Chỉ có thể check-in phòng đang trống.', 'bg-danger');
            return;
        }

        const titleEl = document.getElementById('walkinCheckinModalTitle');
        const roomsLabelEl = document.getElementById('modal-rooms-label');
        if (titleEl) titleEl.innerHTML = walkinType === 'now'
            ? '<i class="fa-solid fa-user-plus me-2"></i>Check-in Khách Vãng Lai'
            : '<i class="fa-solid fa-calendar-plus me-2"></i>Tạo đặt phòng tại quầy';
        if (roomsLabelEl) roomsLabelEl.innerText = walkinType === 'now' ? 'Các phòng Check-in:' : 'Các phòng đặt:';
        document.getElementById('walkin-type').value = walkinType;

        const listEl = document.getElementById('modal-rooms-list');
        listEl.innerHTML = '';
        rooms.forEach(r => {
            const span = document.createElement('span');
            span.className = 'badge bg-primary px-3 py-2 fs-6';
            span.innerText = 'Phòng ' + r.room_number;
            listEl.appendChild(span);
        });

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('walkin-checkout').min = [tomorrow.getFullYear(), String(tomorrow.getMonth()+1).padStart(2,'0'), String(tomorrow.getDate()).padStart(2,'0')].join('-');
        document.getElementById('walkin-checkout').value = [tomorrow.getFullYear(), String(tomorrow.getMonth()+1).padStart(2,'0'), String(tomorrow.getDate()).padStart(2,'0')].join('-');

        document.getElementById('walkin-name').value = '';
        document.getElementById('walkin-phone').value = '';
        document.getElementById('walkin-email').value = '';
        document.getElementById('walkin-adults').value = rooms.length;
        document.getElementById('walkin-children').value = 0;

        const modal = new bootstrap.Modal(document.getElementById('walkinCheckinModal'));
        modal.show();
    }

    function submitWalkinCheckin() {
        if (!INTERNAL_CAPABILITIES.bp_create_booking) return;
        const name = document.getElementById('walkin-name').value.trim();
        const phone = document.getElementById('walkin-phone').value.trim();
        const email = document.getElementById('walkin-email').value.trim();
        const walkinType = document.getElementById('walkin-type').value;
        if (walkinType === 'now' && !INTERNAL_CAPABILITIES.bp_checkin_checkout) return;
        const adults = parseInt(document.getElementById('walkin-adults').value) || 1;
        const children = parseInt(document.getElementById('walkin-children').value) || 0;
        const checkout = document.getElementById('walkin-checkout').value;

        if (!name || !phone || !checkout) {
            showToast('Vui lòng điền đầy đủ các thông tin bắt buộc.', 'bg-danger');
            return;
        }

        let rooms = [];
        if (isMultiSelectMode) {
            rooms = [...selectedRoomsData];
        } else {
            if (selectedRoomData) rooms.push(selectedRoomData);
        }

        const roomIds = rooms.map(r => r.id);

        fetch("{{ route('staff.reception.walkin') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                room_ids: roomIds,
                customer_name: name,
                customer_phone: phone,
                customer_email: email,
                adult_count: adults,
                child_count: children,
                walkin_type: walkinType,
                check_out: checkout
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('walkinCheckinModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                showToast(data.message, 'bg-success');
                if (isMultiSelectMode) {
                    toggleMultiSelectMode();
                    document.getElementById('multi-select-toggle').checked = false;
                }
                refreshReceptionBoard({ reselectCurrentRoom: !isMultiSelectMode });
            } else {
                showToast('Lỗi: ' + data.message, 'bg-danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Lỗi hệ thống khi thực hiện Check-in.', 'bg-danger');
        });
    }

    function openCheckoutScopeModal() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment) return;
        if (!selectedRoomData) return;
        document.getElementById('checkout-room-label').innerText = 'Phòng ' + selectedRoomData.room_number;
        const modal = new bootstrap.Modal(document.getElementById('checkoutScopeModal'));
        modal.show();
    }

    function submitCheckout(scope) {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment) return;
        const modalEl = document.getElementById('checkoutScopeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        if (scope === 'room') {
            // Chỉ trả phòng lẻ: Nếu đây là phòng occupied cuối cùng trong booking, bắt buộc phải thanh toán!
            const occupiedCount = parseInt(selectedRoomData.active_booking_occupied_room_count) || 0;
            if (occupiedCount <= 1) {
                openCheckoutPaymentModal();
            } else {
                triggerStatusUpdate('cleaning', 'room');
            }
        } else {
            // Trả toàn bộ booking: Cần chọn phương thức thanh toán nốt số tiền còn lại
            openCheckoutPaymentModal();
        }
    }

    function selectStaffMethod(radio) {
        document.querySelectorAll('.payment-option-staff').forEach(el => {
            el.classList.remove('is-selected');
            el.style.borderColor = '';
            el.style.background  = '';
        });
        const label = radio.closest('.payment-option-staff');
        if (label) {
            label.classList.add('is-selected');
        }
    }

    function openCheckoutPaymentModal() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment) return;
        if (!selectedRoomData) return;
        if (parseInt(selectedRoomData.has_active_booking) <= 0) {
            // Không có booking hoạt động, chuyển thẳng sang dọn dẹp
            triggerStatusUpdate('cleaning', 'room');
            return;
        }

        const baseTotal = Number(selectedRoomData.active_total_price || 0);
        const paid = Number(selectedRoomData.active_paid_amount || 0);

        const now = new Date();
        const scheduledCheckoutAt = selectedRoomData.active_scheduled_checkout_at
            ? new Date(selectedRoomData.active_scheduled_checkout_at)
            : selectedRoomData.active_check_out
                ? new Date('' + selectedRoomData.active_check_out + 'T12:00:00+07:00')
                : null;
        const lateDeadline = scheduledCheckoutAt
            ? new Date(scheduledCheckoutAt.getTime() + 60 * 60 * 1000)
            : null;
        const nightRate = Number(selectedRoomData.active_room_price_per_night || 0);
        const lateFee = lateDeadline && now > lateDeadline && nightRate > 0
            ? Math.round(nightRate * 0.5)
            : 0;
        currentLateFee = lateFee;

        const remaining = (baseTotal - paid) + lateFee;

        const lateFeeRow = document.getElementById('mo-late-fee-row');
        document.getElementById('late-fee-waiver').hidden = lateFee <= 0;
        if (lateFee > 0) {
            lateFeeRow.hidden = false;
            document.getElementById('mo-late-fee').innerText = new Intl.NumberFormat('vi-VN').format(lateFee) + ' đ';
        } else {
            lateFeeRow.hidden = true;
        }

        document.getElementById('mo-customer').innerText = selectedRoomData.customer_name || '---';
        document.getElementById('mo-room').innerText = 'Phòng ' + selectedRoomData.room_number;
        document.getElementById('mo-checkin').innerText = formatCheckinVi(selectedRoomData.active_check_in);
        document.getElementById('mo-checkout').innerText = formatDateVi(selectedRoomData.active_check_out);
        
        document.getElementById('mo-total').innerText = new Intl.NumberFormat('vi-VN').format(baseTotal) + ' đ';
        document.getElementById('mo-paid').innerText = new Intl.NumberFormat('vi-VN').format(paid) + ' đ';
        document.getElementById('mo-remaining').innerText = new Intl.NumberFormat('vi-VN').format(remaining) + ' đ';
        document.getElementById('waive-late-fee').checked = false;

        // Reset payment options
        document.querySelectorAll('input[name="staff_payment_method"]').forEach(el => el.checked = false);
        document.querySelectorAll('.payment-option-staff').forEach(el => {
            el.classList.remove('is-selected');
            el.style.borderColor = '';
            el.style.background  = '';
        });

        // Show checkout payment modal
        const modal = new bootstrap.Modal(document.getElementById('checkoutPaymentModal'));
        modal.show();
    }

    function submitCheckoutPayment() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment) return;
        if (!selectedRoomData) return;
        const bookingId = selectedRoomData.active_booking_id;
        if (!bookingId) return;

        const selectedRadio = document.querySelector('input[name="staff_payment_method"]:checked');
        if (!selectedRadio) {
            showToast('Vui lòng chọn phương thức thanh toán.', 'bg-warning text-dark');
            return;
        }

        const paymentMethod = selectedRadio.value;

        // Hide checkout payment modal
        const modalEl = document.getElementById('checkoutPaymentModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        fetch(`/staff/bookings/${bookingId}/checkout-payment`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                payment_method: paymentMethod,
                waive_late_fee: document.getElementById('waive-late-fee').checked,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                showToast(data.message, 'bg-success');
                refreshReceptionBoard();
            } else {
                showToast('Lỗi: ' + data.message, 'bg-danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Lỗi hệ thống khi thực hiện trả phòng & thanh toán.', 'bg-danger');
        });
    }

    function triggerStatusUpdate(newStatus, checkoutScope = null) {
        if (!selectedRoomId) return;

        const currentStatus = selectedRoomData ? (selectedRoomData.ui_status || selectedRoomData.status) : '';
        const isCheckout = newStatus === 'available'
            && (isOccupiedStatus(currentStatus) || parseInt(selectedRoomData?.has_active_booking) > 0);
        const isPartialCheckout = newStatus === 'cleaning' && checkoutScope === 'room';
        if (newStatus === 'occupied' && !INTERNAL_CAPABILITIES.bp_checkin_checkout) return;
        if (newStatus === 'cleaning' && !isPartialCheckout && !INTERNAL_CAPABILITIES.bp_clean_status) return;
        if (isPartialCheckout
            && (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment)) return;
        if (newStatus === 'available' && isCheckout
            && (!INTERNAL_CAPABILITIES.bp_checkin_checkout || !INTERNAL_CAPABILITIES.tn_collect_payment)) return;
        if (newStatus === 'available' && !isCheckout && !INTERNAL_CAPABILITIES.bp_clean_status) return;
        if (newStatus === 'available' && (isOccupiedStatus(currentStatus) || parseInt(selectedRoomData?.has_active_booking) > 0) && checkoutScope === null) {
            const roomCount = parseInt(selectedRoomData.active_booking_room_count) || 0;
            if (roomCount > 1) {
                openCheckoutScopeModal();
            } else {
                openCheckoutPaymentModal();
            }
            return;
        }

        const optimisticCard = document.getElementById(`room-card-${selectedRoomId}`);
        const optimisticSnapshot = optimisticCard ? {
            status: optimisticCard.dataset.status,
            className: optimisticCard.className,
            label: optimisticCard.querySelector('.room-status-text')?.textContent
        } : null;
        const labels = { available: 'Đang trống', occupied: 'Đang lưu trú', cleaning: 'Đang dọn dẹp', maintenance: 'Bảo trì', booked: 'Đã đặt', overdue: 'Quá giờ trả' };
        if (optimisticCard) {
            optimisticCard.className = optimisticCard.className.replace(/status-(available|occupied|cleaning)/, `status-${newStatus}`);
            optimisticCard.dataset.status = newStatus;
            const label = optimisticCard.querySelector('.room-status-text');
            if (label) label.textContent = labels[newStatus] || newStatus;
            optimisticCard.classList.add('is-syncing');
        }
        const rollbackOptimisticCard = () => {
            if (!optimisticCard || !optimisticSnapshot) return;
            optimisticCard.className = optimisticSnapshot.className;
            optimisticCard.dataset.status = optimisticSnapshot.status;
            const label = optimisticCard.querySelector('.room-status-text');
            if (label) label.textContent = optimisticSnapshot.label;
        };

        fetch("{{ route('staff.reception.update-status') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                room_id: selectedRoomId,
                status: newStatus,
                checkout_scope: checkoutScope
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const prebookModalEl = document.getElementById('prebookCheckinModal');
                if (prebookModalEl) {
                    const prebookModal = bootstrap.Modal.getInstance(prebookModalEl);
                    if (prebookModal) prebookModal.hide();
                }

                showToast(data.message, 'bg-success');
                refreshReceptionBoard();
            } else {
                rollbackOptimisticCard();
                showToast('Lỗi: ' + data.message, 'bg-danger');
            }
        })
        .catch(error => {
            rollbackOptimisticCard();
            console.error(error);
            showToast('Lỗi hệ thống khi cập nhật trạng thái phòng.', 'bg-danger');
        });
    }

    // Toast thông báo booking huỷ cần xử lý
    @if(($pendingRefunds ?? 0) > 0)
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            showToast(
                '🔴 Có {{ $pendingRefunds }} booking huỷ cần xử lý hoàn tiền!',
                'bg-danger'
            );
        }, 800);
    });
    @endif

    let staffFlashTimer = null;

    function showToast(message, typeOrClass = 'bg-success') {
        const wrap = document.getElementById('staffFlashToastWrap');
        const card = document.getElementById('staffFlashToastCard');
        const msgEl = document.getElementById('staffFlashMessage');
        const iconWrap = document.getElementById('staffFlashIconWrap');
        const icon = document.getElementById('staffFlashIcon');
        const bar = document.getElementById('staffFlashProgressBar');
        if (!wrap || !card || !msgEl) return;

        if (staffFlashTimer) {
            clearTimeout(staffFlashTimer);
            staffFlashTimer = null;
        }

        const isError = String(typeOrClass).includes('danger') || String(typeOrClass).includes('error');
        const isWarning = String(typeOrClass).includes('warning');

        if (isError) {
            card.style.background = 'rgba(255, 245, 245, 0.97)';
            card.style.borderColor = 'rgba(239, 68, 68, 0.32)';
            card.style.color = '#991b1b';
            if (iconWrap) { iconWrap.style.background = '#fee2e2'; iconWrap.style.color = '#dc2626'; }
            if (icon) { icon.className = 'bi bi-exclamation-triangle-fill'; }
            if (bar) { bar.style.background = '#ef4444'; }
        } else if (isWarning) {
            card.style.background = 'rgba(255, 251, 235, 0.97)';
            card.style.borderColor = 'rgba(245, 158, 11, 0.32)';
            card.style.color = '#92400e';
            if (iconWrap) { iconWrap.style.background = '#fef3c7'; iconWrap.style.color = '#d97706'; }
            if (icon) { icon.className = 'bi bi-exclamation-circle-fill'; }
            if (bar) { bar.style.background = '#f59e0b'; }
        } else {
            card.style.background = 'rgba(240, 253, 244, 0.97)';
            card.style.borderColor = 'rgba(34, 197, 94, 0.32)';
            card.style.color = '#14532d';
            if (iconWrap) { iconWrap.style.background = '#dcfce7'; iconWrap.style.color = '#16a34a'; }
            if (icon) { icon.className = 'bi bi-check-circle-fill'; }
            if (bar) { bar.style.background = '#22c55e'; }
        }

        msgEl.innerText = message;
        wrap.style.display = 'block';

        if (bar) {
            bar.style.transition = 'none';
            bar.style.transform = 'scaleX(1)';
        }

        requestAnimationFrame(() => {
            wrap.style.opacity = '1';
            wrap.style.transform = 'translateX(-50%) translateY(0)';
            if (bar) {
                requestAnimationFrame(() => {
                    bar.style.transition = 'transform 5s linear';
                    bar.style.transform = 'scaleX(0)';
                });
            }
        });

        staffFlashTimer = setTimeout(() => {
            dismissStaffFlashToast();
        }, 5000);
    }

    function dismissStaffFlashToast() {
        const wrap = document.getElementById('staffFlashToastWrap');
        if (!wrap) return;
        wrap.style.opacity = '0';
        wrap.style.transform = 'translateX(-50%) translateY(-14px)';
        setTimeout(() => {
            wrap.style.display = 'none';
        }, 400);
    }

    function filterRooms() {
        const searchVal = document.getElementById('search-input').value.toLowerCase().trim();
        const floorVal = document.getElementById('filter-floor').value;
        const typeVal = document.getElementById('filter-type').value;
        const guestsVal = document.getElementById('filter-guests').value;
        const statusVal = document.getElementById('filter-status').value;

        document.querySelectorAll('[data-floor-tab]').forEach(tab => {
            tab.classList.toggle('is-active', tab.dataset.floorTab === floorVal);
            tab.setAttribute('aria-pressed', String(tab.dataset.floorTab === floorVal));
        });
        document.querySelectorAll('.legend-badge').forEach(tab => {
            const active = tab.dataset.statusVal === statusVal;
            tab.classList.toggle('active-filter', active);
            tab.setAttribute('aria-pressed', String(active));
        });
        
        document.querySelectorAll('.room-card-wrapper').forEach(wrapper => {
            const card = wrapper.querySelector('.room-card');
            const floor = card.getAttribute('data-floor');
            const type = card.getAttribute('data-type');
            const guests = parseInt(card.getAttribute('data-guests'));
            const status = card.getAttribute('data-status');
            const search = card.getAttribute('data-search');
            
            let match = true;
            
            if (searchVal && !search.includes(searchVal)) match = false;
            if (floorVal !== 'Tất cả' && floor !== floorVal) match = false;
            if (typeVal !== 'Tất cả' && type !== typeVal) match = false;
            
            if (guestsVal !== 'Tất cả') {
                if (guestsVal === '5+') {
                    if (guests < 5) match = false;
                } else {
                    if (guests !== parseInt(guestsVal)) match = false;
                }
            }
            
            if (statusVal !== 'Tất cả' && status !== statusVal) match = false;
            
            wrapper.style.display = match ? 'block' : 'none';
        });
        
        document.querySelectorAll('.floor-section').forEach(section => {
            const visibleRooms = Array.from(section.querySelectorAll('.room-card-wrapper')).filter(el => el.style.display !== 'none');
            section.style.display = visibleRooms.length === 0 ? 'none' : 'block';
        });

        const params = new URLSearchParams();
        if (searchVal) params.set('q', searchVal);
        if (floorVal !== 'Tất cả') params.set('floor', floorVal);
        if (typeVal !== 'Tất cả') params.set('type', typeVal);
        if (guestsVal !== 'Tất cả') params.set('guests', guestsVal);
        if (statusVal !== 'Tất cả') params.set('status', statusVal);
        history.replaceState({ roomFilters: true }, '', `${location.pathname}${params.size ? '?' + params : ''}`);
    }

    function selectFloorTab(button) {
        const floorFilter = document.getElementById('filter-floor');
        if (!floorFilter) return;
        floorFilter.value = button.dataset.floorTab;
        filterRooms();
    }

    function debounceFilterRooms() {
        clearTimeout(filterDebounceTimer);
        filterDebounceTimer = setTimeout(filterRooms, 180);
    }

    function restoreFiltersFromUrl() {
        const params = new URLSearchParams(location.search);
        const values = {
            'search-input': params.get('q') || '',
            'filter-floor': params.get('floor') || 'Tất cả',
            'filter-type': params.get('type') || 'Tất cả',
            'filter-guests': params.get('guests') || 'Tất cả',
            'filter-status': params.get('status') || 'Tất cả'
        };
        Object.entries(values).forEach(([id, value]) => {
            const field = document.getElementById(id);
            if (field && (id === 'search-input' || [...(field.options || [])].some(option => option.value === value))) field.value = value;
        });
        filterRooms();
    }

    function toggleLegendFilter(element) {
        document.getElementById('filter-status').value = element.dataset.statusVal;
        filterRooms();
    }

    function resetFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('filter-floor').value = 'Tất cả';
        document.getElementById('filter-type').value = 'Tất cả';
        document.getElementById('filter-guests').value = 'Tất cả';
        document.getElementById('filter-status').value = 'Tất cả';
        
        filterRooms();
    }

    window.addEventListener('DOMContentLoaded', () => {
        restoreFiltersFromUrl();
        focusRoomFromHash();
        const syncRoomBoardWhenSafe = () => {
            const activeField = document.activeElement?.matches('input, select, textarea, [contenteditable="true"]');
            if (document.hidden || isMultiSelectMode || activeField || document.querySelector('.modal.show')) return;
            refreshReceptionBoard();
        };
        window.setInterval(syncRoomBoardWhenSafe, 15000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) syncRoomBoardWhenSafe();
        });
        document.getElementById('qr-image-input')?.addEventListener('change', event => {
            const file = event.target.files?.[0];
            if (file) scanQrImage(file);
        });
        document.getElementById('qrScannerModal')?.addEventListener('hidden.bs.modal', stopQRScanner);
    });
    window.addEventListener('popstate', restoreFiltersFromUrl);

    let html5QrCode = null;
    let qrCameras = [];
    let antiGlareEnabled = false;
    let antiGlareRange = null;
    let defaultExposure = 0;

    function openQRScannerModal() {
        if (!INTERNAL_CAPABILITIES.bp_checkin_checkout) return;
        const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
        modal.show();

        setQrScanError('');
        setQrScanStatus('Đang tìm camera sau...');
        const imageInput = document.getElementById('qr-image-input');
        if (imageInput) imageInput.value = '';
        resetAntiGlareControl();
        
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length > 0) {
                qrCameras = devices;
                const selectEl = document.getElementById('qr-camera-select');
                selectEl.innerHTML = '';
                devices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.id;
                    option.text = device.label || `Camera ${index + 1}`;
                    selectEl.appendChild(option);
                });

                const preferredCamera = devices.find(device => /(back|rear|environment|sau)/i.test(device.label))
                    || devices[devices.length - 1];
                selectEl.value = preferredCamera.id;
                startScanning(preferredCamera.id);
            } else {
                document.getElementById('qr-camera-select').innerHTML = '<option value="">Không tìm thấy camera</option>';
                setQrScanStatus('Không tìm thấy camera. Bạn có thể dùng ảnh QR đã lưu.');
                showToast('Không tìm thấy thiết bị camera nào.', 'bg-danger');
            }
        }).catch(err => {
            document.getElementById('qr-camera-select').innerHTML = '<option value="">Không có quyền truy cập camera</option>';
            setQrScanStatus('Camera chưa được cấp quyền. Bạn có thể dùng ảnh QR đã lưu.');
            showToast('Không thể truy cập camera. Vui lòng cấp quyền.', 'bg-danger');
        });
    }

    async function startScanning(cameraId) {
        await stopQRScanner();
        initScanner(cameraId);
    }

    function initScanner(cameraId) {
        html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.start(
            cameraId,
            {
                fps: 10,
                qrbox: (width, height) => {
                    const minEdge = Math.min(width, height);
                    const size = Math.max(50, Math.floor(minEdge * 0.70));
                    return { width: size, height: size };
                }
            },
            handleDecodedQr,
            () => {
                // Ignore silent scanning noise
            }
        ).then(configureCameraForQr).catch(err => {
            setQrScanError('Không thể khởi động camera. Hãy chọn camera khác hoặc dùng ảnh QR.');
            setQrScanStatus('Camera chưa sẵn sàng.');
            console.error(err);
        });
    }

    function changeCamera(cameraId) {
        if (cameraId) {
            resetAntiGlareControl();
            startScanning(cameraId);
        }
    }

    async function stopQRScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            try {
                await html5QrCode.stop();
            } catch (err) {
                console.error(err);
            }
        }
    }

    function closeQRScanner() {
        stopQRScanner();
    }

    async function configureCameraForQr() {
        setQrScanStatus('Căn mã QR vào khung màu xanh.');
        setQrScanError('');

        try {
            const capabilities = html5QrCode.getRunningTrackCapabilities();
            const settings = html5QrCode.getRunningTrackSettings();
            const advanced = [];
            if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
                advanced.push({ focusMode: 'continuous' });
            }
            if (advanced.length > 0) {
                await html5QrCode.applyVideoConstraints({ advanced });
            }

            const exposure = capabilities.exposureCompensation;
            if (exposure && Number.isFinite(exposure.min) && Number.isFinite(exposure.max) && exposure.min < 0) {
                antiGlareRange = exposure;
                defaultExposure = Number.isFinite(settings.exposureCompensation) ? settings.exposureCompensation : 0;
                document.getElementById('qr-anti-glare-btn').hidden = false;
            }
        } catch (error) {
            resetAntiGlareControl();
        }
    }

    async function toggleAntiGlare() {
        if (!html5QrCode?.isScanning || !antiGlareRange) return;

        const button = document.getElementById('qr-anti-glare-btn');
        const nextEnabled = !antiGlareEnabled;
        const step = antiGlareRange.step || 0.1;
        const desired = nextEnabled ? -0.7 : defaultExposure;
        const clamped = Math.max(antiGlareRange.min, Math.min(antiGlareRange.max, desired));
        const exposureValue = antiGlareRange.min
            + Math.round((clamped - antiGlareRange.min) / step) * step;

        try {
            await html5QrCode.applyVideoConstraints({ advanced: [{ exposureCompensation: exposureValue }] });
            antiGlareEnabled = nextEnabled;
            button.setAttribute('aria-pressed', nextEnabled ? 'true' : 'false');
            button.querySelector('span').textContent = nextEnabled ? 'Đang giảm chói' : 'Giảm chói';
            setQrScanStatus(nextEnabled
                ? 'Đã giảm phơi sáng camera. Tiếp tục giữ QR trong khung.'
                : 'Đã trả camera về độ sáng ban đầu.');
        } catch (error) {
            setQrScanError('Camera này không hỗ trợ giảm chói. Hãy nghiêng mã QR hoặc dùng ảnh QR.');
        }
    }

    function resetAntiGlareControl() {
        antiGlareEnabled = false;
        antiGlareRange = null;
        defaultExposure = 0;
        const button = document.getElementById('qr-anti-glare-btn');
        if (!button) return;
        button.hidden = true;
        button.setAttribute('aria-pressed', 'false');
        button.querySelector('span').textContent = 'Giảm chói';
    }

    async function scanQrImage(file) {
        if (!file.type.startsWith('image/') || file.size > 10 * 1024 * 1024) {
            setQrScanError('Ảnh QR phải là tệp PNG, JPG hoặc WebP không quá 10 MB.');
            return;
        }

        setQrScanError('');
        setQrScanStatus('Đang đọc mã từ ảnh...');
        await stopQRScanner();

        try {
            if (!html5QrCode) html5QrCode = new Html5Qrcode('qr-reader');
            const decodedText = await html5QrCode.scanFile(file, true);
            await handleDecodedQr(decodedText);
        } catch (error) {
            setQrScanError('Không tìm thấy QR hợp lệ trong ảnh. Hãy chọn ảnh rõ, không bị cắt mép.');
            setQrScanStatus('Chưa đọc được mã QR.');
            const selectedCamera = document.getElementById('qr-camera-select')?.value;
            if (selectedCamera) startScanning(selectedCamera);
        } finally {
            const input = document.getElementById('qr-image-input');
            if (input) input.value = '';
        }
    }

    async function handleDecodedQr(decodedText) {
        if (navigator.vibrate) navigator.vibrate(100);
        await stopQRScanner();
        const modalEl = document.getElementById('qrScannerModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        processScannedText(decodedText);
    }

    function setQrScanStatus(message) {
        const status = document.getElementById('qr-scan-status');
        if (status) status.textContent = message;
    }

    function setQrScanError(message) {
        const error = document.getElementById('qr-scan-error');
        if (!error) return;
        error.textContent = message;
        error.style.display = message ? 'block' : 'none';
    }

    function processScannedText(text) {
        const normalizedText = String(text || '').trim();
        if (!/^(ROYAL|POSH|ROSA)-CHECKIN:/.test(normalizedText)) {
            showToast('Mã QR không đúng định dạng nhận phòng Royal.', 'bg-danger');
            return;
        }
        
        fetch(`{{ route('staff.reception.booking-by-scan') }}?payload=${encodeURIComponent(normalizedText)}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message || 'Lỗi liên kết dữ liệu'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showQRResult(data.booking, data.rooms, data.checkin_token);
            } else {
                showToast(data.message || 'Không tìm thấy thông tin đặt phòng.', 'bg-danger');
            }
        })
        .catch(error => {
            showToast(error.message || 'Không tìm thấy thông tin đặt phòng.', 'bg-danger');
        });
    }

    function showQRResult(booking, rooms, checkinToken) {
        document.getElementById('qr-guest-name').innerText = booking.customer_name;
        document.getElementById('qr-guest-phone').innerText = booking.customer_phone;
        document.getElementById('qr-guest-email').innerText = booking.customer_email || '---';

        const checkinDate = String(booking.check_in || '').slice(0, 10);
        const checkoutDate = String(booking.check_out || '').slice(0, 10);
        const formatCivilDate = value => {
            const [year, month, day] = value.split('-');
            return year && month && day ? `${day}/${month}/${year}` : 'Không xác định';
        };
        document.getElementById('qr-checkin-date').innerText = formatCivilDate(checkinDate);
        document.getElementById('qr-checkout-date').innerText = formatCivilDate(checkoutDate);
        
        document.getElementById('qr-guest-counts').innerText = `${booking.adult_count} Người lớn` + (booking.child_count > 0 ? `, ${booking.child_count} Trẻ em` : '');
        document.getElementById('qr-total-price').innerText = new Intl.NumberFormat('vi-VN').format(booking.total_price) + ' đ';
        
        const roomListContainer = document.getElementById('qr-room-list');
        roomListContainer.innerHTML = '';
        rooms.forEach(r => {
            const span = document.createElement('span');
            let statusBadge = '';
            if (r.room_status === 'available') {
                statusBadge = '<span class="badge bg-success ms-1" style="font-size:0.7rem;">Trống</span>';
            } else if (r.room_status === 'occupied') {
                statusBadge = '<span class="badge bg-primary ms-1" style="font-size:0.7rem;">Đang ở</span>';
            } else if (r.room_status === 'cleaning') {
                statusBadge = '<span class="badge bg-warning text-dark ms-1" style="font-size:0.7rem;">Đang dọn</span>';
            }
            
            span.className = 'badge bg-light text-dark border p-2 d-flex align-items-center';
            span.style.fontSize = '0.9rem';
            span.innerHTML = `<i class="fa-solid fa-door-closed me-1 text-primary"></i> Phòng ${r.room_number} ${statusBadge}`;
            roomListContainer.appendChild(span);
        });
        
        const todayParts = Object.fromEntries(new Intl.DateTimeFormat('en', {
            timeZone: 'Asia/Ho_Chi_Minh',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).formatToParts(new Date()).filter(part => part.type !== 'literal').map(part => [part.type, part.value]));
        const todayStr = `${todayParts.year}-${todayParts.month}-${todayParts.day}`;
        const isToday = checkinDate === todayStr;
        
        const alertContainer = document.getElementById('qr-status-alert');
        const btnAction = document.getElementById('btn-qr-action');
        
        alertContainer.style.display = 'flex';
        alertContainer.className = 'alert mt-4 mb-0 py-3 d-flex align-items-center gap-3';
        
        if (booking.status === 'confirmed') {
            if (isToday) {
                alertContainer.classList.add('alert-info');
                alertContainer.innerHTML = `<i class="fa-solid fa-circle-info fs-4 text-info"></i><div>Đơn đặt phòng hợp lệ. Có thể tiến hành nhận phòng nhanh cho toàn bộ ${rooms.length} phòng hôm nay.</div>`;
                btnAction.style.display = 'block';
                btnAction.onclick = () => performQuickCheckin(checkinToken);
            } else {
                alertContainer.classList.add('alert-warning');
                alertContainer.innerHTML = `<i class="fa-solid fa-triangle-exclamation fs-4 text-warning"></i><div>Cảnh báo: Ngày nhận phòng là ${formatCivilDate(checkinDate)} (không phải hôm nay).</div>`;
                btnAction.style.display = 'none';
            }
        } else if (booking.status === 'checked_in') {
            alertContainer.classList.add('alert-success');
            alertContainer.innerHTML = `<i class="fa-solid fa-circle-check fs-4 text-success"></i><div>Đơn đặt phòng này đã được nhận phòng trước đó.</div>`;
            btnAction.style.display = 'none';
        } else if (booking.status === 'completed' || booking.status === 'checked_out') {
            alertContainer.classList.add('alert-secondary');
            alertContainer.innerHTML = `<i class="fa-solid fa-circle-minus fs-4 text-secondary"></i><div>Đơn đặt phòng này đã hoàn tất thanh toán và trả phòng.</div>`;
            btnAction.style.display = 'none';
        }
        
        const resultModal = new bootstrap.Modal(document.getElementById('qrResultModal'));
        resultModal.show();
    }

    async function performQuickCheckin(checkinToken) {
        if (!await window.confirmOperation('Xác nhận nhận phòng nhanh cho tất cả các phòng thuộc đơn đặt này?')) return;
        
        fetch("{{ route('staff.reception.quick-checkin') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ token: checkinToken })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'bg-success');
                const modalEl = document.getElementById('qrResultModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                refreshReceptionBoard({ reselectCurrentRoom: false });
            } else {
                showToast(data.message || 'Lỗi nhận phòng nhanh.', 'bg-danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Lỗi hệ thống khi nhận phòng.', 'bg-danger');
        });
        }

    function toggleWaiveLateFee() {
        const waived = document.getElementById('waive-late-fee').checked;
        const baseTotal = Number(selectedRoomData.active_total_price || 0);
        const paid = Number(selectedRoomData.active_paid_amount || 0);
        const fee = waived ? 0 : currentLateFee;

        const lateFeeRow = document.getElementById('mo-late-fee-row');
        if (fee > 0) {
            lateFeeRow.hidden = false;
            document.getElementById('mo-late-fee').innerText = new Intl.NumberFormat('vi-VN').format(fee) + ' đ';
        } else {
            lateFeeRow.hidden = true;
        }
        document.getElementById('mo-remaining').innerText =
            new Intl.NumberFormat('vi-VN').format((baseTotal - paid) + fee) + ' đ';
    }

</script>
@endsection
