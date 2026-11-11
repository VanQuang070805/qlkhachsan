import { StateGraph, START, END } from '@langchain/langgraph';
import { AgentStateAnnotation } from './state.js';
import { makeRetriever } from '../shared/retrieval.js';
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
  'hạng phòng',
  'hang phong',
  'phòng trống',
  'phong trong',
  'trả phòng',
  'tra phong',
  'nhận phòng',
  'nhan phong',
  'khách sạn',
  'khach san',
  'check-in',
  'check-out',
  'booking',
  'suite',
  'deluxe',
  'standard',
  'triple',
  'vip',
  'rosaliza',
  'phụ thu phòng',
  'phu thu phong',
  'gia hạn lưu trú',
  'gia han luu tru',
  'hoàn tiền đặt phòng',
  'hoan tien dat phong',
  'hủy phòng',
  'huy phong',
  'đơn của tôi',
  'don cua toi',
];

function isHotelQuery(query: string): boolean {
  const lower = query.toLowerCase();
  const normalized = lower.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
  if (/\b(gia|dat|hang|loai|con|huy|thue|phu thu|tra|nhan|tim)\s+phog\b/.test(normalized)) return true;
  return HOTEL_FACT_KEYWORDS.some((kw) => lower.includes(kw));
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
  ].some((kw) => lower.includes(kw));
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
  const combinedText = [
    ...history.slice(-4).map((m) => String(m.content || '')),
    query,
  ].join(' ');

  const isoDates = Array.from(
    combinedText.matchAll(/\b(20\d{2}-\d{2}-\d{2})\b/g),
  ).map((m) => m[1]);

  let checkIn: string | undefined;
  let checkOut: string | undefined;
  if (isoDates.length >= 2) {
    checkIn = isoDates[isoDates.length - 2];
    checkOut = isoDates[isoDates.length - 1];
  }

  let guests: number | undefined;
  const guestMatch = combinedText.match(/(\d+)\s*(người|nguoi|khách|khach)/i);
  if (guestMatch) {
    guests = Number(guestMatch[1]);
  }

  let rooms = 1;
  const roomMatch = combinedText.match(/(\d+)\s*(phòng|phong)/i);
  if (roomMatch) {
    rooms = Math.max(1, Math.min(10, Number(roomMatch[1])));
  }

  return { check_in: checkIn, check_out: checkOut, guests, rooms };
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

  try {
    if (isPriceOrRoomTypeQuery(query)) {
      const rtRes = await fetch(`${baseUrl}/chatbot/tools/room-types`, {
        method: 'POST',
        headers,
        body: JSON.stringify({}),
        signal: AbortSignal.timeout(8000),
      });
      if (rtRes.ok) {
        results.room_types = await rtRes.json();
      }
    }

    const criteria = extractSearchCriteria(query, history);
    if (criteria.check_in && criteria.check_out && criteria.guests) {
      const searchRes = await fetch(`${baseUrl}/chatbot/tools/rooms/search`, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          check_in: criteria.check_in,
          check_out: criteria.check_out,
          guests: criteria.guests,
          rooms: criteria.rooms || 1,
        }),
        signal: AbortSignal.timeout(8000),
      });
      results.availability_search = await searchRes.json();
    }

    if (
      isMyBookingQuery(query) &&
      capabilityToken &&
      actorScope.startsWith('customer:')
    ) {
      const myBookingsRes = await fetch(
        `${baseUrl}/chatbot/tools/my-bookings`,
        {
          method: 'POST',
          headers,
          body: JSON.stringify({}),
          signal: AbortSignal.timeout(8000),
        },
      );
      if (myBookingsRes.ok) {
        results.my_bookings = await myBookingsRes.json();
      }
    }
  } catch {
    results.tool_warning =
      'Không thể kết nối tức thời tới công cụ phòng trực tiếp; không tự đoán giá hoặc phòng trống.';
  }

  return `<live_hotel_tools>\n${JSON.stringify(results, null, 2)}\n</live_hotel_tools>`;
}

