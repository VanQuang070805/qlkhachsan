<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { gsap } from 'gsap';

const props = defineProps({ endpoint: { type: String, required: true } });
const open = ref(false);
const pending = ref(false);
const typing = ref(false);
const draft = ref('');
const messages = ref([]);
const messageList = ref(null);
const panel = ref(null);
const input = ref(null);
const pageZoomed = ref(false);
let controller = null;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const petAction = ref(null);
const lookDirection = ref(null);
let petTimer = null;
const petStates = {
    idle: { row: 0, frames: 6 }, 'running-right': { row: 1, frames: 8 },
    'running-left': { row: 2, frames: 8 }, waving: { row: 3, frames: 4 },
    jumping: { row: 4, frames: 5 }, failed: { row: 5, frames: 8 },
    waiting: { row: 6, frames: 6 }, running: { row: 7, frames: 6 },
    review: { row: 8, frames: 6 },
};
const petState = computed(() => petAction.value || (pending.value ? (typing.value ? 'waiting' : 'review') : (lookDirection.value !== null ? 'look' : 'idle')));
const petStyle = computed(() => {
    if (petState.value === 'look') {
        const i = lookDirection.value;
        return { '--pico-row': i < 8 ? '90%' : '100%', '--pico-x': `${(i % 8) * 100 / 7}%` };
    }
    const state = petStates[petState.value];
    return { '--pico-row': `${state.row * 10}%`, '--pico-end': `${(state.frames - 1) * 100 / 7}%`, '--pico-steps': state.frames - 1 };
});
function playPet(state, duration = 1100) {
    clearTimeout(petTimer);
    petAction.value = state;
    petTimer = setTimeout(() => { petAction.value = null; }, duration);
}
function lookAtPointer(event) {
    if (event.pointerType === 'touch') return;
    const rect = event.currentTarget.getBoundingClientRect();
    const dx = event.clientX - rect.left - rect.width / 2;
    const dy = event.clientY - rect.top - rect.height / 2;
    if (Math.hypot(dx, dy) < 8) { lookDirection.value = null; return; }
    lookDirection.value = (Math.round((Math.atan2(dx, -dy) + Math.PI * 2) / (Math.PI / 8)) % 16);
}


const suggestions = [
    ['Phòng cho 2 người', 'Gợi ý phòng cho 2 người'],
    ['Xem giá phòng', 'Giá phòng hiện tại thế nào?'],
    ['Cách đặt phòng', 'Hướng dẫn tôi đặt phòng'],
];

const latestUserIndex = computed(() => {
    for (let index = messages.value.length - 1; index >= 0; index -= 1) if (messages.value[index].role === 'user') return index;
    return -1;
});

function message(role, content, extras = {}) {
    return { role, content, sentAt: new Date().toISOString(), status: role === 'user' ? 'Đã gửi' : null, ...extras };
}

function persist() {
    sessionStorage.setItem('royal-chat-history', JSON.stringify(messages.value.slice(-10).map(({ role, content, sentAt, status }) => ({ role, content, sentAt, status }))));
}

function timeLabel(value) {
    const date = new Date(value || Date.now());
    return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit' }).format(date);
}

function isLastInRun(index) {
    return messages.value[index + 1]?.role !== messages.value[index]?.role;
}

async function scrollToLatest() {
    await nextTick();
    if (messageList.value) messageList.value.scrollTop = messageList.value.scrollHeight;
}

const isMinimized = ref(false);

async function toggle(next = !open.value) {
    open.value = next;
    playPet(next ? 'waving' : 'running-left', next ? 1200 : 800);
    if (!next) {
        isMinimized.value = false;
        pageZoomed.value = false;
    }
    await nextTick();
    if (next) {
        if (!reduceMotion) gsap.fromTo(panel.value, { autoAlpha: 0, y: 14, scale: .97 }, { autoAlpha: 1, y: 0, scale: 1, duration: .34, ease: 'power3.out', clearProps: 'opacity,transform,visibility' });
        input.value?.focus();
    }
}

function closeChat() {
    toggle(false);
}

function minimizeChat() {
    isMinimized.value = !isMinimized.value;
}

