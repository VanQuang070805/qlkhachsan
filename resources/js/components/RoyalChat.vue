<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { gsap } from 'gsap';

const props = defineProps({
    endpoint: { type: String, required: true },
    resetEndpoint: { type: String, default: '/chatbot/reset' },
    userId: { type: String, default: '' },
    userName: { type: String, default: '' },
});

const STORAGE_KEY = 'rosaliza-chat-state-v2';
const NAV_MARKER_KEY = 'rosaliza-chat-internal-nav';

const open = ref(false);
const pending = ref(false);
const typing = ref(false);
const draft = ref('');
const messages = ref([]);
const messageList = ref(null);
const panel = ref(null);
const input = ref(null);
const pageZoomed = ref(false);
const isMinimized = ref(false);

let controller = null;
let followLatest = true;
let internalNavPending = false;
let leavingSite = false;
let lastInAppInteractionAt = 0;

const userKey = computed(() => (props.userId ? `user:${props.userId}` : 'guest'));

const petAction = ref(null);
const lookDirection = ref(null);
let petTimer = null;
const petStates = {
    idle: { row: 0, frames: 6 },
    'running-right': { row: 1, frames: 8 },
    'running-left': { row: 2, frames: 8 },
    waving: { row: 3, frames: 4 },
    jumping: { row: 4, frames: 5 },
    failed: { row: 5, frames: 8 },
    waiting: { row: 6, frames: 6 },
    running: { row: 7, frames: 6 },
    review: { row: 8, frames: 6 },
};
const petState = computed(() =>
    petAction.value ||
    (pending.value ? (typing.value ? 'waiting' : 'review') : lookDirection.value !== null ? 'look' : 'idle')
);
const petStyle = computed(() => {
    if (petState.value === 'look') {
        const i = lookDirection.value;
        return { '--pico-row': i < 8 ? '90%' : '100%', '--pico-x': `${((i % 8) * 100) / 7}%` };
    }
    const state = petStates[petState.value];
    return {
        '--pico-row': `${state.row * 10}%`,
        '--pico-end': `${((state.frames - 1) * 100) / 7}%`,
        '--pico-steps': state.frames - 1,
    };
});

function playPet(state, duration = 1100) {
    clearTimeout(petTimer);
    petAction.value = state;
    petTimer = setTimeout(() => {
        petAction.value = null;
    }, duration);
}

function lookAtPointer(event) {
    if (event.pointerType === 'touch') return;
    const rect = event.currentTarget.getBoundingClientRect();
    const dx = event.clientX - rect.left - rect.width / 2;
    const dy = event.clientY - rect.top - rect.height / 2;
    if (Math.hypot(dx, dy) < 8) {
        lookDirection.value = null;
        return;
    }
    lookDirection.value = Math.round((Math.atan2(dx, -dy) + Math.PI * 2) / (Math.PI / 8)) % 16;
}

const suggestions = computed(() =>
    props.userId
        ? [
              ['Đơn đặt phòng của tôi', 'Kiểm tra giúp tôi đơn đặt phòng hiện tại'],
              ['Phòng cho 2 người', 'Gợi ý phòng phù hợp cho 2 người'],
              ['Xem giá phòng', 'Giá các hạng phòng hiện tại thế nào?'],
          ]
        : [
              ['Phòng cho 2 người', 'Gợi ý phòng cho 2 người'],
              ['Xem giá phòng', 'Giá phòng hiện tại thế nào?'],
              ['Cách đặt phòng', 'Hướng dẫn tôi quy trình đặt phòng'],
          ]
);

const latestUserIndex = computed(() => {
    for (let index = messages.value.length - 1; index >= 0; index -= 1) {
        if (messages.value[index].role === 'user') return index;
    }
    return -1;
});

function defaultGreeting() {
    const cleanName = String(props.userName || '').trim();
    if (props.userId && cleanName) {
        return `Xin chào ${cleanName}, tôi là Pico tại Rosaliza Hotel. Tôi có thể hỗ trợ bạn chọn phòng, tra cứu đơn đặt phòng hoặc giải đáp thông tin lưu trú.`;
    }
    return 'Xin chào, tôi là Pico tại Rosaliza Hotel. Tôi có thể giúp bạn chọn phòng, xem giá hoặc hướng dẫn đặt kỳ nghỉ.';
}

