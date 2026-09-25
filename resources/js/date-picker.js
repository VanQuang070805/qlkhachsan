const parseCivil = value => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12) : null;
};

const civil = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const sameDay = (a, b) => a && b && civil(a) === civil(b);

export function enhanceDatePickers(root = document) {
    const fields = [...root.querySelectorAll('input[type="date"]:not([readonly]):not([disabled]):not([data-date-picker])')];
    if (!fields.length) return;

    const panel = document.createElement('section');
    panel.id = 'royal-date-picker';
    panel.className = 'royal-calendar';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-label', 'Chọn ngày');
    panel.innerHTML = `<header><button type="button" data-month="-1" aria-label="Tháng trước"><i class="bi bi-chevron-left" aria-hidden="true"></i></button><strong aria-live="polite"></strong><button type="button" data-month="1" aria-label="Tháng sau"><i class="bi bi-chevron-right" aria-hidden="true"></i></button></header><div class="royal-calendar__week" aria-hidden="true"><span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>CN</span></div><div class="royal-calendar__grid" role="grid"></div>`;
    document.body.append(panel);

    let active = null;
    let cursor = new Date();
    const title = panel.querySelector('strong');
    const grid = panel.querySelector('[role="grid"]');

    const rangeFor = input => {
        const scope = input.form || input.closest('[data-window-frame],.search-card,.availability-card,.bk-card,.filter-card,.modal-content') || document;
        const siblings = [...scope.querySelectorAll('input[data-date-picker]')];
        const identity = item => `${item.name || ''} ${item.id || ''}`;
        return {
            start: parseCivil(siblings.find(item => /(check[_-]?in|start)/i.test(identity(item)))?.value),
            end: parseCivil(siblings.find(item => /(check[_-]?out|end)/i.test(identity(item)))?.value),
        };
    };

    const position = () => {
        if (!active || panel.hidden) return;
        const rect = active.getBoundingClientRect();
        const width = Math.min(330, innerWidth - 24);
        panel.style.width = `${width}px`;
        panel.style.left = `${Math.max(12, Math.min(rect.left, innerWidth - width - 12))}px`;
        panel.style.top = `${rect.bottom + 358 < innerHeight ? rect.bottom + 8 : Math.max(12, rect.top - 350)}px`;
    };

    const close = () => {
        panel.hidden = true;
        active?.setAttribute('aria-expanded', 'false');
        active = null;
    };

    const select = date => {
        active.value = civil(date);
        active.dispatchEvent(new Event('input', { bubbles: true }));
        active.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };

    const render = () => {
        const year = cursor.getFullYear();
        const month = cursor.getMonth();
        title.textContent = new Intl.DateTimeFormat('vi-VN', { month: 'long', year: 'numeric' }).format(cursor);
        const first = new Date(year, month, 1, 12);
        const offset = (first.getDay() + 6) % 7;
        const start = new Date(year, month, 1 - offset, 12);
        const selected = parseCivil(active?.value);
        const today = new Date(); today.setHours(12, 0, 0, 0);
        const min = parseCivil(active?.min); const max = parseCivil(active?.max);
        const range = active ? rangeFor(active) : { start: null, end: null };
        grid.innerHTML = '';
        for (let index = 0; index < 42; index++) {
            const date = new Date(start); date.setDate(start.getDate() + index);
            const value = civil(date);
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = date.getDate(); button.dataset.date = value;
            button.setAttribute('role', 'gridcell');
            button.setAttribute('aria-label', new Intl.DateTimeFormat('vi-VN', { dateStyle: 'full' }).format(date));
            button.setAttribute('aria-selected', String(sameDay(date, selected) || sameDay(date, range.start) || sameDay(date, range.end)));
            button.disabled = Boolean((min && date < min) || (max && date > max));
            if (date.getMonth() !== month) button.classList.add('is-outside');
            if (sameDay(date, today)) button.classList.add('is-today');
            if (sameDay(date, range.start)) button.classList.add('is-range-start');
            if (sameDay(date, range.end)) button.classList.add('is-range-end');
            if (range.start && range.end && date > range.start && date < range.end) button.classList.add('is-range-middle');
            button.addEventListener('click', () => select(date));
            grid.append(button);
        }
    };

    const open = input => {
        active = input;
        const initial = parseCivil(input.value) || parseCivil(input.min) || new Date();
        cursor = new Date(initial.getFullYear(), initial.getMonth(), 1, 12);
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        render(); position();
        requestAnimationFrame(() => {
            const preferred = grid.querySelector('[aria-selected="true"]:not(:disabled), .is-today:not(:disabled), [role="gridcell"]:not(:disabled)');
            preferred?.focus({ preventScroll: true });
        });
    };

    fields.forEach(input => {
        input.dataset.datePicker = 'true';
        input.type = 'text';
        input.readOnly = true;
        input.inputMode = 'none';
        input.classList.add('date-picker-field');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-haspopup', 'dialog');
        input.setAttribute('aria-controls', panel.id);
        input.setAttribute('aria-expanded', 'false');
        input.addEventListener('click', () => panel.hidden || active !== input ? open(input) : close());
        input.addEventListener('keydown', event => {
            if (['Enter', ' ', 'ArrowDown'].includes(event.key)) { event.preventDefault(); open(input); }
            if (event.key === 'Escape') close();
        });
    });

    panel.querySelectorAll('[data-month]').forEach(button => button.addEventListener('click', () => { cursor.setMonth(cursor.getMonth() + Number(button.dataset.month)); render(); }));
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') { const target = active; close(); target?.focus(); return; }
        if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown','PageUp','PageDown'].includes(event.key)) return;
        event.preventDefault();
        const focused = panel.querySelector('[role="gridcell"]:focus');
        const date = parseCivil(focused?.dataset.date) || parseCivil(active?.value) || new Date();
        if (event.key === 'PageUp' || event.key === 'PageDown') date.setMonth(date.getMonth() + (event.key === 'PageUp' ? -1 : 1));
        else date.setDate(date.getDate() + ({ArrowLeft:-1,ArrowRight:1,ArrowUp:-7,ArrowDown:7}[event.key]));
        cursor = new Date(date.getFullYear(), date.getMonth(), 1, 12); render();
        panel.querySelector(`[data-date="${civil(date)}"]:not(:disabled)`)?.focus();
    });
    document.addEventListener('pointerdown', event => { if (!panel.hidden && !panel.contains(event.target) && event.target !== active) close(); });
    window.addEventListener('resize', position, { passive: true });
    window.addEventListener('scroll', position, { passive: true, capture: true });
}
