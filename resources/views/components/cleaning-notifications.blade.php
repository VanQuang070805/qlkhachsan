<style>
    .cleaning-bell-button {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        color: #475569;
    }
    .cleaning-bell-button:hover { background: #f1f5f9; color: #f59e0b; }
    .cleaning-bell-badge {
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        font-size: .68rem;
        line-height: 19px;
    }
    .cleaning-notification-menu { width: 330px; max-height: 390px; overflow-y: auto; }
    .cleaning-notification-item:hover { background: #fffaf0; }
    .cleaning-notification-room {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #fff3cd;
        color: #b7791f;
        flex: 0 0 auto;
    }
</style>

<div class="dropdown" id="cleaning-notification-widget">
    <button class="btn cleaning-bell-button position-relative d-flex align-items-center justify-content-center border-0"
            type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo phòng cần dọn dẹp">
        <i class="fa-solid fa-bell fs-5"></i>
        <span id="cleaning-notification-count"
              class="cleaning-bell-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end cleaning-notification-menu shadow border-0 mt-2 p-0">
        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom">
            <strong class="small">Phòng cần dọn dẹp</strong>
            <span class="badge text-bg-warning" id="cleaning-notification-summary">0 phòng</span>
        </div>
        <div id="cleaning-notification-list">
            <div class="text-center text-muted small py-4">Đang tải thông báo...</div>
        </div>
    </div>
</div>

<script>
(() => {
    const endpoint = @json(route('staff.cleaning-notifications'));
    const bookingsUrl = @json(route('staff.bookings'));
    const badge = document.getElementById('cleaning-notification-count');
    const summary = document.getElementById('cleaning-notification-summary');
    const list = document.getElementById('cleaning-notification-list');
    let previousRoomSignature = null;

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[char]);

    function render(data) {
        const count = Number(data.count || 0);
        const roomSignature = (data.rooms || []).map(room => `${room.id}:${room.requested_at || ''}`).join('|');
        const cleaningStateChanged = previousRoomSignature !== null && previousRoomSignature !== roomSignature;
        previousRoomSignature = roomSignature;
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.toggle('d-none', count === 0);
        summary.textContent = `${count} phòng`;

        if (!count) {
            list.innerHTML = '<div class="text-center text-muted small py-4"><i class="fa-solid fa-circle-check text-success me-1"></i> Không có phòng chờ dọn</div>';
            if (cleaningStateChanged) window.dispatchEvent(new CustomEvent('cleaning-notifications-updated'));
            return;
        }

        list.innerHTML = data.rooms.map(room => `
            <a href="${bookingsUrl}#room-card-${room.id}"
               class="cleaning-notification-item dropdown-item d-flex gap-2 align-items-center px-3 py-2 border-bottom"
               data-room-id="${room.id}">
                <span class="cleaning-notification-room d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-broom"></i>
                </span>
                <span class="flex-grow-1">
                    <strong class="d-block small">Phòng ${escapeHtml(room.room_number)} cần dọn dẹp</strong>
                    <span class="text-muted" style="font-size:.75rem">${escapeHtml(room.source)} · Tầng ${escapeHtml(room.floor)}</span>
                </span>
            </a>
        `).join('');

        list.querySelectorAll('[data-room-id]').forEach(link => {
            link.addEventListener('click', event => {
                const card = document.getElementById(`room-card-${link.dataset.roomId}`);
                if (!card) return;
                event.preventDefault();
                bootstrap.Dropdown.getOrCreateInstance(document.querySelector('.cleaning-bell-button')).hide();
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.click();
            });
        });

        if (cleaningStateChanged) window.dispatchEvent(new CustomEvent('cleaning-notifications-updated'));
    }

    async function refreshCleaningNotifications() {
        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            if (!response.ok) return;
            render(await response.json());
        } catch (error) {
            console.debug('Không thể cập nhật thông báo dọn phòng.', error);
        }
    }

    refreshCleaningNotifications();
    window.refreshCleaningNotifications = refreshCleaningNotifications;
    window.setInterval(refreshCleaningNotifications, 3000);
})();
</script>
