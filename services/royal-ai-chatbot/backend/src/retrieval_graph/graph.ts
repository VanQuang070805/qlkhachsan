import { StateGraph, START, END } from '@langchain/langgraph';
import { AgentStateAnnotation } from './state.js';
import { getMySQLPool, makeRetriever } from '../shared/retrieval.js';
import { formatDocs } from './utils.js';
import { AIMessage, BaseMessage, HumanMessage, SystemMessage } from '@langchain/core/messages';
import { z } from 'zod';
import { RESPONSE_SYSTEM_PROMPT, ROUTER_SYSTEM_PROMPT } from './prompts.js';
import { answerClockQuestion, hotelClock, PICO_STYLE, recentConversation, retrievalQuestion } from './conversation.js';
import { fetchWeather, isWeatherQuestion, weatherLocation } from './weather.js';
import { RunnableConfig } from '@langchain/core/runnables';
import {
  AgentConfigurationAnnotation,
  ensureAgentConfiguration,
} from './configuration.js';
import {
  invokeChatModelWithFallback,
  loadChatModel,
  withAIRequestTimeout,
} from '../shared/utils.js';

const HOTEL_FACT_KEYWORDS = [
  'giá phòng',
  'gia phong',
  'đặt phòng',
  'dat phong',
  'đặt cọc',
  'dat coc',
  'tiền cọc',
  'tien coc',
  'thanh toán',
  'thanh toan',
  'vietqr',
  'momo',
  'zalopay',
  'vnpay',
  'hạng phòng',
  'hang phong',
  'loại phòng',
  'loai phong',
  'phòng trống',
  'phong trong',
  'còn phòng',
  'con phong',
  'trả phòng',
  'tra phong',
  'nhận phòng',
  'nhan phong',
  'khách sạn',
  'khach san',
  'check-in',
  'check in',
  'check-out',
  'check out',
  'booking',
  'suite',
  'deluxe',
  'standard',
  'triple',
  'vip',
  'rosaliza',
  'phụ thu',
  'phu thu',
  'gia hạn',
  'gia han',
  'hoàn tiền',
  'hoan tien',
  'hủy phòng',
  'huy phong',
  'hủy đơn',
  'huy don',
  'đơn của tôi',
  'don cua toi',
  'địa chỉ',
  'dia chi',
  'ở đâu',
  'o dau',
  'hotline',
  'liên hệ',
  'lien he',
  'số điện thoại',
  'so dien thoai',
  'tiện nghi',
  'tien nghi',
  'dọn phòng',
  'don phong',
  'face id',
  'faceid',
  'lễ tân',
  'le tan',
];

const NON_HOTEL_FALSE_POSITIVES =
  /\b(phòng khám|phong kham|văn phòng|van phong|phòng ban|phong ban|phòng thí nghiệm|giá vàng|gia vang|giá xăng|gia xang|giá cổ phiếu|chứng khoán)\b/i;

function isHotelQuery(query: string): boolean {
  if (NON_HOTEL_FALSE_POSITIVES.test(query)) return false;
  const lower = query.toLowerCase();
  const normalized = lower
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd');
  if (
    /\b(gia|dat|hang|loai|con|huy|thue|phu thu|tra|nhan|tim|coc)\s+phog\b/.test(
      normalized,
    )
  ) {
    return true;
  }
  if (HOTEL_FACT_KEYWORDS.some((kw) => lower.includes(kw))) {
    return true;
  }
  return /\b(phòng|phong|lưu trú|luu tru|kỳ nghỉ|ky nghi)\b/i.test(query);
}

function isPriceOrRoomTypeQuery(query: string): boolean {
  const lower = query.toLowerCase();
  return [
    'giá',
    'gia',
    'bao nhiêu',
    'bao nhieu',
    'hạng phòng',
    'hang phong',
    'loại phòng',
    'loai phong',
    'sức chứa',
    'suc chua',
    'rẻ nhất',
    're nhat',
    'đắt nhất',
    'dat nhat',
    'phòng trống',
    'phong trong',
    'còn phòng',
    'con phong',
    'tìm phòng',
    'tim phong',
    'người',
    'nguoi',
    'khách',
    'khach',
    'cọc',
    'coc',
  ].some((kw) => lower.includes(kw));
}