function message(role, content, extras = {}) {
    return {
        role,
        content,
        sentAt: new Date().toISOString(),
        status: role === 'user' ? 'Đã gửi' : null,
        ...extras,
    };
}

function clearLocalChatStorage() {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem('royal-chat-history');
        sessionStorage.removeItem(NAV_MARKER_KEY);
    } catch {
        // Ignore storage restrictions.
    }
}

function persist() {
    try {
        sessionStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                userKey: userKey.value,
                open: open.value,
                messages: messages.value
                    .slice(-12)
                    .map(({ role, content, sentAt, status }) => ({ role, content, sentAt, status })),
            })
        );
    } catch {
        // Ignore storage quota errors.
    }
}

function markInternalNavigation() {
    internalNavPending = true;
    leavingSite = false;
    try {
        sessionStorage.setItem(
            NAV_MARKER_KEY,
            JSON.stringify({
                ts: Date.now(),
                userKey: userKey.value,
            })
        );
        persist();
    } catch {
        // Ignore storage errors.
    }
}

function sendResetBeacon() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (!props.resetEndpoint) return;
    try {
        if (navigator.sendBeacon && csrfToken) {
            const fd = new FormData();
            fd.append('_token', csrfToken);
            navigator.sendBeacon(props.resetEndpoint, fd);
            return;
        }
        fetch(props.resetEndpoint, {
            method: 'POST',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({}),
        }).catch(() => {});
    } catch {
        // Ignore unload reset errors.
    }
}

function collectPageContext() {
    const roomTitle =
        document.querySelector('.aeth-room-title, [data-room-title]')?.textContent?.replace(/\s+/g, ' ').trim() || '';
    const roomPrice =
        document.querySelector('#displayRate, .aeth-rate-price')?.textContent?.replace(/\s+/g, ' ').trim() || '';
    const checkIn = document.querySelector('#bkCheckIn, input[name="check_in"]')?.value || '';
    const checkOut = document.querySelector('#bkCheckOut, input[name="check_out"]')?.value || '';
    const adults = document.querySelector('#bkAdults, input[name="adults"], select[name="adults"]')?.value || '';
    const children = document.querySelector('#bkChildren, input[name="children"], select[name="children"]')?.value || '';
    const guests = adults
        ? `${adults} người lớn${Number(children) > 0 ? `, ${children} trẻ em` : ''}`
        : '';

    return {
        path: `${window.location.pathname}${window.location.search}`.slice(0, 200),
        title: String(document.title || '').slice(0, 200),
        room_name: roomTitle.slice(0, 120),
        room_price: roomPrice.slice(0, 80),
        check_in: String(checkIn || '').slice(0, 40),
        check_out: String(checkOut || '').slice(0, 40),
        guests: guests.slice(0, 40),
    };
}

function timeLabel(value) {
    const date = new Date(value || Date.now());
    return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit' }).format(date);
}

function isLastInRun(index) {
    return messages.value[index + 1]?.role !== messages.value[index]?.role;
}

function onMessageScroll() {
    const list = messageList.value;
    if (list) followLatest = list.scrollHeight - list.clientHeight - list.scrollTop < 48;
}

async function scrollToLatest(force = false) {
    await nextTick();
    if (messageList.value && (force || followLatest)) {
        messageList.value.scrollTop = messageList.value.scrollHeight;
    }
}

async function toggle(next = !open.value, animate = true) {
    if (panel.value) gsap.killTweensOf(panel.value);
    if (next && typeof window.closeMobileDrawer === 'function') {
        window.closeMobileDrawer();
    }
    open.value = next;
    persist();
    playPet(next ? 'waving' : 'running-left', next ? 1200 : 800);
    if (!next) {
        isMinimized.value = false;
        pageZoomed.value = false;
    }
    await nextTick();
    if (next) {
        if (animate && panel.value) {
            gsap.fromTo(
                panel.value,
                { autoAlpha: 0, y: 14, scale: 0.97 },
                { autoAlpha: 1, y: 0, scale: 1, duration: 0.34, ease: 'power3.out', clearProps: 'opacity,transform,visibility' }
            );
        }
        if (window.innerWidth > 768) input.value?.focus();
        await scrollToLatest(true);
    }
}

function closeChat() {
    toggle(false);
}

function resizeInput() {
    if (!input.value) return;
    input.value.style.height = 'auto';
    input.value.style.height = `${Math.min(input.value.scrollHeight, 96)}px`;
}

function keydown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        send();
    }
}

