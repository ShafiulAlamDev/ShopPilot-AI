# =============================================================================
# Next.js 14 Dockerfile — ShopPilot AI
# Multi-stage build: development | builder | production
# =============================================================================

# ---------------------------------------------------------------------------
# Base — shared Node.js image
# ---------------------------------------------------------------------------
FROM node:20-alpine AS base
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

# ---------------------------------------------------------------------------
# Development — hot reload, source maps
# ---------------------------------------------------------------------------
FROM base AS development

# Install dependencies first (better layer caching)
COPY package.json package-lock.json* ./
RUN npm ci

# Copy source
COPY . .

EXPOSE 3000
CMD ["npm", "run", "dev"]

# ---------------------------------------------------------------------------
# Builder — compile production build
# ---------------------------------------------------------------------------
FROM base AS builder

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

# Build args (injected at build time for static public env vars)
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL

RUN npm run build

# ---------------------------------------------------------------------------
# Production — minimal runtime image
# ---------------------------------------------------------------------------
FROM base AS production

ENV NODE_ENV=production

# Only copy what Next.js needs to run
COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

EXPOSE 3000
CMD ["node", "server.js"]