function isMyBookingQuery(query: string): boolean {
  const lower = query.toLowerCase();
  return [
    'đơn của tôi',
    'don cua toi',
    'đặt phòng của tôi',
    'dat phong cua toi',
    'booking của tôi',
    'booking cua toi',
    'lịch sử đặt',
    'lich su dat',
    'tôi đã đặt',
    'toi da dat',
    'mã đặt phòng của tôi',
    'my booking',
  ].some((kw) => lower.includes(kw));
}

function hanoiDateParts(offsetDays = 0): { year: number; month: number; day: number; iso: string } {
  const target = new Date(Date.now() + offsetDays * 86_400_000);
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Ho_Chi_Minh',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(target);
  const year = Number(parts.find((p) => p.type === 'year')?.value || target.getUTCFullYear());
  const month = Number(parts.find((p) => p.type === 'month')?.value || target.getUTCMonth() + 1);
  const day = Number(parts.find((p) => p.type === 'day')?.value || target.getUTCDate());
  const iso = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
  return { year, month, day, iso };
}

function parseFlexibleDates(text: string): string[] {
  const found: string[] = [];
  const today = hanoiDateParts(0);

  for (const m of text.matchAll(/\b(20\d{2})-(\d{2})-(\d{2})\b/g)) {
    found.push(`${m[1]}-${m[2]}-${m[3]}`);
  }

  for (const m of text.matchAll(/\b(\d{1,2})[/-](\d{1,2})(?:[/-](20\d{2}))?\b/g)) {
    const day = Number(m[1]);
    const month = Number(m[2]);
    if (day < 1 || day > 31 || month < 1 || month > 12) continue;
    let year = m[3] ? Number(m[3]) : today.year;
    if (!m[3] && (month < today.month || (month === today.month && day < today.day))) {
      year += 1;
    }
    found.push(
      `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
    );
  }

  if (found.length === 0) {
    if (/\b(hôm nay|hom nay|tối nay|toi nay|bây giờ|bay gio)\b/i.test(text)) {
      found.push(hanoiDateParts(0).iso);
    }
    if (/\b(ngày mai|ngay mai|tối mai|toi mai|\bmai\b)\b/i.test(text)) {
      found.push(hanoiDateParts(1).iso);
    }
    if (/\b(ngày kia|ngay kia|ngày mốt|ngay mot)\b/i.test(text)) {
      found.push(hanoiDateParts(2).iso);
    }
  }

  const durMatch = text.match(/\b(?:trả|tra|đến|den|sau|ở|o)?\s*(\d{1,2})\s*(đêm|dem|hôm nữa|hom nua|ngày nữa|ngay nua|hôm|hom|ngày|ngay)\b/i);
  if ((found.length as number) === 1 && durMatch) {
    const nights = Math.max(1, Math.min(30, Number(durMatch[1])));
    const baseMs = Date.parse(`${found[0]}T00:00:00Z`);
    if (!Number.isNaN(baseMs)) {
      const out = new Date(baseMs + nights * 86_400_000).toISOString().slice(0, 10);
      found.push(out);
    }
  } else if ((found.length as number) === 0 && durMatch) {
    const nights = Math.max(1, Math.min(30, Number(durMatch[1])));
    const startIso = hanoiDateParts(0).iso;
    const baseMs = Date.parse(`${startIso}T00:00:00Z`);
    if (!Number.isNaN(baseMs)) {
      const out = new Date(baseMs + nights * 86_400_000).toISOString().slice(0, 10);
      found.push(startIso, out);
    }
  }

  return found;
}

function extractSearchCriteria(
  query: string,
  history: BaseMessage[],
): {
  check_in?: string;
  check_out?: string;
  guests?: number;
  rooms?: number;
} {
  // Inspect only HumanMessages so assistant replies like "còn 5 phòng" never corrupt criteria.
  const humanHistory = history
    .filter((m) => m instanceof HumanMessage)
    .slice(-3)
    .map((m) => String(m.content || ''));
  const combinedText = [...humanHistory, query].join(' ');

  const dates = parseFlexibleDates(query);
  const effectiveDates = dates.length >= 2 ? dates : parseFlexibleDates(combinedText);

  let checkIn: string | undefined;
  let checkOut: string | undefined;
  if (effectiveDates.length >= 2) {
    checkIn = effectiveDates[effectiveDates.length - 2];
    checkOut = effectiveDates[effectiveDates.length - 1];
  }

  let guests: number | undefined;
  const adultMatches = Array.from(
    combinedText.matchAll(/(\d+)\s*(người lớn|nguoi lon)/gi),
  );
  const childMatches = Array.from(
    combinedText.matchAll(/(\d+)\s*(trẻ em|tre em|bé|be)/gi),
  );
  if (adultMatches.length > 0 || childMatches.length > 0) {
    const adults = adultMatches.length > 0 ? Number(adultMatches[adultMatches.length - 1][1]) : 1;
    const children = childMatches.length > 0 ? Number(childMatches[childMatches.length - 1][1]) : 0;
    guests = Math.max(1, adults + children);
  } else {
    const guestMatches = Array.from(
      combinedText.matchAll(/(\d+)\s*(người|nguoi|khách|khach)/gi),
    );
    if (guestMatches.length > 0) {
      guests = Number(guestMatches[guestMatches.length - 1][1]);
    }
  }

  let rooms = 1;
  const roomMatches = Array.from(
    combinedText.matchAll(/(\d+)\s*(phòng|phong)/gi),
  );
  if (roomMatches.length > 0) {
    rooms = Math.max(
      1,
      Math.min(10, Number(roomMatches[roomMatches.length - 1][1])),
    );
  }

  return { check_in: checkIn, check_out: checkOut, guests, rooms };
}

const prefetchedToolsCache = new Map<string, { promise: Promise<string>; expires: number }>();

function toolPrefetchKey(query: string, config?: RunnableConfig): string {
  const corr = String(config?.configurable?.correlationId || '');
  const fp = String(config?.configurable?.dedupeFingerprint || '');
  return `${corr}|${fp}|${query}`;
}

let cachedDirectRoomTypes: { data: Record<string, unknown>; expires: number } | null =
  null;

async function fetchRoomTypesFast(
  baseUrl: string,
  headers: Record<string, string>,
): Promise<Record<string, unknown> | null> {
  if ((process.env.VECTOR_PROVIDER || 'mysql') === 'mysql') {
    if (cachedDirectRoomTypes && cachedDirectRoomTypes.expires > Date.now()) {
      return cachedDirectRoomTypes.data;
    }
    try {
      const [rows] = await getMySQLPool().query<any[]>(
        'SELECT id, type_name, price, max_adults, max_children, max_guests, description FROM room_types ORDER BY price ASC',
      );
      if (Array.isArray(rows) && rows.length > 0) {
        const payload = {
          room_types: rows.map((row) => ({
            id: Number(row.id),
            type_name: String(row.type_name || ''),
            price_per_night: Math.round(Number(row.price || 0)),
            base_price_vnd: Math.round(Number(row.price || 0)),
            max_adults: Number(row.max_adults || 0),
            max_children: Number(row.max_children || 0),
            max_guests: Number(row.max_guests || 0),
            description: String(row.description || ''),
          })),
          queried_at: new Date().toISOString(),
        };
        cachedDirectRoomTypes = { data: payload, expires: Date.now() + 15_000 };
        return payload;
      }
    } catch {
      // Fall back to internal Laravel HTTP endpoint below when direct MySQL read is unavailable.
    }
  }

  const rtRes = await fetch(`${baseUrl}/chatbot/tools/room-types`, {
    method: 'POST',
    headers,
    body: JSON.stringify({}),
    signal: AbortSignal.timeout(4500),
  });
  if (rtRes.ok) {
    return (await rtRes.json()) as Record<string, unknown>;
  }
  return null;
}

async function callLaravelPublicTools(
  query: string,
  history: BaseMessage[],
  config?: RunnableConfig,
): Promise<string> {
  const baseUrl = (
    process.env.LARAVEL_INTERNAL_API_URL || 'http://127.0.0.1:18084/api'
  ).replace(/\/+$/, '');
  const toolSecret = (
    process.env.LARAVEL_TOOL_API_KEY ||
    process.env.LANGGRAPH_TOOL_API_KEY ||
    ''
  ).trim();

  if (!toolSecret) {
    return '<live_hotel_tools status="unconfigured">Công cụ tra cứu dữ liệu trực tiếp chưa được cấu hình khóa nội bộ.</live_hotel_tools>';
  }

  const agentConfig = config ? ensureAgentConfiguration(config) : null;
  const capabilityToken = agentConfig?.capabilityToken || '';
  const actorScope = agentConfig?.actorScope || 'public';
  const correlationId = agentConfig?.correlationId || '';

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    Authorization: `Bearer ${toolSecret}`,
  };
  if (capabilityToken) {
    headers['X-Chatbot-Capability'] = capabilityToken;
  }
  if (correlationId) {
    headers['X-Correlation-ID'] = correlationId;
  }

  const results: Record<string, unknown> = {
    queried_at: new Date().toISOString(),
  };

  const criteria = extractSearchCriteria(query, history);
  const viewingRoomInReality = Boolean(
    agentConfig?.userReality &&
      agentConfig.userReality.includes('Đang xem hạng phòng'),
  );
  const needRoomTypes =
    isPriceOrRoomTypeQuery(query) ||
    Boolean(criteria.check_in && criteria.check_out) ||
    (viewingRoomInReality && /\b(phòng này|phong nay|hạng này|hang nay|đang xem|dang xem)\b/i.test(query));
  const needSearch = Boolean(
    criteria.check_in && criteria.check_out,
  );
  const needMyBookings = Boolean(
    isMyBookingQuery(query) &&
      capabilityToken &&
      actorScope.startsWith('customer:'),
  );

  if (!needRoomTypes && !needSearch && !needMyBookings) {
    return `<live_hotel_tools>\n${JSON.stringify(results)}\n</live_hotel_tools>`;
  }

  try {
    const tasks: Array<Promise<void>> = [];

    if (needRoomTypes) {
      tasks.push(
        fetchRoomTypesFast(baseUrl, headers).then((payload) => {
          if (payload) {
            results.room_types = payload;
          }
        }),
      );
    }

    if (needSearch) {
      tasks.push(
        fetch(`${baseUrl}/chatbot/tools/rooms/search`, {
          method: 'POST',
          headers,
          body: JSON.stringify({
            check_in: criteria.check_in,
            check_out: criteria.check_out,
            guests: criteria.guests || 1,
            rooms: criteria.rooms || 1,
          }),
          signal: AbortSignal.timeout(4500),
        }).then(async (searchRes) => {
          results.availability_search = await searchRes.json();
        }),
      );
    }

    if (needMyBookings) {
      tasks.push(
        fetch(`${baseUrl}/chatbot/tools/my-bookings`, {
          method: 'POST',
          headers,
          body: JSON.stringify({}),
          signal: AbortSignal.timeout(4500),
        }).then(async (myBookingsRes) => {
          if (myBookingsRes.ok) {
            results.my_bookings = await myBookingsRes.json();
          }
        }),
      );
    }

    await Promise.all(tasks);
  } catch {
    results.tool_warning =
      'Không thể kết nối tức thời tới công cụ phòng trực tiếp; không tự đoán giá hoặc phòng trống.';
  }

  return `<live_hotel_tools>\n${JSON.stringify(results)}\n</live_hotel_tools>`;
}

function getOrStartToolPrefetch(
  query: string,
  history: BaseMessage[],
  config?: RunnableConfig,
): Promise<string> {
  const key = toolPrefetchKey(query, config);
  const existing = prefetchedToolsCache.get(key);
  if (existing && existing.expires > Date.now()) {
    return existing.promise;
  }

  const promise = callLaravelPublicTools(query, history, config);
  if (prefetchedToolsCache.size >= 100) {
    const oldest = prefetchedToolsCache.keys().next().value;
    if (oldest) prefetchedToolsCache.delete(oldest);
  }
  prefetchedToolsCache.set(key, { promise, expires: Date.now() + 8000 });
  return promise;
}

async function checkQueryType(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<{
  route: 'retrieve' | 'direct';
}> {
  const q = (state.query || '').trim();
  if (answerClockQuestion(q)) {
    return { route: 'direct' };
  }

  if (isWeatherQuestion(q)) {
    return { route: 'direct' };
  }

  if (isHotelQuery(q)) {
    return { route: 'retrieve' };
  }

  if (
    /\b(phòng này|phong nay|hạng này|hang nay|đang xem|dang xem|đơn của tôi|don cua toi|booking của tôi|booking cua toi)\b/i.test(
      q,
    )
  ) {
    return { route: 'retrieve' };
  }

  const lastUserQuestion = [...(state.messages || [])]
    .reverse()
    .find((message) => message instanceof HumanMessage);
  const followup =
    /^(còn|con|thế|the|vậy|vay|nó|no|loại đó|loai do|bao nhiêu|bao nhieu|giá thì sao|gia thi sao|nếu|neu|quá giờ|qua gio|sớm hơn|som hon)/i.test(
      q,
    );
  if (
    followup &&
    lastUserQuestion &&
    isHotelQuery(String(lastUserQuestion.content || ''))
  ) {
    return { route: 'retrieve' };
  }

  if (
    /^(chào|xin chào|hello|hi\b|cảm ơn|cám ơn|kể (mình|tôi) (một )?chuyện cười|bạn (có )?khỏe không)/i.test(
      q,
    )
  ) {
    return { route: 'direct' };
  }

  if (
    NON_HOTEL_FALSE_POSITIVES.test(q) ||
    !/phòng|phong|phog|khách sạn|khach san|đặt|dat|hủy|huy|hoàn tiền|hoan tien|cọc|coc/i.test(
      q,
    )
  ) {
    return { route: 'direct' };
  }

  const schema = z.object({
    route: z.enum(['retrieve', 'direct']),
    directAnswer: z.string().optional(),
  });

  try {
    const configuration = ensureAgentConfiguration(config);
    const model = await loadChatModel(configuration.queryModel);
    const formattedPrompt = await ROUTER_SYSTEM_PROMPT.invoke({
      query: state.query,
      history: recentConversation(state.messages || []),
    });

    const structuredModel = model.withStructuredOutput(schema);
    const response = await withAIRequestTimeout((signal) =>
      structuredModel.invoke(formattedPrompt.toString(), { signal }),
    );

    return { route: response.route || 'direct' };
  } catch {
    return { route: 'retrieve' };
  }
}

async function answerQueryDirectly(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<typeof AgentStateAnnotation.Update> {
  const configuration = ensureAgentConfiguration(config);
  const userHumanMessage = new HumanMessage(state.query);
  const clockAnswer = answerClockQuestion(state.query);
  if (clockAnswer) {
    return { messages: [userHumanMessage, new AIMessage(clockAnswer)] };
  }

  let weatherContext = '';
  if (isWeatherQuestion(state.query)) {
    const place = weatherLocation(state.query, state.messages || []);
    try {
      const weather = await fetchWeather(place);
      if (!weather) {
        return {
          messages: [
            userHumanMessage,
            new AIMessage(
              `Mình chưa xác định được địa điểm "${place}". Bạn cho mình tên thành phố hoặc quốc gia cụ thể hơn nhé.`,
            ),
          ],
        };
      }
      weatherContext = `\nDữ liệu thời tiết trực tiếp từ Open-Meteo, cập nhật lúc ${weather.observedAt} theo múi giờ ${weather.timezone}: ${JSON.stringify(weather)}. Chỉ dùng các số liệu này để trả lời thời tiết; không tự suy đoán. Nêu tên địa điểm và thời điểm của số liệu. Xác suất mưa cực đại trong ngày không đồng nghĩa chắc chắn mưa.`;
    } catch {
      return {
        messages: [
          userHumanMessage,
          new AIMessage(
            'Mình chưa lấy được dữ liệu thời tiết trực tiếp lúc này, nên không muốn đoán bừa. Bạn thử lại sau ít phút nhé.',
          ),
        ],
      };
    }
  }

  const realityBlock = configuration.userReality
    ? `\nThực tại của người dùng trên hệ thống lúc này (dữ liệu xác thực từ máy chủ):\n<user_reality>\n${configuration.userReality}\n</user_reality>\nNếu người dùng hỏi họ là ai, tên gì, đang ở trang nào hoặc thực tại của họ lúc này, hãy trả lời tự nhiên dựa trên <user_reality>.`
    : '';

  const scopeBlock = configuration.scopePolicy
    ? `\nChính sách phạm vi hỗ trợ bắt buộc:\n${configuration.scopePolicy}`
    : '';

  const response = await invokeChatModelWithFallback(
    [
      new SystemMessage(
        `Bạn là Pico, trợ lý AI song ngữ (Tiếng Việt & Tiếng Anh) thân thiện của Rosaliza Hotel. Hãy trả lời bằng đúng ngôn ngữ khách đang sử dụng (Tiếng Việt hoặc Tiếng Anh), không dùng dấu gạch ngang dài (em dash). Với câu hỏi ngoài phạm vi chính sách và đặt phòng khách sạn, chỉ trả lời ngắn gọn TỐI ĐA 2 CÂU rồi khéo léo mời khách hỏi về phòng nghỉ và dịch vụ tại Rosaliza Hotel. TUYỆT ĐỐI KHÔNG trả lời các chủ đề chính trị, chiến tranh, lịch sử, quân sự, vũ khí vũ trang, y khoa, nội dung người lớn (18+) và TUYỆT ĐỐI KHÔNG bịa đặt thông tin chưa được kiểm duyệt của Nhà nước Việt Nam. ${PICO_STYLE}\nĐồng hồ hệ thống tại Hà Nội: ${hotelClock()}. Đây là thời gian thực, không phải kiến thức của mô hình.${scopeBlock}${realityBlock}${weatherContext}`,
      ),
      ...state.messages.slice(-6),
      userHumanMessage,
    ],
    configuration.queryModel,
    0.2,
    config,
  );

  return { messages: [userHumanMessage, response] };
}

async function routeQuery(
  state: typeof AgentStateAnnotation.State,
): Promise<'retrieveDocuments' | 'directAnswer'> {
  const route = state.route;
  if (!route) {
    throw new Error('Route is not set');
  }

  if (route === 'retrieve') {
    return 'retrieveDocuments';
  } else if (route === 'direct') {
    return 'directAnswer';
  } else {
    throw new Error('Invalid route');
  }
}

async function retrieveDocuments(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<typeof AgentStateAnnotation.Update> {
  // Start live Laravel tool calls concurrently with vector retrieval!
  const toolPrefetch = getOrStartToolPrefetch(
    state.query,
    state.messages || [],
    config,
  );
  const retriever = await makeRetriever(config);
  const [response] = await Promise.all([
    retriever.invoke(retrievalQuestion(state.query, state.messages || [])),
    toolPrefetch.catch(() => ''),
  ]);

  return { documents: response };
}

async function generateResponse(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<typeof AgentStateAnnotation.Update> {
  const configuration = ensureAgentConfiguration(config);
  const liveToolsXml = await getOrStartToolPrefetch(
    state.query,
    state.messages || [],
    config,
  );
  prefetchedToolsCache.delete(toolPrefetchKey(state.query, config));
  const docContext = formatDocs(state.documents);

  const formattedPrompt = await RESPONSE_SYSTEM_PROMPT.invoke({
    question: state.query,
    context: docContext,
    toolContext: liveToolsXml,
    currentTime: hotelClock(),
    userReality:
      configuration.userReality || 'Khách vãng lai trên hệ thống Rosaliza Hotel.',
  });

  const userHumanMessage = new HumanMessage(state.query);
  const promptMessages = formattedPrompt.toChatMessages();
  const messageHistory = [
    promptMessages[0],
    ...state.messages.slice(-6),
    ...promptMessages.slice(1),
  ];

  const response = await invokeChatModelWithFallback(
    messageHistory,
    configuration.queryModel,
    0.2,
    config,
  );
  const cleanedText = String(response.content || '')
    .replace(/\*\*/g, '')
    .replace(/\u2014/g, '-')
    .trim();

  return {
    messages: [userHumanMessage, new AIMessage(cleanedText)],
  };
}

const builder = new StateGraph(
  AgentStateAnnotation,
  AgentConfigurationAnnotation,
)
  .addNode('checkQueryType', checkQueryType)
  .addNode('retrieveDocuments', retrieveDocuments)
  .addNode('generateResponse', generateResponse)
  .addNode('directAnswer', answerQueryDirectly)
  .addEdge(START, 'checkQueryType')
  .addConditionalEdges('checkQueryType', routeQuery, [
    'retrieveDocuments',
    'directAnswer',
  ])
  .addEdge('retrieveDocuments', 'generateResponse')
  .addEdge('generateResponse', END)
  .addEdge('directAnswer', END);

export const graph = builder.compile().withConfig({
  runName: 'RetrievalGraph',
});
