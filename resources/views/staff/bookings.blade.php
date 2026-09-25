@extends('layouts.dashboard')

@section('content')
@php
    // Extract unique floors, types, and counts
    $allFloors = array_keys($floors);
    sort($allFloors);

    $uniqueTypes = [];
    $uniqueCapacities = [];
    $counts = [
        'available' => 0,
        'occupied' => 0,
        'cleaning' => 0,
    ];

    foreach ($floors as $floor => $rooms) {
        foreach ($rooms as $room) {
            $uniqueTypes[$room['room_type_id']] = $room['type_name'];
            $uniqueCapacities[] = $room['max_guests'];
            
            $uiStatus = $room['status'];
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
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<style>
    /* Status Colors */
    .status-available { background: #e2f4e8; border: 1px solid #c3ebd2; }
    .status-available .room-icon { color: #2d9f58; }
    
    .status-occupied { background: #e6efff; border: 1px solid #cce0ff; }
    .status-occupied .room-icon { color: #2b6ff2; }

    .status-cleaning { background: #fff8e1; border: 1px solid #ffe082; }
    .status-cleaning .room-icon { color: #f59e0b; }
    
    /* Room Card */
    .room-card {
        border-radius: 12px;
        padding: 18px 15px;
        position: relative;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        height: 108px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .room-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 8px 16px rgba(0,0,0,0.06); 
    }
    .room-card.selected { 
        border: 2px solid #0d6efd !important; 
        box-shadow: 0 0 0 4px rgba(13,110,253,0.15); 
    }
    
    .room-number { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0; }
    .room-status-text { font-size: 0.8rem; font-weight: 600; margin: 2px 0 0 0; }
    .room-capacity { font-size: 0.75rem; color: #64748b; margin: 0; font-weight: 500; }
    .room-icon { position: absolute; top: 18px; right: 15px; font-size: 1.25rem; }

    /* Filter Bar */
    .filter-card { 
        background: white; 
        padding: 16px 20px; 
        border-radius: 12px; 
        border: 1px solid #e2e8f0; 
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 15px;
        align-items: flex-end;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .filter-label { 
        font-size: 0.775rem; 
        color: #475569; 
        font-weight: 600; 
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .filter-card select, .filter-card input { 
        border-radius: 8px; 
        border: 1px solid #cbd5e1; 
        padding: 8px 12px; 
        outline: none; 
        font-size: 0.875rem;
        color: #334155;
        background-color: #fff;
        transition: border-color 0.15s;
    }
    .filter-card select:focus, .filter-card input:focus {
        border-color: #0d6efd;
    }
    
    /* Interactive Legend Row */
    .legend-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
    }
    .legend-badge {
        padding: 10px 16px;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        border: 1px solid transparent;
        user-select: none;
    }
    .legend-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.03);
    }
    .legend-badge.active-filter {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.12) !important;
        font-weight: 700;
    }
    .legend-badge .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    /* Right Panel Details */
    .detail-img { width: 100%; height: 180px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; background: #eee; }
    .detail-row { display: flex; margin-bottom: 12px; font-size: 0.9rem; }
    .detail-label { width: 110px; color: #64748b; display: flex; align-items: center; gap: 8px; font-weight: 500; }
    .detail-value { flex: 1; font-weight: 600; color: #1e293b; }
    .btn-action { border-radius: 8px; padding: 10px; font-weight: 600; width: 100%; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.875rem;}

    .empty-panel-flex {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    /* QR Scanner UI overlay */
    .qr-scan-viewport-container #qr-reader video {
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
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
        animation: qr-pulse 2s infinite ease-in-out;
    }
    .qr-target-box .corner {
        position: absolute;
        width: 30px;
        height: 30px;
        border: 4px solid #ff9f43; /* Yellow-orange border */
        box-sizing: border-box;
    }
    .qr-target-box .corner.top-left {
        top: 0;
        left: 0;
        border-right: none;
        border-bottom: none;
        border-top-left-radius: 8px;
    }
    .qr-target-box .corner.top-right {
        top: 0;
        right: 0;
        border-left: none;
        border-bottom: none;
        border-top-right-radius: 8px;
    }
    .qr-target-box .corner.bottom-left {
        bottom: 0;
        left: 0;
        border-right: none;
        border-top: none;
        border-bottom-left-radius: 8px;
    }
    .qr-target-box .corner.bottom-right {
        bottom: 0;
        right: 0;
        border-left: none;
        border-top: none;
        border-bottom-right-radius: 8px;
    }
    @keyframes qr-pulse {
        0% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.98); }
        50% { opacity: 1; transform: translate(-50%, -50%) scale(1.02); }
        100% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.98); }
    }

    /* Operations board: compact, theme-aware and responsive. */
    .operations-bar { display:grid; grid-template-columns:minmax(240px,1fr) auto; gap:12px; align-items:center; margin-bottom:14px; }
    .operations-search { display:flex; align-items:center; gap:10px; min-height:44px; padding:0 13px; border:1px solid var(--line); border-radius:11px; background:var(--panel); color:var(--muted); }
    .operations-search:focus-within { border-color:var(--blue-deep); box-shadow:0 0 0 3px color-mix(in srgb,var(--blue-deep) 18%,transparent); }
    .operations-search input { width:100%; min-width:0; border:0!important; outline:0; background:transparent!important; color:var(--ink)!important; box-shadow:none!important; }
    .operations-actions { display:flex; align-items:center; gap:8px; }
    .control-toggle { min-height:42px; display:inline-flex; align-items:center; gap:8px; margin:0; padding:0 12px; border:1px solid var(--line); border-radius:10px; background:var(--panel-raised); color:var(--ink); font-size:.78rem; font-weight:600; white-space:nowrap; cursor:pointer; }
    .control-toggle .form-check-input { margin:0!important; flex:0 0 auto; }
    .operation-button { min-height:42px; display:inline-flex!important; align-items:center; justify-content:center; gap:8px; border-radius:10px!important; padding:0 13px!important; font-size:.78rem!important; font-weight:650!important; white-space:nowrap; box-shadow:none!important; }
    .legend-row { display:flex; flex-wrap:nowrap; gap:7px; margin-bottom:14px; overflow-x:auto; scrollbar-width:none; }
    .legend-row::-webkit-scrollbar { display:none; }
    .legend-badge { min-height:36px; flex:0 0 auto; padding:7px 11px; border-radius:9px; border:1px solid var(--line)!important; background:var(--panel)!important; color:var(--muted)!important; font-size:.72rem; font-weight:600; box-shadow:none; }
    .legend-badge:hover { transform:none; background:var(--panel-raised)!important; color:var(--ink)!important; }
    .legend-badge.active-filter { border-color:var(--blue-deep)!important; color:var(--ink)!important; box-shadow:0 0 0 2px color-mix(in srgb,var(--blue-deep) 18%,transparent)!important; }
    .filter-card { margin-bottom:18px; padding:12px; border:1px solid var(--line); border-radius:12px; background:var(--panel); box-shadow:none; }
    .filter-grid { grid-template-columns:repeat(4,minmax(120px,1fr)) auto; gap:9px; }
    .filter-label { color:var(--muted); font-size:.62rem; letter-spacing:.08em; }
    .filter-card select,.filter-card input { min-height:38px; padding:6px 10px; border-color:var(--line); border-radius:8px; background:var(--panel-raised); color:var(--ink); font-size:.78rem; }
    .floor-section { margin:0 0 14px!important; padding:16px; border:1px solid var(--line); border-radius:14px; background:var(--panel); }
    .floor-section>h6 { margin:0 0 12px!important; color:var(--ink)!important; font-size:.76rem; letter-spacing:.04em; }
    .room-card { height:104px; padding:14px; border-radius:11px; border:1px solid var(--line)!important; background:var(--panel-raised)!important; box-shadow:none; overflow:hidden; }
    .room-card::before { content:""; position:absolute; inset:0 auto 0 0; width:3px; background:#6f7680; }
    .room-card.status-available::before { background:#46a273; }
    .room-card.status-occupied::before { background:#5e8fc0; }
    .room-card.status-cleaning::before { background:#d6a13d; }
    .internal-shell .room-card.status-available,.internal-shell .room-card.status-occupied,.internal-shell .room-card.status-cleaning { background:var(--panel-raised)!important; }
    .room-card:hover { transform:translateY(-2px); border-color:color-mix(in srgb,var(--blue-deep) 50%,var(--line))!important; box-shadow:0 10px 24px rgba(0,0,0,.08); }
    .room-card.selected { border-color:var(--blue-deep)!important; box-shadow:0 0 0 3px color-mix(in srgb,var(--blue-deep) 18%,transparent)!important; }
    .room-number,.room-status-text,.room-capacity { color:var(--ink); }
    .room-number { font-size:1.08rem; }
    .room-status-text,.room-capacity { color:var(--muted); font-size:.68rem; }
    .room-icon { top:14px; right:14px; font-size:1rem; }
    .right-panel { background:var(--panel)!important; color:var(--ink); }
    .detail-img { height:150px; border-radius:11px; margin-bottom:16px; }
    .detail-label { color:var(--muted); font-size:.76rem; }
    .detail-value { color:var(--ink); font-size:.8rem; }
    .floor-switcher { display:flex; align-items:center; gap:7px; margin:0 0 12px; padding:6px; overflow-x:auto; border:1px solid var(--line); border-radius:12px; background:var(--panel); scrollbar-width:none; }
    .floor-switcher::-webkit-scrollbar { display:none; }
    .floor-switcher button { min-height:38px; flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; padding:0 12px; border:1px solid transparent; border-radius:9px; background:transparent; color:var(--muted); font-size:.76rem; font-weight:600; white-space:nowrap; }
    .floor-switcher button span { display:grid; min-width:21px; height:21px; place-items:center; padding:0 5px; border-radius:7px; background:var(--panel-raised); color:var(--muted); font-size:.66rem; font-variant-numeric:tabular-nums; }
    .floor-switcher button:hover { color:var(--ink); background:var(--panel-raised); }
    .floor-switcher button.is-active { background:var(--ink); color:var(--bg); }
    .floor-switcher button.is-active span { background:color-mix(in srgb,var(--bg) 14%,transparent); color:inherit; }
    .floor-section { position:relative; overflow:hidden; }
    .floor-section::after { content:""; position:absolute; top:0; right:0; width:160px; height:110px; pointer-events:none; opacity:.42; background:radial-gradient(circle at 80% 0,color-mix(in srgb,var(--blue-deep) 18%,transparent),transparent 70%); }
    .floor-section>h6 { display:flex; align-items:center; gap:8px; }
    .floor-section>h6::after { content:""; height:1px; flex:1; margin-left:8px; background:var(--line); }
    .legend-badge strong { min-width:18px; text-align:center; font-size:.68rem; font-variant-numeric:tabular-nums; }
    .room-card.status-soon_to_checkin::before,.room-card.status-soon_to_checkout::before { background:#ad91e8; }
    .room-card.status-maintenance::before,.room-card.status-overdue::before { background:#e78383; }
    .room-card.status-booked::before { background:#e6b85c; }
    .internal-shell[data-theme="dark"] .room-card.status-soon_to_checkin,.internal-shell[data-theme="dark"] .room-card.status-soon_to_checkout { border-color:#3b3150!important; }
    .internal-shell[data-theme="dark"] .room-card.status-maintenance { border-color:#503035!important; }
    @media(max-width:1050px){.operations-bar{grid-template-columns:1fr}.operations-actions{justify-content:flex-start}.filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-grid>button{grid-column:1/-1}.action-label{display:none}.operation-button{width:42px;padding:0!important}.control-toggle span,.control-toggle input{position:absolute;opacity:0;pointer-events:none}.control-toggle:has(input:checked){background:var(--ink);color:var(--bg);border-color:var(--ink)}}
    @media(max-width:640px){.operations-bar{gap:9px}.operations-actions{display:flex}.control-toggle{justify-content:center;width:42px;padding:0}.filter-grid{grid-template-columns:1fr 1fr}.legend-badge{font-size:.68rem}.floor-section{padding:12px}.room-card{height:96px}.floor-switcher{margin-inline:-2px}.floor-switcher button{padding-inline:10px}}
    .operations-bar { grid-template-columns:minmax(0,1fr) auto!important; }
    .operations-more { position:relative; z-index:20; }
    .operations-more summary { display:flex;align-items:center;gap:9px;min-height:44px;padding:0 15px;border:1px solid var(--line);border-radius:999px;background:var(--panel);color:var(--ink);font-size:.8rem;font-weight:600;cursor:pointer;list-style:none;white-space:nowrap; }
    .operations-more summary::-webkit-details-marker { display:none; }
    .operations-more[open] summary,.operations-more summary:hover { background:var(--panel-raised); }
    .operations-more summary .fa-chevron-down { font-size:.65rem;transition:transform .2s ease; }
    .operations-more[open] summary .fa-chevron-down { transform:rotate(180deg); }
    .operations-more__panel { position:absolute;top:calc(100% + 8px);right:0;width:min(490px,calc(100vw - 110px));padding:17px;border:1px solid var(--line);border-radius:18px;background:var(--panel);box-shadow:0 24px 55px rgba(23,43,59,.16); }
    .operations-more .operations-actions { flex-wrap:wrap;margin-bottom:14px; }
    .operations-more .filter-card { margin:0;padding:0;border:0!important;background:transparent!important; }
    .operations-more .filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)) auto!important;align-items:end; }
    .operations-more .filter-grid>button { grid-column:auto!important;min-height:38px;border-radius:999px; }
    .operations-more .operation-button,.operations-more .control-toggle { border-radius:999px!important; }
    .operations-more .operation-button,.operations-more .control-toggle { width:auto!important;padding:0 13px!important; }
    .operations-more .control-toggle input,.operations-more .control-toggle span { position:static!important;opacity:1!important;pointer-events:auto!important; }
    .legend-row { flex-wrap:wrap;gap:8px;overflow:visible; }
    .legend-row .legend-badge { min-height:38px;padding:7px 14px;border-radius:999px!important;display:inline-flex;align-items:center;gap:7px;cursor:pointer; }
    .legend-row .legend-badge.active-filter { background:var(--ink)!important;color:var(--bg)!important;border-color:var(--ink)!important;box-shadow:none!important; }
    .legend-row .dot { width:7px;height:7px;border-radius:50%; }
    .status-dot--available { background:#4aa57c; }.status-dot--occupied { background:#71a9d1; }.status-dot--cleaning { background:#dbb662; }
    .internal-shell .staff-workspace .room-card:hover { border-color:var(--line)!important;box-shadow:0 10px 24px rgba(22,42,58,.12)!important; }
    .internal-shell .staff-workspace .room-card.selected { outline:0!important;border:1px solid var(--line)!important;background:var(--panel)!important;box-shadow:0 16px 34px rgba(19,47,67,.18)!important;animation:room-select-pop .28s cubic-bezier(.2,.8,.3,1) both; }
    @keyframes room-select-pop { from { transform:translateY(2px) scale(.985) } to { transform:translateY(-2px) scale(1) } }
    @media(max-width:640px){.operations-bar{grid-template-columns:minmax(0,1fr) auto!important}.operations-more summary{padding:0 11px;font-size:0}.operations-more summary i{font-size:.85rem}.operations-more__panel{padding:14px}.operations-more .filter-grid{grid-template-columns:1fr 1fr!important}.operations-more .filter-grid>button{grid-column:1/-1!important}.legend-row .legend-badge{font-size:.68rem}}
    @media(prefers-reduced-motion:reduce){.internal-shell .staff-workspace .room-card.selected{animation:none}}
</style>

<!-- Main Center Column -->
<main class="main-content">
    <div class="operations-bar" aria-label="Tìm và quản lý phòng">
        <label class="operations-search" for="search-input">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="search-input" placeholder="Tìm phòng, khách hoặc số điện thoại" oninput="debounceFilterRooms()">
        </label>
        <details class="operations-more">
            <summary><i class="fa-solid fa-sliders" aria-hidden="true"></i> Tùy chọn <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
            <div class="operations-more__panel">
                <div class="operations-actions">
                    <label class="control-toggle" for="multi-select-toggle">
                        <input class="form-check-input" type="checkbox" id="multi-select-toggle" onchange="toggleMultiSelectMode()">
                        <span>Chọn nhiều</span>
                    </label>
                    <a href="{{ route('staff.cancellations') }}" class="btn btn-outline-secondary operation-button">Hoàn tiền @if(($pendingRefunds ?? 0) > 0)<strong>{{ $pendingRefunds }}</strong>@endif</a>
                    <button type="button" class="btn btn-primary operation-button" onclick="openQRScannerModal()">Quét QR</button>
                </div>
                <div class="filter-card">
                    <div class="filter-grid">
                        <div class="filter-group visually-hidden">
                            <label for="filter-floor">Tầng</label>
                            <select id="filter-floor" aria-label="Lọc theo tầng" onchange="filterRooms()">
                                <option value="Tất cả">Tất cả tầng</option>
                                @foreach ($allFloors as $fl)<option value="{{ $fl }}">Tầng {{ $fl }}</option>@endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label" for="filter-type">Loại phòng</label>
                            <select id="filter-type" onchange="filterRooms()">
                                <option value="Tất cả">Mọi loại phòng</option>
                                @foreach ($uniqueTypes as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label" for="filter-guests">Sức chứa</label>
                            <select id="filter-guests" onchange="filterRooms()">
                                <option value="Tất cả">Mọi sức chứa</option>
                                @foreach ($uniqueCapacities as $cap)<option value="{{ $cap }}">{{ $cap }} người</option>@endforeach
                                <option value="5+">5+ người</option>
                            </select>
                        </div>
                        <div class="filter-group visually-hidden">
                            <label for="filter-status">Trạng thái</label>
                            <select id="filter-status" onchange="filterRooms()">
                                <option value="Tất cả">Tất cả</option>
                                <option value="available">Trống</option>
                                <option value="occupied">Đang ở</option>
                                <option value="cleaning">Dọn phòng</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">Đặt lại</button>
                    </div>
                </div>
            </div>
        </details>
    </div>

    <div class="legend-row" aria-label="Lọc trạng thái phòng">
        <button type="button" class="legend-badge active-filter" data-status-val="Tất cả" onclick="toggleLegendFilter(this)">Tất cả <strong>{{ array_sum(array_map('count', $floors)) }}</strong></button>
        <button type="button" class="legend-badge" data-status-val="available" onclick="toggleLegendFilter(this)"><span class="dot status-dot--available"></span> Trống <strong id="count-available">{{ $counts['available'] }}</strong></button>
        <button type="button" class="legend-badge" data-status-val="occupied" onclick="toggleLegendFilter(this)"><span class="dot status-dot--occupied"></span> Đang ở <strong id="count-occupied">{{ $counts['occupied'] }}</strong></button>
        <button type="button" class="legend-badge" data-status-val="cleaning" onclick="toggleLegendFilter(this)"><span class="dot status-dot--cleaning"></span> Dọn phòng <strong id="count-cleaning">{{ $counts['cleaning'] }}</strong></button>
    </div>

    <!-- Floors & Rooms Grid -->
    <div id="room-grid-container">
        @foreach ($floors as $floor => $rooms)
            <div class="floor-section mb-4" data-floor-num="{{ $floor }}">
                <h6 class="fw-bold text-dark mb-3 mt-2"><i class="fa-solid fa-layer-group text-primary me-2"></i> Tầng {{ $floor }}</h6>
                <div class="row g-3">
                    @foreach ($rooms as $room)
                        @php
                            [$statusText, $icon] = match ($room['status']) {
                                'soon_to_checkin' => ['Sắp nhận phòng', 'fa-clock'],
                                'occupied' => ['Đang lưu trú', 'fa-user-check'],
                                'soon_to_checkout' => ['Sắp trả phòng', 'fa-right-from-bracket'],
                                'cleaning' => ['Đang dọn dẹp', 'fa-broom'],
                                'maintenance' => ['Bảo trì', 'fa-screwdriver-wrench'],
                                'booked' => ['Đã đặt', 'fa-calendar-check'],
                                'overdue' => ['Quá giờ trả', 'fa-clock'],
                                default => ['Đang trống', 'fa-door-open'],
                            };
                            
                            $room['ui_status'] = $room['status'];
                            $room['status_text'] = $statusText;
                        @endphp
                        <div class="col-md-4 col-sm-6 col-xl-2 col-xxl-2 room-card-wrapper">
                            <div class="room-card status-{{ $room['status'] }}" role="button" tabindex="0" aria-label="Phòng {{ $room['room_number'] }}" onkeydown="if(event.key === 'Enter' || event.key === ' '){event.preventDefault();selectRoom(this)}"
                                 data-floor="{{ $room['floor'] }}"
                                 data-type="{{ $room['room_type_id'] }}"
                                 data-guests="{{ $room['max_guests'] }}"
                                 data-status="{{ $room['status'] }}"
                                 data-has-booking="{{ $room['has_today_booking'] ? '1' : '0' }}"
                                 data-search="{{ htmlspecialchars(strtolower($room['room_number'] . ' ' . $room['type_name'] . ' ' . ($room['customer_name'] ?? '') . ' ' . ($room['customer_phone'] ?? ''))) }}"
                                 id="room-card-{{ $room['id'] }}"
                                 data-room="{{ json_encode($room) }}"
                                 onclick="selectRoom(this)">
                                
                                <i class="fa-solid {{ $icon }} room-icon"></i>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="room-number">{{ htmlspecialchars($room['room_number']) }}</h5>
                                        @if ($room['has_today_booking'])
                                            <span class="badge bg-danger text-white rounded-pill" style="font-size: 0.65rem; padding: 2px 6px;">Đã đặt</span>
                                        @endif
                                    </div>
                                    <p class="room-status-text">{{ htmlspecialchars($statusText) }}</p>
                                </div>
                                <p class="room-capacity">{{ $room['max_guests'] }} người</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</main>

<!-- Right Sidebar (Details) -->
<aside class="right-panel shadow-sm" id="room-detail-panel" style="display: none; flex-direction: column;">
    <div class="window-controls window-controls--panel" role="group" aria-label="Điều khiển thông tin phòng">
        <button class="window-control window-control--close" type="button" onclick="closeDetailPanel()" aria-label="Đóng thông tin phòng"></button>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0" id="detail-title">Thông tin phòng</h5>
    </div>
    
    <!-- Single room details -->
    <div id="single-room-container" style="display: block; width: 100%;">
        <img src="" class="detail-img" id="detail-img" alt="Room Image">

        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-bed text-muted"></i> Loại phòng</div>
            <div class="detail-value" id="detail-type">---</div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-users text-muted"></i> Sức chứa</div>
            <div class="detail-value" id="detail-capacity">--- người</div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-circle-dollar-to-slot text-muted"></i> Giá phòng</div>
            <div class="detail-value text-primary fs-5" id="detail-price">--- đ / đêm</div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><i class="fa-solid fa-circle-info text-muted"></i> Trạng thái</div>
            <div class="detail-value" id="detail-status-badge">
                <span class="badge rounded-pill px-3">---</span>
            </div>
        </div>
        <div class="detail-row" id="detail-booking-row" style="display: none;">
            <div class="detail-label"><i class="fa-solid fa-calendar-day text-danger"></i> Lịch đặt hôm nay</div>
            <div class="detail-value text-danger fw-bold" id="detail-booking-text">ĐÃ ĐẶT</div>
        </div>
        <div class="detail-row mb-4">
            <div class="detail-label"><i class="fa-solid fa-wifi text-muted"></i> Tiện ích</div>
            <div class="detail-value" id="detail-amenities" style="font-size: 0.825rem; line-height: 1.4; font-weight: 500; color: #475569;">---</div>
        </div>

        <div id="occupied-booking-info" style="display: none;">
            <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-address-card text-primary me-2"></i>Thông tin khách đang ở</h6>
                    <span class="badge bg-primary rounded-pill" id="occupied-booking-id">---</span>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-user text-muted"></i> Khách</div>
                    <div class="detail-value" id="occupied-customer-name">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-phone text-muted"></i> SĐT</div>
                    <div class="detail-value" id="occupied-customer-phone">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-envelope text-muted"></i> Email</div>
                    <div class="detail-value" id="occupied-customer-email">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-calendar-check text-muted"></i> Nhận</div>
                    <div class="detail-value" id="occupied-checkin">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-calendar-xmark text-muted"></i> Trả</div>
                    <div class="detail-value text-danger" id="occupied-checkout">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-users text-muted"></i> Số khách</div>
                    <div class="detail-value" id="occupied-guests">---</div>
                </div>
                <div class="detail-row mb-0">
                    <div class="detail-label"><i class="fa-solid fa-money-bill-wave text-muted"></i> Tổng tiền</div>
                    <div class="detail-value text-primary fs-5" id="occupied-total-price">---</div>
                </div>
            </div>
        </div>

        <div id="today-booking-info" style="display: none;">
            <div class="p-3 mb-3" style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-day text-warning me-2"></i>Thông tin lịch đặt hôm nay</h6>
                    <span class="badge bg-warning text-dark rounded-pill" id="today-booking-id">---</span>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-user text-muted"></i> Khách đặt</div>
                    <div class="detail-value" id="today-customer-name">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-phone text-muted"></i> SĐT</div>
                    <div class="detail-value" id="today-customer-phone">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-envelope text-muted"></i> Email</div>
                    <div class="detail-value" id="today-customer-email">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-calendar-check text-muted"></i> Nhận</div>
                    <div class="detail-value" id="today-checkin">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-calendar-xmark text-muted"></i> Trả</div>
                    <div class="detail-value text-danger" id="today-checkout">---</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-users text-muted"></i> Số khách</div>
                    <div class="detail-value" id="today-guests">---</div>
                </div>
                <div class="detail-row mb-0">
                    <div class="detail-label"><i class="fa-solid fa-money-bill-wave text-muted"></i> Tổng tiền</div>
                    <div class="detail-value text-primary fs-5" id="today-total-price">---</div>
                </div>
            </div>
        </div>
        
        <hr class="my-3 text-muted">

        <!-- Operational Business Buttons -->
        <div class="mt-2">
            <h6 class="fw-bold mb-3 text-dark" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">Nghiệp vụ phòng</h6>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-success btn-action" id="btn-action-available" onclick="triggerStatusUpdate('available')">
                    <i class="fa-solid fa-door-open"></i> Trả phòng
                </button>
                <button class="btn btn-outline-primary btn-action" id="btn-action-occupied" onclick="handleSingleCheckin()">
                    <i class="fa-solid fa-user-check"></i> Check-in (Nhận phòng)
                </button>
                <button class="btn btn-outline-info btn-action" id="btn-action-extend" onclick="openExtendStayModal()">
                    <i class="fa-solid fa-calendar-plus"></i> Gia hạn lưu trú
                </button>
                <button class="btn btn-outline-success btn-action" id="btn-action-cleaning-done" onclick="triggerStatusUpdate('available')">
                    <i class="fa-solid fa-check"></i> Đã dọn dẹp xong
                </button>
                <button class="btn btn-outline-warning btn-action text-dark" id="btn-action-hold" onclick="handleSingleHold()">
                    <i class="fa-solid fa-clock"></i> Giữ chỗ phòng
                </button>
            </div>
        </div>
    </div>

    <!-- Multi-room list for walk-in guest -->
    <div id="multi-room-container" style="display: none; flex-direction: column; width: 100%; height: 100%;">
        <div class="alert alert-info py-2" style="font-size: 0.85rem;">
            <i class="fa-solid fa-circle-info me-1"></i> Đang ở chế độ chọn nhiều phòng cho khách vãng lai.
        </div>
        
        <p class="text-muted small">Phòng đã chọn (<strong id="multi-room-count" class="text-dark">0</strong>):</p>
        <div id="multi-room-list" class="mb-4" style="max-height: 250px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; background: #f8fafc; display: flex; flex-direction: column; gap: 8px;">
            <!-- Render via JS -->
        </div>

        <div class="d-grid gap-2 mt-auto">
            <button class="btn btn-primary btn-action py-2.5" onclick="openWalkinCheckinModal('now')">
                <i class="fa-solid fa-user-plus me-2"></i>Check-in Khách Vãng Lai
            </button>
            <button class="btn btn-warning btn-action text-dark py-2.5" id="btn-multi-hold" onclick="openWalkinCheckinModal('hold')">
                <i class="fa-solid fa-clock me-2"></i>Giữ Chỗ Các Phòng
            </button>
        </div>
    </div>
</aside>

<!-- Right Sidebar Placeholder -->
<div class="right-panel empty-panel-flex text-muted shadow-sm" id="empty-detail-panel">
    <div class="bg-light p-4 rounded-circle mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
        <i class="fa-solid fa-bed fs-1 text-secondary" style="opacity: 0.5;"></i>
    </div>
    <h6 class="fw-bold text-dark">Chưa chọn phòng</h6>
    <p class="text-center px-4 fs-7 text-muted" style="font-size: 0.8rem;">Hãy chọn một phòng bất kỳ trên sơ đồ để xem thông tin chi tiết và thao tác nghiệp vụ nhanh.</p>
</div>

<!-- Checkout Scope Modal -->
<div class="modal fade" id="checkoutScopeModal" tabindex="-1" aria-labelledby="checkoutScopeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'trả phòng'])
            <div class="modal-header bg-success text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="checkoutScopeModalLabel"><i class="fa-solid fa-door-open me-2"></i>Trả phòng</h5>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="fw-bold fs-5 mb-2" id="checkout-room-label">---</div>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Chọn phạm vi trả phòng cho booking đang ở.</p>
            </div>
            <div class="modal-footer px-4 py-3 d-grid gap-2" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button type="button" class="btn btn-outline-success fw-bold" onclick="submitCheckout('room')" style="border-radius: 8px;">
                    <i class="fa-solid fa-door-open me-1"></i> Chỉ trả phòng này
                </button>
                <button type="button" class="btn btn-success fw-bold" onclick="submitCheckout('booking')" style="border-radius: 8px;">
                    <i class="fa-solid fa-people-roof me-1"></i> Trả toàn bộ booking
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Hủy</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Payment Modal -->
<div class="modal fade" id="checkoutPaymentModal" tabindex="-1" aria-labelledby="checkoutPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'thanh toán trả phòng'])
            <div class="modal-header bg-success text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="checkoutPaymentModalLabel"><i class="fa-solid fa-receipt me-2"></i>Trả phòng & Thanh toán</h5>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="bg-white rounded p-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Khách hàng</span><strong id="mo-customer">---</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Phòng</span><strong id="mo-room">---</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Check-in</span><strong id="mo-checkin">---</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Ngày trả dự kiến</span><strong id="mo-checkout">---</strong></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Tổng tiền</span><strong class="text-primary fs-5" id="mo-total">0 đ</strong></div>
                    <div class="d-flex justify-content-between mb-1" id="mo-late-fee-row" hidden>
                        <span class="text-warning"><i class="fa-solid fa-clock me-1"></i>Phụ thu trả muộn</span>
                        <strong class="text-warning" id="mo-late-fee">0 đ</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Đã đặt cọc</span><strong class="text-success" id="mo-paid">0 đ</strong></div>
                    <div class="d-flex justify-content-between"><span class="fw-bold">Còn lại cần thu</span><strong class="text-danger fs-5" id="mo-remaining">0 đ</strong></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Phương thức thanh toán</label>
                    <div class="d-flex flex-column gap-2">
                        @foreach([
                            ['cash',    '#28a745', 'Tiền mặt',           'Nhân viên thu tiền mặt trực tiếp'],
                            ['vietqr',  '#1565C0', 'Chuyển khoản VietQR','Quét mã QR · Hỗ trợ tất cả ngân hàng'],
                            ['momo',    '#ae2070', 'Ví MoMo',             'Thanh toán qua ứng dụng MoMo'],
                            ['zalopay', '#0068ff', 'ZaloPay',             'Thanh toán qua ví ZaloPay'],
                            ['vnpay',   '#e53935', 'VNPay',               'Thanh toán qua cổng VNPay'],
                        ] as [$val, $color, $name, $desc])
                        <label class="payment-option-staff d-flex align-items-center gap-3 p-3 rounded border"
                            style="cursor:pointer;transition:all .2s;background:#fff;"
                            for="staff_method_{{ $val }}">
                            <input type="radio" name="staff_payment_method" id="staff_method_{{ $val }}"
                                value="{{ $val }}" onchange="selectStaffMethod(this)"
                                style="width:18px;height:18px;accent-color:{{ $color }}">
                            <div>
                                <div class="fw-bold" style="font-size:0.9rem;">{{ $name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $desc }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <div class="form-check mb-2" id="late-fee-waiver" hidden>
                    <input class="form-check-input" type="checkbox" id="waive-late-fee" onchange="toggleWaiveLateFee()">
                    <label class="form-check-label text-muted" for="waive-late-fee">
                        Miễn phụ thu trả muộn
                    </label>
                </div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Huỷ</button>
                <button type="button" class="btn btn-success fw-bold" onclick="submitCheckoutPayment()" style="border-radius: 8px;">
                    <i class="fa-solid fa-check me-1"></i> Xác nhận thanh toán & Trả phòng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extend Stay Modal -->
<div class="modal fade" id="extendStayModal" tabindex="-1" aria-labelledby="extendStayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'gia hạn lưu trú'])
            <div class="modal-header bg-info text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="extendStayModalLabel"><i class="fa-solid fa-calendar-plus me-2"></i>Gia hạn lưu trú</h5>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="mb-3">
                    <div class="text-muted small fw-semibold mb-1">Phòng</div>
                    <div class="fw-bold fs-5" id="extend-room-label">---</div>
                </div>
                <div class="mb-3"><label for="extend-mode" class="form-label fw-semibold">Hình thức gia hạn</label><select id="extend-mode" class="form-select" onchange="syncExtendMode()"><option value="hours">Theo giờ · 200.000đ/giờ</option><option value="days">Theo ngày · giá phòng hiện tại</option></select></div>
                <div class="mb-3"><label for="extend-amount" class="form-label fw-semibold" id="extend-amount-label">Số giờ gia hạn</label><input type="number" id="extend-amount" class="form-control" min="1" max="12" value="1" required><div class="form-text" id="extend-help">Tối đa 12 giờ. Phí 200.000đ cho mỗi giờ.</div></div>
                <div class="alert alert-info mb-0 py-2" style="font-size: 0.875rem;">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Hệ thống sẽ kiểm tra lịch trùng trước khi gia hạn theo ngày.
                </div>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Hủy</button>
                <button type="button" class="btn btn-info text-white px-4 fw-bold" onclick="submitExtendStay()" style="border-radius: 8px;">
                    <i class="fa-solid fa-check me-1"></i> Xác nhận gia hạn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Walk-in Check-in Modal -->
<div class="modal fade" id="walkinCheckinModal" tabindex="-1" aria-labelledby="walkinCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'check-in khách vãng lai'])
            <div class="modal-header bg-primary text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="walkinCheckinModalTitle"><i class="fa-solid fa-user-plus me-2"></i>Check-in Khách Vãng Lai</h5>
            </div>
            <div class="modal-body p-4">
                <form id="walkin-checkin-form">
                    <input type="hidden" id="walkin-type" value="now">
                    <div class="mb-3 p-3 bg-light rounded" style="font-size: 0.9rem;">
                        <span class="text-muted fw-bold" id="modal-rooms-label">Các phòng Check-in:</span>
                        <div id="modal-rooms-list" class="mt-2 d-flex flex-wrap gap-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên khách hàng <span class="text-danger">*</span></label>
                        <input type="text" id="walkin-name" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" id="walkin-phone" class="form-control" placeholder="0901234567" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email (Không bắt buộc)</label>
                        <input type="email" id="walkin-email" class="form-control" placeholder="nguyenvana@gmail.com">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold">Số người lớn <span class="text-danger">*</span></label>
                            <input type="number" id="walkin-adults" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold">Số trẻ em</label>
                            <input type="number" id="walkin-children" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ngày trả phòng <span class="text-danger">*</span></label>
                        <input type="date" id="walkin-checkout" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #eee;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Hủy</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitWalkinCheckin()" style="border-radius: 8px;">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- Pre-booked Check-in Confirmation Modal -->
<div class="modal fade" id="prebookCheckinModal" tabindex="-1" aria-labelledby="prebookCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'xác nhận lịch đặt trước'])
            <div class="modal-header bg-primary text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="prebookCheckinModalLabel"><i class="fa-solid fa-address-card me-2"></i>Xác nhận Lịch Đặt Trước</h5>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <input type="hidden" id="prebook-id">
                
                <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px; background: #ffffff;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-light p-3 d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-user text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small fw-medium">Khách hàng</div>
                            <h5 class="fw-bold mb-0 text-dark" id="prebook-name">---</h5>
                        </div>
                    </div>
                    
                    <div class="row g-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                        <div class="col-6">
                            <span class="text-muted small d-block">Số điện thoại</span>
                            <strong class="text-dark" id="prebook-phone">---</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Email</span>
                            <span class="text-dark fw-semibold" id="prebook-email" style="font-size: 0.85rem; word-break: break-all;">---</span>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm p-3" style="border-radius: 12px; background: #ffffff;">
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1"><i class="fa-solid fa-users me-1 text-secondary"></i> Số lượng khách</span>
                        <div class="fw-bold text-dark" id="prebook-guests">---</div>
                    </div>
                    
                    <div class="row g-2 border-top pt-3" style="border-color: #f1f5f9 !important;">
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-calendar-days me-1 text-secondary"></i> Ngày trả phòng</span>
                            <strong class="text-dark" id="prebook-checkout">---</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-money-bill-wave me-1 text-secondary"></i> Tổng số tiền</span>
                            <strong class="text-primary fs-5" id="prebook-price">---</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Hủy</button>
                <button type="button" class="btn btn-primary px-4" onclick="confirmPrebookCheckin()" style="border-radius: 8px; font-weight: 500;">Xác nhận Check-in</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'quét QR phòng'])
            <div class="modal-header bg-dark text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="qrScannerModalLabel"><i class="fa-solid fa-qrcode me-2 text-warning"></i>Quét QR phòng</h5>
            </div>
            <div class="modal-body p-4 text-center" style="background: #f8fafc;">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold text-secondary">Chọn Camera:</label>
                    <select id="qr-camera-select" class="form-select" style="border-radius: 8px; font-size: 0.85rem;" onchange="changeCamera(this.value)">
                        <option value="">Đang dò tìm camera...</option>
                    </select>
                </div>

                <div class="qr-scan-viewport-container mb-3 position-relative d-inline-block shadow-sm" style="width: 100%; max-width: 380px; aspect-ratio: 1; border-radius: 12px; overflow: hidden; background: #000;">
                    <div id="qr-reader" style="width: 100%; height: 100%;"></div>
                    <div class="qr-target-box">
                        <div class="corner top-left"></div>
                        <div class="corner top-right"></div>
                        <div class="corner bottom-left"></div>
                        <div class="corner bottom-right"></div>
                    </div>
                </div>

                <p class="text-secondary small fw-medium mt-2 mb-0">Vui lòng đặt mã QR phòng vào trong khung hình</p>
                <div id="qr-scan-error" class="text-danger small mt-1" style="display: none;"></div>

                <div class="mt-3 text-start">
                    <label class="form-label small fw-bold text-secondary">Không quét được? Nhập mã đặt phòng:</label>
                    <div class="input-group">
                        <input type="number" id="manual-booking-id" class="form-control" min="1" placeholder="VD: 12" style="border-radius: 8px 0 0 8px;">
                        <button type="button" class="btn btn-primary" onclick="submitManualBookingId()" style="border-radius: 0 8px 8px 0;">Tìm</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal" style="border-radius: 8px;" onclick="closeQRScanner()">Hủy bỏ</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Result Modal -->
<div class="modal fade" id="qrResultModal" tabindex="-1" aria-labelledby="qrResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
            @include('client.partials.window-controls', ['controls' => ['close'], 'dismiss' => 'modal', 'class' => 'window-controls--modal', 'label' => 'kết quả quét QR'])
            <div class="modal-header bg-primary text-white py-3 px-4" style="border: none;">
                <h5 class="modal-title fw-bold" id="qrResultModalLabel"><i class="fa-solid fa-square-poll-horizontal me-2"></i>Kết quả quét mã QR</h5>
            </div>
            <div class="modal-body p-4" style="background: #f8fafc;">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff;">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-address-card text-primary me-2"></i>Thông tin khách hàng</h6>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Họ và Tên</span>
                                <strong class="text-dark fs-5" id="qr-guest-name">---</strong>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Số điện thoại</span>
                                <strong class="text-dark" id="qr-guest-phone">---</strong>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Email</span>
                                <span class="text-dark fw-semibold" id="qr-guest-email" style="font-size: 0.9rem; word-break: break-all;">---</span>
                            </div>
                            <div class="row g-2 border-top pt-3 mt-1">
                                <div class="col-6">
                                    <span class="text-muted small d-block">Ngày nhận phòng</span>
                                    <strong class="text-dark" id="qr-checkin-date">---</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Ngày trả phòng</span>
                                    <strong class="text-dark" id="qr-checkout-date">---</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: #ffffff; display: flex; flex-direction: column;">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-bed text-primary me-2"></i>Thông tin phòng & Thanh toán</h6>
                            <div class="mb-3 flex-grow-1">
                                <span class="text-muted small d-block mb-2">Danh sách phòng đặt:</span>
                                <div id="qr-room-list" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Số lượng khách</span>
                                <strong class="text-dark" id="qr-guest-counts">---</strong>
                            </div>
                            <div class="border-top pt-3 mt-2">
                                <span class="text-muted small d-block">Tổng tiền thanh toán</span>
                                <strong class="text-primary fs-4" id="qr-total-price">---</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="qr-status-alert" class="alert mt-4 mb-0 py-3 d-flex align-items-center gap-3" style="display: none; border-radius: 12px; font-weight: 500;"></div>
            </div>
            <div class="modal-footer px-4 py-3" style="border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Đóng</button>
                <button type="button" id="btn-qr-action" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 500; display: none;">Nhận phòng nhanh</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Status Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="statusToast" class="toast align-items-center text-white border-0" role="alert" data-bs-delay="4000">
        <div class="d-flex">
            <div class="toast-body fw-500" id="toastMessage">Cập nhật thành công!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-delay="4000" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    // Laravel Base URL & CSRF Token
    const BASE_URL = "{{ url('/') }}";
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let selectedRoomId = null;
    let selectedRoomData = null;
    let currentLateFee = 0;
    let isMultiSelectMode = false;
    let selectedRoomIds = [];
    let selectedRoomsData = [];

    function setRoomDetailOpen(open) {
        const workspace = document.querySelector('.staff-workspace');
        const mutate = () => workspace?.classList.toggle('has-room-detail', open);
        window.animateInternalRoomLayout ? window.animateInternalRoomLayout(mutate) : mutate();
    }

    const roomImages = {
        'Phòng Đơn Tiêu Chuẩn': 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
        'Phòng Đôi Tiêu Chuẩn': 'https://images.unsplash.com/photo-1631049552057-403cdb8f0658?w=600&q=80',
        'Phòng Triple': 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80',
        'Phòng Gia Đình': 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600&q=80',
        'Phòng VIP': 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&q=80'
    };
    const defaultImg = 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600&q=80';

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
                if (card) card.click();
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') console.error('Silent board refresh failed:', error);
        });
    }

    function toggleMultiSelectMode() {
        const toggle = document.getElementById('multi-select-toggle');
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

                const btnMultiHold = document.getElementById('btn-multi-hold');
                if (btnMultiHold) {
                    btnMultiHold.style.display = 'block';
                    btnMultiHold.disabled = false;
                }

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
            document.getElementById('detail-img').src = roomData.image_url || roomImages[roomData.type_name] || defaultImg;

            let badgeClass = 'bg-secondary';
            let statusText = '';
                switch(roomData.ui_status || roomData.status) {
                case 'available': badgeClass = 'bg-success'; statusText = 'Đang trống'; break;
                case 'soon_to_checkin': badgeClass = 'bg-info'; statusText = 'Sắp nhận phòng'; break;
                case 'occupied': badgeClass = 'bg-primary'; statusText = 'Đang lưu trú'; break;
                case 'soon_to_checkout': badgeClass = 'bg-info'; statusText = 'Sắp trả phòng'; break;
                case 'cleaning': badgeClass = 'bg-warning text-dark'; statusText = 'Đang dọn dẹp'; break;
                case 'maintenance': badgeClass = 'bg-danger'; statusText = 'Bảo trì'; break;
                case 'booked': badgeClass = 'bg-warning text-dark'; statusText = 'Đã đặt'; break;
                case 'overdue': badgeClass = 'bg-danger'; statusText = 'Quá giờ trả'; break;
            }
            document.getElementById('detail-status-badge').innerHTML = `<span class="badge ${badgeClass} rounded-pill px-3">${statusText}</span>`;

            const isOccupiedLike = (roomData.ui_status || roomData.status) === 'occupied';
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
        const btnHold = document.getElementById('btn-action-hold');

        if (btnAvailable) { btnAvailable.style.display = 'none'; btnAvailable.disabled = false; }
        if (btnOccupied) { btnOccupied.style.display = 'none'; btnOccupied.disabled = false; }
        if (btnExtend) { btnExtend.style.display = 'none'; btnExtend.disabled = false; }
        if (btnCleaningDone) { btnCleaningDone.style.display = 'none'; btnCleaningDone.disabled = false; }
        if (btnHold) { btnHold.style.display = 'none'; btnHold.disabled = false; }

        if (currentStatus === 'available') {
            if (btnOccupied) btnOccupied.style.display = 'block';
            if (btnHold) {
                btnHold.style.display = 'block';
                const hasBookingToday = selectedRoomData && parseInt(selectedRoomData.has_today_booking) > 0;
                btnHold.disabled = hasBookingToday;
            }
        } else if (currentStatus === 'occupied') {
            if (btnAvailable) btnAvailable.style.display = 'block';
            if (btnExtend) {
                btnExtend.style.display = 'block';
                btnExtend.disabled = !(selectedRoomData && parseInt(selectedRoomData.has_active_booking) > 0);
            }
        } else if (currentStatus === 'cleaning') {
            if (btnCleaningDone) btnCleaningDone.style.display = 'block';
        }
    }

    function openExtendStayModal() {
        if (!selectedRoomData) return;

        const currentStatus = selectedRoomData.ui_status || selectedRoomData.status;
        if (currentStatus !== 'occupied') {
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
        document.getElementById('extend-help').textContent = mode === 'hours' ? 'Tối đa 12 giờ. Phí 200.000đ cho mỗi giờ.' : 'Từ 1 đến 30 ngày, tính theo giá phòng hiện tại.';
        amount.max = mode === 'hours' ? 12 : 30;
    }

    function submitExtendStay() {
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

    function handleSingleHold() {
        if (!selectedRoomData) return;
        openWalkinCheckinModal('hold');
    }

    function confirmPrebookCheckin() {
        triggerStatusUpdate('occupied');
    }

    function openWalkinCheckinModal(type = 'now') {
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
            showToast('Chỉ có thể đặt hoặc giữ chỗ cho phòng đang trống.', 'bg-danger');
            return;
        }

        const titleEl = document.getElementById('walkinCheckinModalTitle');
        const roomsLabelEl = document.getElementById('modal-rooms-label');
        if (type === 'hold') {
            if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-clock me-2 text-warning"></i>Giữ Chỗ Phòng';
            if (roomsLabelEl) roomsLabelEl.innerText = 'Các phòng giữ chỗ:';
        } else {
            if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Check-in Khách Vãng Lai';
            if (roomsLabelEl) roomsLabelEl.innerText = 'Các phòng Check-in:';
        }
        document.getElementById('walkin-type').value = type;

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
        const name = document.getElementById('walkin-name').value.trim();
        const phone = document.getElementById('walkin-phone').value.trim();
        const email = document.getElementById('walkin-email').value.trim();
        const walkinType = document.getElementById('walkin-type').value;
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
        if (!selectedRoomData) return;
        document.getElementById('checkout-room-label').innerText = 'Phòng ' + selectedRoomData.room_number;
        const modal = new bootstrap.Modal(document.getElementById('checkoutScopeModal'));
        modal.show();
    }

    function submitCheckout(scope) {
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
            el.style.borderColor = '#dee2e6';
            el.style.background  = '#fff';
        });
        const label = radio.closest('label');
        if (label) {
            label.style.borderColor = '#198754';
            label.style.background  = '#f4fbf7';
        }
    }

    function openCheckoutPaymentModal() {
        if (!selectedRoomData) return;
        if (parseInt(selectedRoomData.has_active_booking) <= 0) {
            // Không có booking hoạt động, chuyển thẳng sang dọn dẹp
            triggerStatusUpdate('cleaning', 'room');
            return;
        }

        const baseTotal = Number(selectedRoomData.active_total_price || 0);
        const paid = Number(selectedRoomData.active_deposit_amount || 0);

        const now = new Date();
        const lateDeadline = selectedRoomData.active_check_out
            ? new Date(`${selectedRoomData.active_check_out}T13:00:00+07:00`)
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
            el.style.borderColor = '#dee2e6';
            el.style.background  = '#fff';
        });

        // Show checkout payment modal
        const modal = new bootstrap.Modal(document.getElementById('checkoutPaymentModal'));
        modal.show();
    }

    function submitCheckoutPayment() {
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
        if (newStatus === 'available' && currentStatus === 'occupied' && checkoutScope === null) {
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
        const labels = { available: 'Đang trống', occupied: 'Đang lưu trú', cleaning: 'Đang dọn dẹp', maintenance: 'Bảo trì', soon_to_checkin: 'Sắp nhận phòng', soon_to_checkout: 'Sắp trả phòng', booked: 'Đã đặt', overdue: 'Quá giờ trả' };
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

    function showToast(message, bgClass) {
        const toastEl = document.getElementById('statusToast');
        const messageEl = document.getElementById('toastMessage');
        
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        toastEl.classList.add(bgClass);
        messageEl.innerText = message;
        
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
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
            const visibleRooms = section.querySelectorAll('.room-card-wrapper[style="display: block;"]');
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
        const manualBookingInput = document.getElementById('manual-booking-id');
        if (manualBookingInput) {
            manualBookingInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    submitManualBookingId();
                }
            });
        }
        document.getElementById('qrScannerModal')?.addEventListener('hidden.bs.modal', stopQRScanner);
    });
    window.addEventListener('popstate', restoreFiltersFromUrl);

    let html5QrCode = null;
    let qrCameras = [];

    function openQRScannerModal() {
        const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
        modal.show();
        
        document.getElementById('qr-scan-error').style.display = 'none';
        
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
                
                startScanning(devices[0].id);
            } else {
                document.getElementById('qr-camera-select').innerHTML = '<option value="">Không tìm thấy camera</option>';
                showToast('Không tìm thấy thiết bị camera nào.', 'bg-danger');
            }
        }).catch(err => {
            document.getElementById('qr-camera-select').innerHTML = '<option value="">Không có quyền truy cập camera</option>';
            showToast('Không thể truy cập camera. Vui lòng cấp quyền.', 'bg-danger');
        });
    }

    function startScanning(cameraId) {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                initScanner(cameraId);
            }).catch(() => {
                initScanner(cameraId);
            });
        } else {
            initScanner(cameraId);
        }
    }

    function initScanner(cameraId) {
        html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.start(
            cameraId,
            {
                fps: 10,
                qrbox: (width, height) => {
                    const minEdge = Math.min(width, height);
                    const size = Math.floor(minEdge * 0.85);
                    return { width: size, height: size };
                }
            },
            (decodedText) => {
                if (navigator.vibrate) {
                    navigator.vibrate(100);
                }
                
                stopQRScanner();
                
                const modalEl = document.getElementById('qrScannerModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                
                processScannedText(decodedText);
            },
            () => {
                // Ignore silent scanning noise
            }
        ).catch(err => {
            document.getElementById('qr-scan-error').innerText = "Không thể khởi động camera: " + err;
            document.getElementById('qr-scan-error').style.display = 'block';
        });
    }

    function changeCamera(cameraId) {
        if (cameraId) {
            startScanning(cameraId);
        }
    }

    function stopQRScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(err => console.error(err));
        }
    }

    function closeQRScanner() {
        stopQRScanner();
    }

    function submitManualBookingId() {
        const input = document.getElementById('manual-booking-id');
        const bookingId = input ? input.value.trim() : '';
        if (!bookingId) {
            showToast('Vui lòng nhập mã đặt phòng.', 'bg-warning text-dark');
            return;
        }
        stopQRScanner();
        const modalEl = document.getElementById('qrScannerModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        processScannedText(bookingId);
    }

    function processScannedText(text) {
        const normalizedText = String(text || '').trim();
        let bookingId = null;

        const patterns = [
            /Ma\s*don\s*:?\s*#?(\d+)/i,
            /Ma\s*dat\s*phong\s*:?\s*#?(\d+)/i,
            /Booking\s*ID\s*:?\s*#?(\d+)/i,
            /booking_id\s*:?\s*#?(\d+)/i,
        ];

        for (const pattern of patterns) {
            const match = normalizedText.match(pattern);
            if (match) {
                bookingId = match[1];
                break;
            }
        }

        if (!bookingId) {
            const numMatch = normalizedText.match(/^#?(\d+)$/);
            if (numMatch) {
                bookingId = numMatch[1];
            }
        }
        
        if (!bookingId) {
            showToast('Mã QR không đúng định dạng hóa đơn đặt phòng.', 'bg-danger');
            return;
        }
        
        fetch(`{{ route('staff.reception.booking-by-scan') }}?booking_id=${bookingId}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message || 'Lỗi liên kết dữ liệu'); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showQRResult(data.booking, data.rooms);
            } else {
                showToast(data.message || 'Không tìm thấy thông tin đặt phòng.', 'bg-danger');
            }
        })
        .catch(error => {
            showToast(error.message || 'Không tìm thấy thông tin đặt phòng.', 'bg-danger');
        });
    }

    function showQRResult(booking, rooms) {
        document.getElementById('qr-guest-name').innerText = booking.customer_name;
        document.getElementById('qr-guest-phone').innerText = booking.customer_phone;
        document.getElementById('qr-guest-email').innerText = booking.customer_email || '---';
        
        const checkin = new Date(booking.check_in);
        const checkout = new Date(booking.check_out);
        document.getElementById('qr-checkin-date').innerText = checkin.toLocaleDateString('vi-VN');
        document.getElementById('qr-checkout-date').innerText = checkout.toLocaleDateString('vi-VN');
        
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
        
        const todayStr = new Date().toLocaleDateString('sv-SE');
        const isToday = (booking.check_in === todayStr);
        
        const alertContainer = document.getElementById('qr-status-alert');
        const btnAction = document.getElementById('btn-qr-action');
        
        alertContainer.style.display = 'flex';
        alertContainer.className = 'alert mt-4 mb-0 py-3 d-flex align-items-center gap-3';
        
        if (booking.status === 'confirmed' || booking.status === 'pending') {
            if (isToday) {
                alertContainer.classList.add('alert-info');
                alertContainer.innerHTML = `<i class="fa-solid fa-circle-info fs-4 text-info"></i><div>Đơn đặt phòng hợp lệ. Có thể tiến hành nhận phòng nhanh cho toàn bộ ${rooms.length} phòng hôm nay.</div>`;
                btnAction.style.display = 'block';
                btnAction.onclick = () => performQuickCheckin(booking.id);
            } else {
                alertContainer.classList.add('alert-warning');
                alertContainer.innerHTML = `<i class="fa-solid fa-triangle-exclamation fs-4 text-warning"></i><div>Cảnh báo: Ngày nhận phòng là ${booking.check_in.split('-').reverse().join('/')} (không phải hôm nay).</div>`;
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

    async function performQuickCheckin(bookingId) {
        if (!await window.confirmOperation('Xác nhận nhận phòng nhanh cho tất cả các phòng thuộc đơn đặt này?')) return;
        
        fetch("{{ route('staff.reception.quick-checkin') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ booking_id: bookingId })
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
        const paid = Number(selectedRoomData.active_deposit_amount || 0);
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