async function send(prompt) {
    if (typeof prompt === 'string') draft.value = prompt;
    const content = draft.value.trim();
    if (!content || pending.value) return;

    messages.value.push(message('user', content));
    const priorMessages = messages.value
        .slice(0, -1)
        .filter((item) => item.role === 'user' || item.role === 'assistant');
    const history = priorMessages.some((item) => item.role === 'user')
        ? priorMessages.slice(-8).map((item) => ({
              role: item.role,
              content: String(item.content || '').slice(0, 1000),
          }))
        : [];
    const pageContext = collectPageContext();

    persist();
    draft.value = '';
    if (input.value) input.value.style.height = 'auto';
    pending.value = true;
    typing.value = true;
    playPet('running-right', 650);
    controller = new AbortController();
    followLatest = true;
    await scrollToLatest(true);

    messages.value.push(message('assistant', ''));
    const assistant = messages.value[messages.value.length - 1];
    const userIndex = latestUserIndex.value;
    if (userIndex >= 0) messages.value[userIndex].status = 'Đã đọc';
    let completed = false;
    let failureText = '';
    try {
        for (let attempt = 0; attempt < 2 && !completed; attempt++) {
            if (attempt) await new Promise((resolve) => setTimeout(resolve, 400));
            try {
                const response = await fetch(props.endpoint, {
                    method: 'POST',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/event-stream',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ message: content, history, page_context: pageContext }),
                });
                if (!response.ok || !response.body) throw new Error('Request failed');
                assistant.content = '';
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const { value, done } = await reader.read();
                    buffer += decoder.decode(value || new Uint8Array(), { stream: !done }).replace(/\r\n/g, '\n');
                    const events = buffer.split('\n\n');
                    buffer = events.pop() || '';
                    let receivedDelta = false;
                    for (const event of events) {
                        const line = event.split('\n').find((item) => item.startsWith('data: '));
                        if (!line) continue;
                        const data = JSON.parse(line.slice(6));
                        if (data.error) {
                            failureText = data.error;
                            typing.value = false;
                            break;
                        }
                        if (data.delta) {
                            typing.value = false;
                            assistant.content += data.delta;
                            receivedDelta = true;
                        }
                        if (data.done) {
                            typing.value = false;
                            completed = true;
                        }
                    }
                    if (receivedDelta) await scrollToLatest();
                    if (done || failureText) {
                        if (!done && failureText) await reader.cancel();
                        break;
                    }
                    if (completed) {
                        const tail = await Promise.race([
                            reader.read(),
                            new Promise((resolve) => setTimeout(() => resolve({ timedOut: true }), 150)),
                        ]);
                        if (tail?.timedOut) await reader.cancel();
                        break;
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') throw error;
                failureText = 'Kết nối đang gián đoạn.';
            }
            if (!completed && attempt === 0) failureText = '';
        }
        if (!completed) {
            assistant.content = assistant.content
                ? `${assistant.content}\n\n${failureText || 'Kết nối bị ngắt.'} Bạn có thể gửi lại câu hỏi.`
                : `${failureText || 'Pico chưa thể trả lời lúc này.'} Bạn vui lòng thử lại.`;
        }
        playPet(completed ? 'jumping' : 'failed', completed ? 1100 : 1900);
        persist();
    } catch (error) {
        if (error.name !== 'AbortError') {
            assistant.content = 'Kết nối đang gián đoạn. Bạn vui lòng thử lại sau ít phút.';
            playPet('failed', 1900);
        }
    } finally {
        pending.value = false;
        typing.value = false;
        controller = null;
        await scrollToLatest();
        input.value?.focus();
    }
}

function escapeClose(event) {
    if (event.key === 'Escape' && open.value) toggle(false);
}

function handleDocumentClick(event) {
    const link = event.target?.closest?.('a[href]');
    if (link) {
        const rawHref = link.getAttribute('href') || '';
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:')) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        try {
            const url = new URL(link.href, window.location.origin);
            if (url.origin !== window.location.origin) {
                leavingSite = true;
                internalNavPending = false;
                lastInAppInteractionAt = 0;
                return;
            }
            if (/\/logout\b/i.test(url.pathname)) {
                leavingSite = true;
                internalNavPending = false;
                clearLocalChatStorage();
                return;
            }
            markInternalNavigation();
            return;
        } catch {
            return;
        }
    }

    if (event.target?.closest?.('button, [role="button"], select, input')) {
        lastInAppInteractionAt = Date.now();
    }
}

