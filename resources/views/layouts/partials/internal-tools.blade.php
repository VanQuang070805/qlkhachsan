<dialog class="operation-confirm" id="operationConfirm" aria-labelledby="operationConfirmTitle">
    <form method="dialog">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: rgba(0,0,0,0.06) !important;">
            <div class="window-controls window-controls--dialog d-flex align-items-center gap-1" style="pointer-events: none;" aria-hidden="true">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <h2 id="operationConfirmTitle" class="m-0 fs-6 fw-bold text-dark text-center flex-grow-1" style="color: #0f172a;">Xác nhận thao tác</h2>
            <button type="submit" class="btn-close m-0" value="cancel" aria-label="Đóng hộp thoại" style="font-size: 0.75rem;"></button>
        </div>
        <p data-confirm-message style="color: #475569; font-size: 0.92rem;"></p>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button class="btn btn-light px-3 fw-medium" value="cancel" autofocus style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); color: #334155; font-size: 0.88rem;">Quay lại</button>
            <button class="btn btn-primary px-3 fw-medium" value="confirm" style="border-radius: 10px; font-size: 0.88rem;">Xác nhận</button>
        </div>
    </form>
</dialog>
<div class="command-palette" id="commandPalette" hidden>
    <button class="command-backdrop" type="button" data-command-close aria-label="Đóng"></button>
    <section class="command-dialog" role="dialog" aria-modal="true" aria-label="Lệnh nhanh">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color: rgba(0,0,0,0.06) !important;">
            <div class="window-controls window-controls--dialog d-flex align-items-center gap-1" style="pointer-events: none;" aria-hidden="true">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <span class="fw-semibold text-muted small">Lệnh nhanh</span>
            <button type="button" class="btn-close m-0" data-command-close aria-label="Đóng lệnh nhanh" style="font-size: 0.75rem;"></button>
        </div>
        <label style="height: 52px; padding: 0 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(0,0,0,0.06); margin: 0;">
            <i class="bi bi-search text-muted" style="font-size: 0.95rem;"></i>
            <input id="commandInput" type="search" placeholder="Tìm tính năng hoặc nhập lệnh…" autocomplete="off" style="flex: 1; border: none; outline: none; background: transparent; font-size: 0.88rem; color: #0f172a;">
            <kbd class="command-esc-badge" style="display: inline-flex; align-items: center; justify-content: center; height: 20px; min-width: 24px; padding: 0 6px; font-size: 0.65rem; font-weight: 700; border-radius: 5px; background: #f1f5f9; color: #64748b; border: 1px solid rgba(0,0,0,0.14); box-shadow: 0 1px 1px rgba(0,0,0,0.05); font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;">ESC</kbd>
        </label>
        <div class="command-results">
            @if(session('user.role') === 'admin')
                <a href="{{ route('admin.reports') }}"><i class="bi bi-graph-up"></i><span>Báo cáo tài chính<small>Doanh thu, ADR và RevPAR</small></span></a>
                <a href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i><span>Người dùng & RBAC<small>Tài khoản và phân quyền</small></span></a>
                <a href="{{ route('admin.price-settings.index') }}"><i class="bi bi-sliders"></i><span>Điều chỉnh giá<small>Giá theo mùa và sự kiện</small></span></a>
            @endif
            <a href="{{ route('staff.bookings') }}"><i class="bi bi-grid-3x3-gap"></i><span>Sơ đồ phòng<small>Tra cứu và thao tác tại quầy</small></span></a>
            <a href="{{ route('staff.iot') }}"><i class="bi bi-person-bounding-box"></i><span>Face ID &amp; IoT<small>Camera, đồng bộ Pi và khóa cửa</small></span></a>
            <a href="{{ route('receptionist.profile') }}"><i class="bi bi-person"></i><span>Hồ sơ cá nhân<small>Thông tin và mật khẩu</small></span></a>
        </div>
    </section>
</div>
<script>
(() => {
    const root = document.body;
    localStorage.removeItem('royal-internal-theme');
    root.dataset.theme = 'light';
    root.classList.remove('sidebar-collapsed', 'sidebar-hover-open');
    const palette = document.getElementById('commandPalette');
    const input = document.getElementById('commandInput');
    const openPalette = () => { palette.hidden = false; requestAnimationFrame(() => palette.classList.add('is-open')); setTimeout(() => input.focus(), 30); };
    const closePalette = () => { palette.classList.remove('is-open'); setTimeout(() => { palette.hidden = true; input.value = ''; filterCommands(''); }, 160); };
    const filterCommands = value => document.querySelectorAll('.command-results a').forEach(link => link.hidden = !link.textContent.toLowerCase().includes(value.toLowerCase()));
    document.querySelector('[data-command-open]')?.addEventListener('click', openPalette);
    document.querySelectorAll('[data-command-close]').forEach(button => button.addEventListener('click', closePalette));
    input?.addEventListener('input', event => filterCommands(event.target.value));
    document.addEventListener('keydown', event => { if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); palette.hidden ? openPalette() : closePalette(); } if (event.key === 'Escape' && !palette.hidden) closePalette(); });
})();
</script>