function zoomChat() {
    if (isMinimized.value) isMinimized.value = false;
    pageZoomed.value = !pageZoomed.value;
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
    persist();
    draft.value = '';
    pending.value = true;
    typing.value = true;
    playPet('running-right', 650);
    controller = new AbortController();
    await scrollToLatest();

    try {
        const response = await fetch(props.endpoint, {
            method: 'POST',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ message: content }),
        });
        if (!response.ok || !response.body) throw new Error('Request failed');
        const assistant = message('assistant', '');
        messages.value.push(assistant);
        const assistantIndex = messages.value.length - 1;
        const userIndex = latestUserIndex.value;
        if (userIndex >= 0) messages.value[userIndex].status = 'Đã đọc';
        typing.value = false;
        const reader = response.body.getReader();
        let responseFailed = false;
        const decoder = new TextDecoder();
        let buffer = '';
        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
            const events = buffer.split('\n\n');
            buffer = events.pop() || '';
            for (const event of events) {
                const line = event.split('\n').find(item => item.startsWith('data: '));
                if (!line) continue;
                const data = JSON.parse(line.slice(6));
                if (data.error) {
                    responseFailed = true;
                    if (!messages.value[assistantIndex].content) {
                        messages.value[assistantIndex].content = data.error;
                    }
                    break;
                }
                if (data.delta) messages.value[assistantIndex].content += data.delta;
            }
            await scrollToLatest();
            if (done) break;
        }
        if (!messages.value[assistantIndex].content) { messages.value[assistantIndex].content = 'Tôi chưa thể trả lời lúc này. Bạn vui lòng thử lại.'; responseFailed = true; }
        playPet(responseFailed ? 'failed' : 'jumping', responseFailed ? 1900 : 1100);
        persist();
    } catch (error) {
        if (error.name !== 'AbortError') {
            messages.value.push(message('assistant', 'Kết nối đang gián đoạn. Bạn vui lòng thử lại sau ít phút.'));
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

onMounted(() => {
    try {
        const saved = JSON.parse(sessionStorage.getItem('royal-chat-history') || '[]');
        messages.value = Array.isArray(saved) && saved.length
            ? saved.slice(-10).map(item => message(item.role, item.content, { sentAt: item.sentAt || new Date().toISOString(), status: item.status || (item.role === 'user' ? 'Đã gửi' : null) }))
            : [message('assistant', 'Xin chào, tôi có thể giúp bạn chọn phòng, xem giá hoặc hướng dẫn đặt kỳ nghỉ.')];
    } catch {
        messages.value = [message('assistant', 'Xin chào, tôi có thể giúp bạn chuẩn bị kỳ nghỉ tại Rosaliza Hotel.')];
    }
    window.openRoyalChat = () => toggle(true);
    window.toggleRoyalChat = (val) => toggle(val);
    document.addEventListener('keydown', escapeClose);
});

onBeforeUnmount(() => {
    controller?.abort();
    clearTimeout(petTimer);
    document.removeEventListener('keydown', escapeClose);
});
</script>

<template>
    <button class="royal-chat__launcher" type="button" aria-label="Mở hỗ trợ trực tuyến" :aria-expanded="open" aria-controls="royal-chat-panel" @click="toggle()" @pointerenter="playPet('waving', 1000)" @pointermove="lookAtPointer" @pointerleave="lookDirection = null">
        <span class="royal-chat__launcher-icon" aria-hidden="true"><span class="royal-chat__pico" :class="{ 'is-looking': petState === 'look' }" :style="petStyle" :data-pet-state="petState"></span></span>
    </button>

    <div v-show="open" id="royal-chat-panel" ref="panel" :class="['royal-chat__panel', { 'is-page-zoomed': pageZoomed, 'is-minimized': isMinimized }]" role="dialog" aria-modal="false" aria-labelledby="royal-chat-title">
        <header class="royal-chat__header">
            <div class="royal-chat__identity">
                <span class="royal-chat__avatar-wrap" aria-hidden="true">
                    <span class="royal-chat__pico royal-chat__pico--header" :class="{ 'is-looking': petState === 'look' }" :style="petStyle" :data-pet-state="petState"></span>
                    <span class="presence-dot"><i></i><b></b></span>
                </span>
                <span class="royal-chat__identity-copy">
                    <strong id="royal-chat-title">Pico Concierge</strong>
                    <small>Trợ lý kỳ nghỉ của Rosaliza</small>
                </span>
            </div>
            <button class="royal-chat__close-btn" type="button" aria-label="Đóng cửa sổ" title="Đóng" @click.stop="closeChat">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </header>

        <div ref="messageList" class="royal-chat__messages" role="log" aria-live="polite" aria-relevant="additions text" aria-label="Cuộc trò chuyện hỗ trợ trực tuyến">
            <div v-for="(item, index) in messages" :key="`${index}-${item.role}`" :class="['royal-chat__row', `royal-chat__row--${item.role}`, { 'has-tail': isLastInRun(index) }]" role="group" :aria-label="item.role === 'user' ? 'Tin nhắn của bạn' : 'Tin nhắn phản hồi'">
                <div :class="['royal-chat__message', `royal-chat__message--${item.role}`]">
                    {{ item.content }}
                </div>
                <small v-if="item.role === 'user' && index === latestUserIndex" class="royal-chat__meta"><time :datetime="item.sentAt">{{ timeLabel(item.sentAt) }}</time><span aria-hidden="true">·</span><span>{{ item.status }}</span></small>
            </div>
            <div v-if="typing" class="royal-chat__row royal-chat__row--assistant has-tail" role="status" aria-label="Đang trả lời..."><div class="royal-chat__message royal-chat__message--assistant royal-chat__message--typing"><i></i><i></i><i></i></div></div>
        </div>

        <div v-if="messages.length <= 1" class="royal-chat__suggestions" aria-label="Câu hỏi gợi ý">
            <button v-for="suggestion in suggestions" :key="suggestion[0]" type="button" @click="send(suggestion[1])">{{ suggestion[0] }}</button>
        </div>

        <form class="royal-chat__composer" @submit.prevent="send()">
            <label class="sr-only" for="royal-chat-input">Nhập câu hỏi</label>
            <textarea id="royal-chat-input" ref="input" v-model="draft" rows="1" maxlength="1000" placeholder="Hỏi về kỳ nghỉ của bạn…" required @input="resizeInput(); if (!pending) playPet('running', 650)" @keydown="keydown"></textarea>
            <button type="submit" aria-label="Gửi câu hỏi" :disabled="pending"><span aria-hidden="true">↑</span></button>
        </form>
        <p class="royal-chat__privacy">Không chia sẻ mật khẩu, mã OTP hoặc thông tin thẻ</p>
    </div>
</template>

<style scoped>
.royal-chat__launcher { width: 72px !important; height: 72px !important; }
.royal-chat__launcher-icon { background: transparent !important; border: 0 !important; box-shadow: none !important; overflow: visible; }
.royal-chat__pico { display: block; flex: none; width: 66px; height: 72px; background-image: url('/images/chatbot/pico-atlas.png'); background-repeat: no-repeat; background-size: 800% 1100%; background-position: 0 var(--pico-row, 0%); transform-origin: 50% 90%; animation: pico-idle 1.2s steps(var(--pico-steps, 5), end) infinite, pico-breathe 2.8s ease-in-out infinite; image-rendering: auto; }
.royal-chat__pico--header { width: 44px; height: 48px; }
.royal-chat__panel { bottom: 84px; width: min(404px, calc(100vw - 32px)) !important; height: min(640px, calc(100svh - 124px)) !important; border: 1px solid rgba(88,76,59,.14) !important; border-radius: 26px !important; background: rgba(255,255,255,.98) !important; box-shadow: 0 28px 80px rgba(37,31,24,.2), 0 2px 10px rgba(37,31,24,.08) !important; }
.royal-chat__header { justify-content: space-between !important; min-height: 76px; padding: 12px 14px 12px 12px !important; background: linear-gradient(135deg,#f7f2eb 0%,#fff 62%,#eef3f6 100%) !important; }
.royal-chat__identity { min-width: 0; gap: 11px !important; }
.royal-chat__close-btn { order: 2 !important; margin-left: auto !important; flex: 0 0 34px !important; width: 34px !important; height: 34px !important; background: rgba(255,255,255,.72) !important; border: 1px solid rgba(88,76,59,.12) !important; }
.royal-chat__messages { padding: 18px !important; background: linear-gradient(180deg,#fbfaf8 0%,#fff 30%) !important; }
.royal-chat__message--assistant { background: #f1eee9 !important; border-color: rgba(88,76,59,.08) !important; color: #2f2c28 !important; }
.royal-chat__message--user { background: #181715 !important; box-shadow: 0 8px 20px rgba(24,23,21,.14) !important; }
.royal-chat__suggestions { padding: 10px 16px 2px !important; }
.royal-chat__suggestions button { min-height: 36px; border-color: rgba(88,76,59,.15) !important; background: #fff !important; color: #554f47 !important; }
.royal-chat__composer { margin-bottom: 6px !important; border-color: rgba(88,76,59,.18) !important; background: #fbfaf8 !important; }
.royal-chat__composer:focus-within { border-color: #181715 !important; box-shadow: 0 0 0 3px rgba(24,23,21,.08) !important; }
.royal-chat__avatar-wrap { position: relative; display: grid; width: 50px; height: 50px; flex: 0 0 50px; place-items: center; overflow: hidden; border: 1px solid rgba(184,156,114,.26); border-radius: 15px; background: linear-gradient(145deg,#f6f1ea,#fff); }
.royal-chat__avatar-wrap .presence-dot { position: absolute; right: 2px; bottom: 2px; }
.royal-chat__identity-copy { display: grid; min-width: 0; gap: 2px; }
.royal-chat__identity-copy strong { color: #181715; font-size: 14px; font-weight: 700; letter-spacing: -.02em; }
.royal-chat__identity-copy small { color: #5f5a53; font-size: 12px; line-height: 1.4; }
.royal-chat__privacy { margin: 2px 18px 13px; color: #514c45; font-size: 12px; line-height: 1.45; text-align: center; }
.royal-chat__pico.is-looking { animation: pico-breathe 2.8s ease-in-out infinite; background-position: var(--pico-x) var(--pico-row, 0%); }
@keyframes pico-idle { from { background-position: 0 var(--pico-row, 0%); } to { background-position: var(--pico-end, 71.4286%) var(--pico-row, 0%); } }
@keyframes pico-breathe { 0%, 100% { transform: translate3d(0, 0, 0) scale(1); } 50% { transform: translate3d(0, -1.5px, 0) scale(1.025); } }
@media (max-width: 768px) {
    .royal-chat__launcher { display: none !important; }
    .royal-chat__panel { right: 10px !important; bottom: calc(82px + env(safe-area-inset-bottom, 0px)) !important; left: 10px !important; width: auto !important; height: min(620px, calc(100svh - 104px)) !important; max-height: calc(100svh - 104px) !important; border-radius: 22px !important; }
    .royal-chat__privacy { margin-bottom: 10px; }
}
</style>
