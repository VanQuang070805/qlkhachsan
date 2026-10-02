<header class="internal-topbar">
    <div class="topbar-left">
        <div class="topbar-breadcrumb">
            <span class="topbar-brand-label"><i class="bi bi-building me-1 text-primary"></i>Posh Boutique</span>
            <span class="topbar-sep">/</span>
            <span class="topbar-current-page">@hasSection('page-title')@yield('page-title')@else @yield('title', 'Vận hành')@endif</span>
        </div>
    </div>
    
    <div class="topbar-center">
        <button class="topbar-search command-trigger" type="button" data-command-open aria-label="Tìm kiếm trang và tính năng">
            <i class="bi bi-search"></i>
            <span>Tìm kiếm nhanh…</span>
            <kbd>Ctrl K</kbd>
        </button>
    </div>

    <div class="topbar-right">
        @include('components.cleaning-notifications')
        <div class="topbar-clock" id="topbarLiveClock" title="Thời gian hiện tại">
            <i class="bi bi-clock text-primary"></i>
            <span id="topbarClockText">--:--:--</span>
        </div>
        <div class="topbar-status-chip" title="Hệ thống đang hoạt động trực tuyến">
            <span class="topbar-status-dot"></span>
            <span>Trực tuyến</span>
        </div>
        <div class="topbar-user-chip">
            <span class="topbar-avatar">{{ mb_strtoupper(mb_substr(session('user.fullname', 'R'), 0, 1)) }}</span>
            <div class="topbar-user-meta">
                <span class="topbar-user-name">{{ session('user.fullname', 'Nhân viên') }}</span>
                <span class="topbar-user-role">{{ session('user.role') === 'admin' ? 'Quản trị viên' : 'Lễ tân' }}</span>
            </div>
        </div>
    </div>
</header>
