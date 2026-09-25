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

async function toggle(next = !open.value) {
    open.value = next;
    await nextTick();
    if (next) {
        if (!reduceMotion) gsap.fromTo(panel.value, { autoAlpha: 0, y: 14, scale: .97 }, { autoAlpha: 1, y: 0, scale: 1, duration: .34, ease: 'power3.out', clearProps: 'opacity,transform,visibility' });
        input.value?.focus();
    }
}

function minimizeChat() {
    toggle(false);
}

async function zoomChat(event) {
    if (event.altKey) {
        pageZoomed.value = !pageZoomed.value;
        return;
    }
    if (document.fullscreenElement === panel.value) await document.exitFullscreen?.();
    else if (panel.value?.requestFullscreen) {
        try {
            await panel.value.requestFullscreen();
            if (document.fullscreenElement !== panel.value) pageZoomed.value = !pageZoomed.value;
        } catch {
            pageZoomed.value = !pageZoomed.value;
        }
    } else pageZoomed.value = !pageZoomed.value;
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

    const history = messages.value.slice(-8).map(({ role, content }) => ({ role, content }));
    messages.value.push(message('user', content));
    persist();
    draft.value = '';
    pending.value = true;
    typing.value = true;
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
            body: JSON.stringify({ message: content, history }),
        });
        if (!response.ok || !response.body) throw new Error('Request failed');
        const assistant = message('assistant', '', { sources: [] });
        messages.value.push(assistant);
        const assistantIndex = messages.value.length - 1;
        const userIndex = latestUserIndex.value;
        if (userIndex >= 0) messages.value[userIndex].status = 'Đã đọc';
        typing.value = false;
        const reader = response.body.getReader();
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
                if (data.delta) messages.value[assistantIndex].content += data.delta;
                if (data.done) messages.value[assistantIndex].sources = Array.isArray(data.sources) ? data.sources : [];
            }
            await scrollToLatest();
            if (done) break;
        }
        if (!messages.value[assistantIndex].content) messages.value[assistantIndex].content = 'Tôi chưa thể trả lời lúc này. Bạn vui lòng thử lại.';
        persist();
    } catch (error) {
        if (error.name !== 'AbortError') messages.value.push(message('assistant', 'Kết nối đang gián đoạn. Bạn vui lòng thử lại sau ít phút.'));
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
        messages.value = [message('assistant', 'Xin chào, tôi có thể giúp bạn chuẩn bị kỳ nghỉ tại Royal Hotel.')];
    }
    document.addEventListener('keydown', escapeClose);
});

onBeforeUnmount(() => {
    controller?.abort();
    document.removeEventListener('keydown', escapeClose);
});
</script>

<template>
    <button class="royal-chat__launcher" type="button" aria-label="Mở Royal Concierge" :aria-expanded="open" aria-controls="royal-chat-panel" @click="toggle()">
        <span class="royal-chat__launcher-icon" aria-hidden="true">✦</span>
    </button>

    <div v-show="open" id="royal-chat-panel" ref="panel" :class="['royal-chat__panel', { 'is-page-zoomed': pageZoomed }]" role="dialog" aria-modal="false" aria-labelledby="royal-chat-title">
        <header class="royal-chat__header">
            <div class="window-controls royal-chat__window-controls" role="group" aria-label="Điều khiển cửa sổ Royal Concierge">
                <button class="window-control window-control--close" type="button" aria-label="Đóng Royal Concierge" @click="toggle(false)"></button>
                <button class="window-control window-control--minimize" type="button" aria-label="Thu gọn Royal Concierge về biểu tượng" @click="minimizeChat"></button>
                <button class="window-control window-control--zoom" type="button" aria-label="Toàn màn hình Royal Concierge" @click="zoomChat"></button>
            </div>
            <div class="royal-chat__identity">
                <span class="royal-chat__avatar" aria-hidden="true">R</span>
                <div><strong id="royal-chat-title">Royal Concierge</strong><small><span class="presence-dot" aria-hidden="true"><i></i><b></b></span> Sẵn sàng hỗ trợ</small></div>
            </div>
        </header>

        <div ref="messageList" class="royal-chat__messages" role="log" aria-live="polite" aria-relevant="additions text" aria-label="Cuộc trò chuyện với Royal Concierge">
            <div v-for="(item, index) in messages" :key="`${index}-${item.role}`" :class="['royal-chat__row', `royal-chat__row--${item.role}`, { 'has-tail': isLastInRun(index) }]" role="group" :aria-label="item.role === 'user' ? 'Tin nhắn của bạn' : 'Tin nhắn từ Royal Concierge'">
                <div :class="['royal-chat__message', `royal-chat__message--${item.role}`]">
                    {{ item.content }}
                    <small v-if="item.sources?.length" class="royal-chat__sources">Nguồn: {{ item.sources.join(' · ') }}</small>
                </div>
                <small v-if="item.role === 'user' && index === latestUserIndex" class="royal-chat__meta"><time :datetime="item.sentAt">{{ timeLabel(item.sentAt) }}</time><span aria-hidden="true">·</span><span>{{ item.status }}</span></small>
            </div>
            <div v-if="typing" class="royal-chat__row royal-chat__row--assistant has-tail" role="status" aria-label="Royal Concierge đang trả lời"><div class="royal-chat__message royal-chat__message--assistant royal-chat__message--typing"><i></i><i></i><i></i></div></div>
        </div>

        <div v-if="messages.length <= 1" class="royal-chat__suggestions" aria-label="Câu hỏi gợi ý">
            <button v-for="suggestion in suggestions" :key="suggestion[0]" type="button" @click="send(suggestion[1])">{{ suggestion[0] }}</button>
        </div>

        <form class="royal-chat__composer" @submit.prevent="send()">
            <label class="sr-only" for="royal-chat-input">Nhập câu hỏi</label>
            <textarea id="royal-chat-input" ref="input" v-model="draft" rows="1" maxlength="1000" placeholder="Hỏi về kỳ nghỉ của bạn…" required @input="resizeInput" @keydown="keydown"></textarea>
            <button type="submit" aria-label="Gửi câu hỏi" :disabled="pending"><span aria-hidden="true">↑</span></button>
        </form>
        <p class="royal-chat__note">Câu trả lời dùng dữ liệu hiện tại và tài liệu Royal Hotel.</p>
    </div>
</template>
