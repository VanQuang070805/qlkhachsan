import { StateGraph, START, END } from '@langchain/langgraph';
import { AgentStateAnnotation } from './state.js';
import { makeRetriever } from '../shared/retrieval.js';
import { formatDocs } from './utils.js';
import { AIMessage, BaseMessage, HumanMessage } from '@langchain/core/messages';
import { z } from 'zod';
import { RESPONSE_SYSTEM_PROMPT, ROUTER_SYSTEM_PROMPT } from './prompts.js';
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
  'phong',
  'phòng',
  'gia',
  'giá',
  'dat',
  'đặt',
  'book',
  'check',
  'nhan',
  'nhận',
  'tra',
  'trả',
  'huy',
  'hủy',
  'hoan',
  'hoàn',
  'thanh toan',
  'thanh toán',
  'coc',
  'cọc',
  'nguoi',
  'người',
  'khach',
  'khách',
  'suite',
  'deluxe',
  'standard',
  'triple',
  'vip',
  'gia dinh',
  'gia đình',
  'rosaliza',
  'royal',
  'tien ich',
  'tiện ích',
  'phu thu',
  'phụ thu',
  'gia han',
  'gia hạn',
  'con trong',
  'còn trống',
  'đơn của tôi',
  'don cua toi',
];

function isHotelQuery(query: string): boolean {
  const lower = query.toLowerCase();
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
  if (isHotelQuery(state.query || '')) {
    return { route: 'retrieve' };
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
    });

    const structuredModel = model.withStructuredOutput(schema);
    const response = await withAIRequestTimeout((signal) =>
      structuredModel.invoke(formattedPrompt.toString(), { signal }),
    );

    return { route: response.route || 'retrieve' };
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

  const response = await invokeChatModelWithFallback(
    [
      new HumanMessage(
        `Bạn là Rosaliza Concierge của Rosaliza Hotel. Hãy phản hồi lịch sự bằng tiếng Việt cho lời chào hoặc tin nhắn sau mà không bịa bất kỳ giá phòng hay chính sách nào: "${state.query}"`,
      ),
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
  const response = await retriever.invoke(state.query);

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
  });

  const userHumanMessage = new HumanMessage(state.query);
  const formattedPromptMessage = new HumanMessage(formattedPrompt.toString());

  const messageHistory = [...state.messages.slice(-6), formattedPromptMessage];

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