async function checkQueryType(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<{
  route: 'retrieve' | 'direct';
}> {
  if (answerClockQuestion(state.query || '')) {
    return { route: 'direct' };
  }

  if (isWeatherQuestion(state.query || '')) {
    return { route: 'direct' };
  }

  if (isHotelQuery(state.query || '')) {
    return { route: 'retrieve' };
  }

  const lastUserQuestion = [...(state.messages || [])].reverse()
    .find((message) => message instanceof HumanMessage);
  const followup = /^(còn|con|thế|the|vậy|vay|nó|no|loại đó|loai do|bao nhiêu|bao nhieu|giá thì sao|gia thi sao)/i.test((state.query || '').trim());
  if (followup && lastUserQuestion && isHotelQuery(String(lastUserQuestion.content || ''))) {
    return { route: 'retrieve' };
  }

  if (/^(chào|xin chào|hello|hi\b|cảm ơn|cám ơn|kể (mình|tôi) (một )?chuyện cười|bạn (có )?khỏe không)/i.test((state.query || '').trim())) {
    return { route: 'direct' };
  }

  // General questions go straight to Gemini. The extra model router was adding
  // one full provider round trip before every ordinary answer.
  if (!/phòng|phong|phog|khách sạn|khach san|đặt|dat|hủy|huy|hoàn tiền|hoan tien/i.test(state.query || '')) {
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
    return { route: 'direct' };
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
        return { messages: [userHumanMessage, new AIMessage(`Mình chưa xác định được địa điểm “${place}”. Bạn cho mình tên thành phố hoặc quốc gia cụ thể hơn nhé.`)] };
      }
      weatherContext = `\nDữ liệu thời tiết trực tiếp từ Open-Meteo, cập nhật lúc ${weather.observedAt} theo múi giờ ${weather.timezone}: ${JSON.stringify(weather)}. Chỉ dùng các số liệu này để trả lời thời tiết; không tự suy đoán. Nêu tên địa điểm và thời điểm của số liệu. Xác suất mưa cực đại trong ngày không đồng nghĩa chắc chắn mưa.`;
    } catch {
      return { messages: [userHumanMessage, new AIMessage('Mình chưa lấy được dữ liệu thời tiết trực tiếp lúc này, nên không muốn đoán bừa. Bạn thử lại sau ít phút nhé.') ] };
    }
  }

  const response = await invokeChatModelWithFallback(
    [
      new SystemMessage(`Bạn là Pico, trợ lý AI thân thiện của Rosaliza Hotel. Với chuyện đời thường, hãy trả lời tự nhiên bằng ngôn ngữ khách đang dùng. Trả lời đúng trọng tâm từng ý khách hỏi; nếu một từ viết tắt mơ hồ, suy từ ngữ cảnh hoặc hỏi lại ngắn. "NYC" có thể là New York City khi hỏi địa điểm/thời tiết, hoặc "người yêu cũ" khi nói chuyện tình cảm; không áp một nghĩa cho mọi câu. Đừng ép câu chuyện về khách sạn hoặc bịa dữ kiện riêng của khách sạn. ${PICO_STYLE}\nĐồng hồ hệ thống tại Hà Nội: ${hotelClock()}. Đây là thời gian thực, không phải kiến thức của mô hình.${weatherContext}`),
      ...state.messages.slice(-6),
      userHumanMessage,
    ],
    configuration.queryModel,
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
  const retriever = await makeRetriever(config);
  const response = await retriever.invoke(retrievalQuestion(state.query, state.messages || []));

  return { documents: response };
}

async function generateResponse(
  state: typeof AgentStateAnnotation.State,
  config: RunnableConfig,
): Promise<typeof AgentStateAnnotation.Update> {
  const configuration = ensureAgentConfiguration(config);
  const liveToolsXml = await callLaravelPublicTools(
    state.query,
    state.messages || [],
    config,
  );
  const docContext = formatDocs(state.documents);

  const promptTemplate = RESPONSE_SYSTEM_PROMPT;

  const formattedPrompt = await promptTemplate.invoke({
    question: state.query,
    context: docContext,
    toolContext: liveToolsXml,
    currentTime: hotelClock(),
  });

  const userHumanMessage = new HumanMessage(state.query);
  const promptMessages = formattedPrompt.toChatMessages();
  const messageHistory = [promptMessages[0], ...state.messages.slice(-6), ...promptMessages.slice(1)];

  const response = await invokeChatModelWithFallback(
    messageHistory,
    configuration.queryModel,
  );
  const cleanedText = String(response.content || '')
    .replace(/\*\*/g, '')
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
