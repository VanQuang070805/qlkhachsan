import { BaseMessage, HumanMessage } from '@langchain/core/messages';

export const PICO_STYLE = `Điều chỉnh giọng theo cảm xúc và cách xưng hô trong các lượt gần đây, không tự gắn nhãn cảm xúc cho khách:
- Khi khách nói chuyện vui, thân mật hoặc gọi "bro", có thể đáp kiểu anh em, dí dỏm nhẹ và tự nhiên; không lặp "bro" trong mọi câu.
- Khi khách lo lắng, buồn, bối rối hoặc gặp lỗi, trả lời bình tĩnh, ấm áp, rõ bước xử lý; không pha trò.
- Khi khách bực bội hoặc phàn nàn, thừa nhận vấn đề và đi vào cách giải quyết; không cãi, không đùa, không dùng tiếng lóng. Tránh bảo khách "bình tĩnh" hay "thong thả". Với lỗi thanh toán, ưu tiên hướng dẫn kiểm tra trạng thái đơn/giao dịch trước khi thử lại để tránh thanh toán trùng; không cam kết kết quả khi chưa tra cứu được đơn.
- Khi hỏi tiền, giá, hủy, hoàn tiền, quyền riêng tư hoặc thông tin quan trọng, ưu tiên chính xác và ngắn gọn.
- Giữ mạch hội thoại: hiểu "nó", "loại đó", "còn cái này" từ các lượt trước; nếu vẫn mơ hồ thì hỏi một câu ngắn. Không giả vờ nhớ thông tin chưa có.`;

const ZONE = 'Asia/Ho_Chi_Minh';

export function hotelClock(now = new Date()): string {
  return new Intl.DateTimeFormat('vi-VN', {
    timeZone: ZONE,
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).format(now);
}

function normalized(text: string): string {
  return text.toLowerCase().normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
}

export function answerClockQuestion(query: string, now = new Date()): string | null {
  const text = normalized(query);
  if (/khach san|phong|dat phong|nhan phong|tra phong|rosaliza/.test(text)) return null;
  const asksTime = /\b(may gio|gio may|gio hien tai|ngay gio hien tai|bay gio la may gio|luc nay may gio)\b/.test(text);
  const asksDay = /\b(hom nay|ngay mai|bay gio|hien tai)\b/.test(text)
    && /\b(thu may|thu gi|ngay may|ngay bao nhieu|ngay nao|ngay thang nam|hom nay la ngay)\b/.test(text);
  if (!asksTime && !asksDay) return null;

  const target = /\bngay mai\b/.test(text) ? new Date(now.getTime() + 86_400_000) : now;
  const date = new Intl.DateTimeFormat('vi-VN', {
    timeZone: ZONE,
    weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric',
  }).format(target);
  if (target !== now) return `Ngày mai là ${date} ở Hà Nội nhé.`;
  if (!asksTime) return `Hôm nay là ${date} ở Hà Nội nhé.`;
  const time = new Intl.DateTimeFormat('vi-VN', {
    timeZone: ZONE, hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
  }).format(now);
  return `Bây giờ ở Hà Nội là ${time}, ${date} nhé.`;
}

export function recentConversation(messages: BaseMessage[]): string {
  return messages.slice(-6).map((message) =>
    `${message instanceof HumanMessage ? 'Khách' : 'Pico'}: ${String(message.content || '').slice(0, 500)}`,
  ).join('\n') || '(chưa có)';
}

export function retrievalQuestion(query: string, messages: BaseMessage[]): string {
  const text = normalized(query);
  if (text.length > 75 || !/\b(no|do|ay|the|vay|con|bao nhieu|gia thi sao)\b/.test(text)) return query;
  const previous = [...messages].reverse().find((message) => message instanceof HumanMessage);
  return previous ? `${String(previous.content).slice(0, 500)}\nCâu hỏi tiếp: ${query}` : query;
}
