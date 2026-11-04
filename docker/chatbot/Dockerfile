# ==============================================================================
# Stage 1: Build TypeScript LangGraph + BullMQ + Kafka Backend
# ==============================================================================
FROM node:20-alpine AS builder
WORKDIR /workspace

COPY services/royal-ai-chatbot/package.json ./
COPY services/royal-ai-chatbot/backend/package.json ./backend/
COPY services/royal-ai-chatbot/frontend/package.json ./frontend/

RUN npm install --legacy-peer-deps --no-audit --no-fund

COPY services/royal-ai-chatbot/backend ./backend
COPY resources/knowledge /workspace/resources/knowledge

WORKDIR /workspace/backend
RUN npm run build

# ==============================================================================
# Stage 2: Production Runtime (Non-root Node 20)
# ==============================================================================
FROM node:20-alpine AS runtime
WORKDIR /workspace/backend

COPY --from=builder /workspace/node_modules /workspace/node_modules
COPY --from=builder /workspace/backend/package.json /workspace/backend/package.json
COPY --from=builder /workspace/backend/dist /workspace/backend/dist
COPY --from=builder /workspace/resources/knowledge /workspace/resources/knowledge

USER node
EXPOSE 2024
CMD ["node", "dist/src/server.js"]
