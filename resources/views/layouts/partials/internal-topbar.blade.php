<header class="internal-topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-mobile-toggle" aria-label="Mở danh mục điều hướng" id="sidebarMobileToggle">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="topbar-breadcrumb">
            <span class="topbar-brand-label" style="display: inline-flex; align-items: center; gap: 6px;">
                <img src="{{ asset('images/branding/rosa_mascot_flamingo_transparent.png') }}" alt="Rosa Hotel" style="height: 22px; width: auto; object-fit: contain;">
                <span>Rosa Hotel</span>
            </span>
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
        <div class="topbar-clock" id="topbarLiveClock" title="Thời gian hiện tại">
            <i class="bi bi-clock text-primary"></i>
            <span id="topbarClockText">--:--:--</span>
        </div>
        <div class="topbar-status-chip" title="Hệ thống đang hoạt động trực tuyến">
            <span class="topbar-status-dot"></span>
            <span>Trực tuyến</span>
        </div>
        <div class="topbar-user-chip">
            <div class="topbar-user-meta">
                <span class="topbar-user-name">{{ session('user.fullname', 'Nhân viên') }}</span>
                <span class="topbar-user-role">{{ auth()->user()?->role_label ?? (session('user.role') === 'admin' ? 'Quản trị viên' : 'Lễ tân') }}</span>
            </div>
        </div>
    </div>
</header>

<script>
(() => {
    const toggle = document.getElementById('sidebarMobileToggle');
    const sidebar = document.getElementById('workspaceSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!toggle || !sidebar || toggle.dataset.sidebarBound === 'true') return;

    const close = () => {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        document.body.classList.remove('sidebar-mobile-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.dataset.sidebarBound = 'true';
    toggle.setAttribute('aria-controls', sidebar.id);
    toggle.setAttribute('aria-expanded', 'false');
    window.toggleInternalSidebar = event => {
        event.stopPropagation();
        const open = !sidebar.classList.contains('is-open');
        sidebar.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        document.body.classList.toggle('sidebar-mobile-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    window.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        if (event.target.closest('#sidebarMobileToggle')) window.toggleInternalSidebar(event);
        else if (event.target.closest('#sidebarBackdrop')) close();
    }, { capture: true });
    backdrop?.addEventListener('click', close);
    sidebar.querySelectorAll('nav a').forEach(link => link.addEventListener('click', () => {
        if (window.innerWidth <= 768) close();
    }));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) close();
    });
})();
</script>
