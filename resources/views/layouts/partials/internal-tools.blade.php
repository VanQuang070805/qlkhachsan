<dialog class="operation-confirm" id="operationConfirm" aria-labelledby="operationConfirmTitle"><form method="dialog"><div class="window-controls window-controls--dialog" role="group" aria-label="Điều khiển hộp thoại"><button class="window-control window-control--close" value="cancel" aria-label="Đóng hộp thoại"></button></div><h2 id="operationConfirmTitle">Xác nhận thao tác</h2><p data-confirm-message></p><div><button class="btn btn-outline-secondary" value="cancel" autofocus>Quay lại</button><button class="btn btn-primary" value="confirm">Xác nhận</button></div></form></dialog>
<div class="command-palette" id="commandPalette" hidden>
    <button class="command-backdrop" type="button" data-command-close aria-label="Đóng"></button>
    <section class="command-dialog" role="dialog" aria-modal="true" aria-label="Lệnh nhanh">
        <div class="window-controls window-controls--dialog" role="group" aria-label="Điều khiển hộp thoại"><button class="window-control window-control--close" type="button" data-command-close aria-label="Đóng lệnh nhanh"></button></div>
        <label><i class="bi bi-search"></i><input id="commandInput" type="search" placeholder="Tìm tính năng hoặc nhập lệnh…" autocomplete="off"><kbd>ESC</kbd></label>
        <div class="command-results">
            @if(session('user.role') === 'admin')
                <a href="{{ route('admin.reports') }}"><i class="bi bi-graph-up"></i><span>Báo cáo tài chính<small>Doanh thu, ADR và RevPAR</small></span></a>
                <a href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i><span>Người dùng & RBAC<small>Tài khoản và phân quyền</small></span></a>
                <a href="{{ route('admin.price-settings.index') }}"><i class="bi bi-sliders"></i><span>Điều chỉnh giá<small>Giá theo mùa và sự kiện</small></span></a>
            @endif
            <a href="{{ route('staff.bookings') }}"><i class="bi bi-grid-3x3-gap"></i><span>Sơ đồ phòng<small>Tra cứu và thao tác tại quầy</small></span></a>
            <a href="{{ route('receptionist.profile') }}"><i class="bi bi-person"></i><span>Hồ sơ cá nhân<small>Thông tin và mật khẩu</small></span></a>
        </div>
    </section>
</div>
<script>
(() => {
    const root = document.body;
    localStorage.removeItem('royal-internal-theme');
    root.dataset.theme = 'light';
    const sidebarPreference = 'collapsed';
    const applySidebarPreference = () => root.classList.toggle('sidebar-collapsed', sidebarPreference === 'collapsed');
    applySidebarPreference();
    const palette = document.getElementById('commandPalette');
    const input = document.getElementById('commandInput');
    const openPalette = () => { palette.hidden = false; requestAnimationFrame(() => palette.classList.add('is-open')); setTimeout(() => input.focus(), 30); };
    const closePalette = () => { palette.classList.remove('is-open'); setTimeout(() => { palette.hidden = true; input.value = ''; filterCommands(''); }, 160); };
    const filterCommands = value => document.querySelectorAll('.command-results a').forEach(link => link.hidden = !link.textContent.toLowerCase().includes(value.toLowerCase()));
    const sidebar = document.querySelector('.workspace-sidebar');
    sidebar?.addEventListener('pointerenter', () => {
        if (matchMedia('(max-width:760px)').matches) return;
        root.classList.remove('sidebar-collapsed');
        root.classList.add('sidebar-hover-open');
    });
    sidebar?.addEventListener('pointerleave', () => {
        if (!root.classList.contains('sidebar-hover-open')) return;
        root.classList.remove('sidebar-hover-open');
        applySidebarPreference();
    });
    document.querySelector('[data-command-open]')?.addEventListener('click', openPalette);
    document.querySelectorAll('[data-command-close]').forEach(button => button.addEventListener('click', closePalette));
    input?.addEventListener('input', event => filterCommands(event.target.value));
    document.addEventListener('keydown', event => { if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); palette.hidden ? openPalette() : closePalette(); } if (event.key === 'Escape' && !palette.hidden) closePalette(); });
})();
</script>
