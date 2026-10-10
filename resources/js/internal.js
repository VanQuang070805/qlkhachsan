import { gsap } from 'gsap';
import '../css/date-picker.css';
import { enhanceDatePickers } from './date-picker';
import './numeric-inputs';
import { Flip } from 'gsap/Flip';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Chart from 'chart.js/auto';
import { Html5Qrcode } from 'html5-qrcode';

window.Chart = Chart;
window.Html5Qrcode = Html5Qrcode;
gsap.registerPlugin(Flip, ScrollTrigger);

document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.body.classList.remove('internal-pending');

    document.querySelectorAll('.modal-content').forEach((content) => {
        if (content.querySelector('.window-controls') || content.querySelector('.macos-traffic-lights')) return;
        const controls = document.createElement('div');
        controls.className = 'window-controls window-controls--modal';
        controls.setAttribute('aria-hidden', 'true');
        controls.style.pointerEvents = 'none';
        controls.innerHTML = '<span class="ctrl-dot ctrl-red"></span><span class="ctrl-dot ctrl-yellow"></span><span class="ctrl-dot ctrl-green"></span>';
        content.prepend(controls);
    });

    enhanceDatePickers();

    document.querySelectorAll('.search-field, .operations-search, .refund-search, .command-dialog > label').forEach((field) => {
        const input = field.querySelector('input[type="search"], input[name="search"]');
        if (!input || field.querySelector('.search-clear')) return;
        input.type = 'search';
        if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', input.placeholder || 'Tìm kiếm');
        const submit = document.createElement('button');
        submit.type = 'button';
        submit.className = 'search-submit';
        submit.setAttribute('aria-label', 'Tìm kiếm');
        submit.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i>';
        submit.addEventListener('click', () => input.form?.requestSubmit?.() || input.focus());
        field.querySelector(':scope > i.bi-search')?.remove();
        field.prepend(submit);
        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'search-clear';
        clear.setAttribute('aria-label', 'Xóa nội dung tìm kiếm');
        clear.innerHTML = '<i class="bi bi-x" aria-hidden="true"></i>';
        const sync = () => clear.hidden = !input.value;
        clear.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('input', { bubbles: true })); input.focus(); sync(); });
        input.addEventListener('input', sync);
        field.append(clear);
        sync();
    });

    // Keep the booking destination visually identical in both internal portals.
    document.querySelectorAll('.workspace-sidebar a[href*="/staff/bookings"] i').forEach(icon => {
        icon.className = 'bi bi-calendar2-check';
    });

    window.animateInternalRoomLayout = mutate => {
        const rooms = document.querySelectorAll('.room-card-wrapper');
        if (reduceMotion || !rooms.length) { mutate(); return; }
        const state = Flip.getState(rooms);
        mutate();
        Flip.from(state, { duration: .55, ease: 'cubic-bezier(0.16, 1, 0.3, 1)', absolute: true, stagger: .01, prune: true, onComplete: () => ScrollTrigger.refresh() });
    };

    if (!reduceMotion) {
        const metrics = document.querySelectorAll('.operations-overview .metric-grid > .metric');
        if (metrics.length) gsap.fromTo(metrics, { autoAlpha: 0, y: 12 }, {
            autoAlpha: 1, y: 0, duration: .32, stagger: .04, ease: 'power2.out',
            clearProps: 'opacity,transform,visibility',
            scrollTrigger: { trigger: metrics[0].parentElement, start: 'top 95%', once: true },
        });
        const operationalBlocks = document.querySelectorAll('.internal-main > .data-panel, .internal-main > .module-head, .internal-main > .price-summary, .report-charts > .data-panel, .staff-workspace .refund-metrics, .staff-workspace .refund-toolbar');
        if (operationalBlocks.length) ScrollTrigger.batch(operationalBlocks, {
            start: 'top 95%', once: true, interval: .04, batchMax: 6,
            onEnter: batch => gsap.fromTo(batch, { autoAlpha: 0, y: 14 }, {
                autoAlpha: 1, y: 0, duration: .34, stagger: .04, ease: 'power2.out',
                clearProps: 'opacity,transform,visibility',
            }),
        });
        window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
    }

    // Re-rendering after browser Back must never retain the exit animation.
    window.addEventListener('pageshow', event => {
        gsap.set('.workspace-content', { clearProps: 'opacity,visibility,transform' });
        if (event.persisted) ScrollTrigger.refresh();
    });

    window.confirmOperation = message => new Promise(resolve => {
        const dialog = document.getElementById('operationConfirm');
        if (dialog.open) return resolve(false);
        dialog.querySelector('[data-confirm-message]').textContent = message;
        dialog.returnValue = 'cancel';
        dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), { once: true });
        dialog.showModal();
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', async event => {
        if (form.dataset.confirmed) return;
        event.preventDefault();
        if (await window.confirmOperation(form.dataset.confirm)) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    }));
    const cancellationTabs = [...document.querySelectorAll('[data-cancellation-tab]')];
    cancellationTabs.forEach(tab => tab.addEventListener('click', () => {
        const activePanel = tab.dataset.cancellationTab;
        cancellationTabs.forEach(item => {
            const active = item === tab;
            item.setAttribute('aria-selected', String(active));
            item.classList.toggle('btn-dark', active);
            item.classList.toggle('btn-outline-secondary', !active);
        });
        document.querySelectorAll('[data-cancellation-panel]').forEach(panel => {
            panel.hidden = panel.dataset.cancellationPanel !== activePanel;
        });
    }));

    const search = document.querySelector('[data-refund-search]');
    const status = document.querySelector('[data-refund-status]');
    const filterRefunds = () => {
        const query = (search?.value || '').trim().toLocaleLowerCase('vi');
        let visible = 0;
        document.querySelectorAll('[data-refund-record]').forEach(record => {
            record.hidden = !(record.dataset.search.toLocaleLowerCase('vi').includes(query) && (!status.value || record.dataset.status === status.value));
            if (!record.hidden) visible++;
        });
        const empty = document.querySelector('[data-refund-empty]');
        if (empty) empty.hidden = visible > 0;
    };
    search?.addEventListener('input', filterRefunds);
    status?.addEventListener('change', filterRefunds);
    document.getElementById('refundConfirm')?.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        document.querySelector('[data-refund-form]').action = trigger.dataset.refundUrl;
        document.querySelector('[data-refund-customer]').textContent = trigger.dataset.refundName;
        document.querySelector('[data-refund-total]').textContent = trigger.dataset.refundAmount;
    });
    document.querySelector('[data-refund-form]')?.addEventListener('submit', event => {
        const submit = event.target.querySelector('[type=submit]');
        submit.disabled = true;
        submit.textContent = 'Đang ghi nhận…';
    });

    // Modal background scroll prevention
    document.addEventListener('show.bs.modal', () => {
        document.body.classList.add('has-active-modal');
        document.body.style.overflow = 'hidden';
    });
    document.addEventListener('hidden.bs.modal', () => {
        if (!document.querySelector('.modal.show')) {
            document.body.classList.remove('has-active-modal');
            document.body.style.overflow = '';
        }
    });

    // Real-time live clock in internal topbar
    const clockEl = document.getElementById('topbarClockText');
    if (clockEl) {
        const updateClock = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('vi-VN', { hour12: false });
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-success, .alert-danger, .alert-info, .alert-warning').forEach(el => {
        setTimeout(() => {
            if (el && el.parentElement) {
                el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                setTimeout(() => el.remove(), 400);
            }
        }, 5000);
    });

    // Mobile sidebar off-canvas drawer toggle
    const mobileToggle = document.getElementById('sidebarMobileToggle');
    const sidebar = document.getElementById('workspaceSidebar') || document.querySelector('.workspace-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    const closeMobileSidebar = () => {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        document.body.classList.remove('sidebar-mobile-open');
    };

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const willOpen = !sidebar.classList.contains('is-open');
            sidebar.classList.toggle('is-open', willOpen);
            backdrop?.classList.toggle('is-open', willOpen);
            document.body.classList.toggle('sidebar-mobile-open', willOpen);
        });

        backdrop?.addEventListener('click', closeMobileSidebar);

        sidebar.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) closeMobileSidebar();
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeMobileSidebar();
            }
        });
    }
});