function handleDocumentSubmit(event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const action = form.getAttribute('action') || window.location.href;
    try {
        const url = new URL(action, window.location.origin);
        if (url.origin !== window.location.origin || /\/logout\b/i.test(url.pathname)) {
            leavingSite = true;
            internalNavPending = false;
            clearLocalChatStorage();
            return;
        }
        markInternalNavigation();
    } catch {
        // Ignore malformed form action.
    }
}

function handlePageHide(event) {
    if (event.persisted) return;
    const triggeredInsideApp = internalNavPending || (!leavingSite && Date.now() - lastInAppInteractionAt < 2500);
    if (triggeredInsideApp) {
        markInternalNavigation();
        return;
    }
    clearLocalChatStorage();
    sendResetBeacon();
}

function isSameOriginReferrer() {
    if (!document.referrer) return false;
    try {
        return new URL(document.referrer).origin === window.location.origin;
    } catch {
        return false;
    }
}

onMounted(() => {
    let restored = false;
    try {
        sessionStorage.removeItem('royal-chat-history');
        const rawMarker = JSON.parse(sessionStorage.getItem(NAV_MARKER_KEY) || 'null');
        sessionStorage.removeItem(NAV_MARKER_KEY);
        const navEntry = performance.getEntriesByType?.('navigation')?.[0];
        const navType = navEntry?.type || 'navigate';

        const markerValid =
            rawMarker &&
            rawMarker.userKey === userKey.value &&
            Date.now() - Number(rawMarker.ts || 0) < 30000;
        const cameFromInternalNav =
            markerValid || (isSameOriginReferrer() && (navType === 'navigate' || navType === 'back_forward'));

        const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
        if (
            saved &&
            saved.userKey === userKey.value &&
            cameFromInternalNav &&
            Array.isArray(saved.messages) &&
            saved.messages.length > 0
        ) {
            messages.value = saved.messages
                .slice(-12)
                .map((item) =>
                    message(item.role, item.content, {
                        sentAt: item.sentAt || new Date().toISOString(),
                        status: item.status || (item.role === 'user' ? 'Đã gửi' : null),
                    })
                );
            if (saved.open) {
                open.value = true;
                nextTick(() => scrollToLatest(true));
            }
            restored = true;
        } else {
            clearLocalChatStorage();
            sendResetBeacon();
        }
    } catch {
        clearLocalChatStorage();
    }

    if (!restored) {
        messages.value = [message('assistant', defaultGreeting())];
        persist();
    }

    window.openRoyalChat = () => toggle(true);
    window.closeRoyalChat = () => toggle(false);
    window.toggleRoyalChat = (val) => toggle(val);

    document.addEventListener('keydown', escapeClose);
    document.addEventListener('click', handleDocumentClick, true);
    document.addEventListener('submit', handleDocumentSubmit, true);
    window.addEventListener('pagehide', handlePageHide);

    const unfinished = messages.value.at(-1);
    if (unfinished?.role === 'user') {
        messages.value.pop();
        open.value = true;
        void send(unfinished.content);
    }
});

onBeforeUnmount(() => {
    controller?.abort();
    clearTimeout(petTimer);
    document.removeEventListener('keydown', escapeClose);
    document.removeEventListener('click', handleDocumentClick, true);
    document.removeEventListener('submit', handleDocumentSubmit, true);
    window.removeEventListener('pagehide', handlePageHide);
});
</script>

<template>
    <button
        class="royal-chat__launcher"
        type="button"
        aria-label="Mở hỗ trợ trực tuyến"
        :aria-expanded="open"
        aria-controls="royal-chat-panel"
        @click="toggle()"
        @pointerenter="playPet('waving', 1000)"
        @pointermove="lookAtPointer"
        @pointerleave="lookDirection = null"
    >
        <span class="royal-chat__launcher-icon" aria-hidden="true">
            <span
                class="royal-chat__pico"
                :class="{ 'is-looking': petState === 'look' }"
                :style="petStyle"
                :data-pet-state="petState"
            ></span>
        </span>
    </button>

    <div
        v-show="open"
        id="royal-chat-panel"
        ref="panel"
        :class="['royal-chat__panel', { 'is-page-zoomed': pageZoomed, 'is-minimized': isMinimized }]"
        role="dialog"
        aria-modal="false"
        aria-labelledby="royal-chat-title"
        data-lenis-prevent
        @wheel.stop
    >
        <header class="royal-chat__header">
            <div class="royal-chat__identity">
                <span class="royal-chat__avatar-wrap" aria-hidden="true">
                    <span
                        class="royal-chat__pico royal-chat__pico--header"
                        :class="{ 'is-looking': petState === 'look' }"
                        :style="petStyle"
                        :data-pet-state="petState"
                    ></span>
                </span>
                <span class="royal-chat__identity-copy">
                    <strong id="royal-chat-title">Pico · Rosaliza Hotel</strong>
                    <small>{{ userId && userName ? `Xin chào ${userName}` : 'Trợ lý kỳ nghỉ trực tuyến' }}</small>
                </span>
            </div>
            <button
                class="royal-chat__close-btn"
                type="button"
                aria-label="Đóng cửa sổ"
                title="Đóng"
                @click.stop="closeChat"
            >
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </button>
        </header>

        <div
            ref="messageList"
            class="royal-chat__messages"
            role="log"
            aria-live="polite"
            aria-relevant="additions text"
            aria-label="Cuộc trò chuyện hỗ trợ trực tuyến"
            data-lenis-prevent
            @scroll.passive="onMessageScroll"
            @wheel.stop
        >
            <template v-for="(item, index) in messages" :key="`${index}-${item.role}`">
                <div
                    v-if="item.role !== 'assistant' || item.content || !typing"
                    :class="['royal-chat__row', `royal-chat__row--${item.role}`, { 'has-tail': isLastInRun(index) }]"
                    role="group"
                    :aria-label="item.role === 'user' ? 'Tin nhắn của bạn' : 'Tin nhắn phản hồi'"
                >
                    <div :class="['royal-chat__message', `royal-chat__message--${item.role}`]">
                        {{ item.content }}
                    </div>
                    <small v-if="item.role === 'user' && index === latestUserIndex" class="royal-chat__meta">
                        <time :datetime="item.sentAt">{{ timeLabel(item.sentAt) }}</time>
                        <span aria-hidden="true">·</span>
                        <span>{{ item.status }}</span>
                    </small>
                </div>
            </template>
            <div
                v-if="typing"
                class="royal-chat__row royal-chat__row--assistant has-tail"
                role="status"
                aria-label="Đang trả lời..."
            >
                <div class="royal-chat__message royal-chat__message--assistant royal-chat__message--typing">
                    <i></i><i></i><i></i>
                </div>
            </div>
        </div>

        <div v-if="messages.length <= 1" class="royal-chat__suggestions" aria-label="Câu hỏi gợi ý">
            <button v-for="suggestion in suggestions" :key="suggestion[0]" type="button" @click="send(suggestion[1])">
                {{ suggestion[0] }}
            </button>
        </div>

        <form class="royal-chat__composer" @submit.prevent="send()">
            <label class="sr-only" for="royal-chat-input">Nhập câu hỏi</label>
            <textarea
                id="royal-chat-input"
                ref="input"
                v-model="draft"
                rows="1"
                maxlength="1000"
                placeholder="Hỏi về kỳ nghỉ của bạn…"
                required
                @input="
                    resizeInput();
                    if (!pending) playPet('running', 650);
                "
                @keydown="keydown"
            ></textarea>
            <button type="submit" aria-label="Gửi câu hỏi" :disabled="pending"><span aria-hidden="true">↑</span></button>
        </form>
        <p class="royal-chat__privacy">Không chia sẻ mật khẩu, mã OTP hoặc thông tin thẻ</p>
    </div>
</template>

<style scoped>
.royal-chat__launcher { width: 72px !important; height: 72px !important; }
.royal-chat__launcher-icon { background: transparent !important; border: 0 !important; box-shadow: none !important; overflow: visible; }
.royal-chat__pico { display: block; flex: none; width: 66px; height: 72px; background-image: url('/images/chatbot/pico-atlas.png'); background-repeat: no-repeat; background-size: 800% 1100%; background-position: 0 var(--pico-row, 0%); transform-origin: 50% 90%; will-change: transform; transform: translateZ(0); animation: pico-idle .72s steps(var(--pico-steps, 5), end) infinite, pico-breathe 1.6s ease-in-out infinite; image-rendering: auto; }
.royal-chat__pico--header { width: 44px; height: 48px; }
.royal-chat__panel { bottom: 84px; width: min(404px, calc(100vw - 32px)) !important; height: min(640px, calc(100svh - 124px)) !important; border: 1px solid rgba(88,76,59,.14) !important; border-radius: 26px !important; background: rgba(255,255,255,.98) !important; box-shadow: 0 28px 80px rgba(37,31,24,.2), 0 2px 10px rgba(37,31,24,.08) !important; overscroll-behavior: contain !important; }
.royal-chat__header { justify-content: space-between !important; min-height: 76px; padding: 12px 14px 12px 12px !important; background: linear-gradient(135deg,#f7f2eb 0%,#fff 62%,#eef3f6 100%) !important; }
.royal-chat__identity { min-width: 0; gap: 11px !important; }
.royal-chat__close-btn { order: 2 !important; margin-left: auto !important; flex: 0 0 40px !important; width: 40px !important; height: 40px !important; min-width: 40px !important; min-height: 40px !important; max-width: 40px !important; max-height: 40px !important; padding: 0 !important; border-radius: 50% !important; aspect-ratio: 1 / 1 !important; background: rgba(255,255,255,.78) !important; border: 1px solid rgba(88,76,59,.14) !important; }
.royal-chat__messages { padding: 18px !important; background: linear-gradient(180deg,#fbfaf8 0%,#fff 30%) !important; overflow-y: auto !important; overflow-x: hidden !important; overscroll-behavior: contain !important; touch-action: pan-y !important; -webkit-overflow-scrolling: touch !important; }
.royal-chat__message--assistant { background: #f1eee9 !important; border-color: rgba(88,76,59,.08) !important; color: #2f2c28 !important; }
.royal-chat__message--user { background: #181715 !important; box-shadow: 0 8px 20px rgba(24,23,21,.14) !important; }
.royal-chat__suggestions { padding: 10px 16px 2px !important; }
.royal-chat__suggestions button { min-height: 40px; border-color: rgba(88,76,59,.18) !important; background: #fff !important; color: #443e37 !important; }
.royal-chat__composer { margin-bottom: 6px !important; border-color: rgba(88,76,59,.18) !important; background: #fbfaf8 !important; }
.royal-chat__composer:focus-within { border-color: #181715 !important; box-shadow: 0 0 0 3px rgba(24,23,21,.08) !important; }
.royal-chat__avatar-wrap { position: relative; display: grid; width: 50px; height: 50px; flex: 0 0 50px; place-items: center; overflow: hidden; border: 1px solid rgba(184,156,114,.26); border-radius: 15px; background: linear-gradient(145deg,#f6f1ea,#fff); }
.royal-chat__identity-copy { display: grid; min-width: 0; gap: 2px; }
.royal-chat__identity-copy strong { color: #181715; font-size: 14px; font-weight: 700; letter-spacing: -.02em; }
.royal-chat__identity-copy small { color: #5f5a53; font-size: 12px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.royal-chat__privacy { margin: 2px 18px 13px; color: #514c45; font-size: 12px; line-height: 1.45; text-align: center; }
.royal-chat__pico.is-looking { animation: pico-breathe 1.6s ease-in-out infinite; background-position: var(--pico-x) var(--pico-row, 0%); }
@keyframes pico-idle { from { background-position: 0 var(--pico-row, 0%); } to { background-position: var(--pico-end, 71.4286%) var(--pico-row, 0%); } }
@keyframes pico-breathe { 0%, 100% { transform: translate3d(0, 0, 0) scale(1); } 50% { transform: translate3d(0, -1.5px, 0) scale(1.025); } }
@media (max-width: 768px) {
    .royal-chat__launcher { display: grid !important; place-items: center !important; }
    .royal-chat__close-btn { flex: 0 0 44px !important; width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; max-width: 44px !important; max-height: 44px !important; }
    .royal-chat__panel { right: 10px !important; bottom: calc(88px + env(safe-area-inset-bottom, 0px)) !important; left: 10px !important; width: auto !important; height: min(620px, calc(100svh - 104px)) !important; max-height: calc(100svh - 104px) !important; border-radius: 22px !important; }
    .royal-chat__suggestions { display: flex !important; flex-wrap: wrap !important; gap: 8px !important; padding: 8px 14px 4px !important; overflow: visible !important; }
    .royal-chat__suggestions button { min-height: 40px !important; max-height: none !important; padding: 6px 13px !important; font-size: 12px !important; }
    .royal-chat__privacy { margin-bottom: 10px; }
}
</style>
