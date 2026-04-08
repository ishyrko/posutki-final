FROM node:20-alpine AS base
WORKDIR /app
COPY package*.json ./

# Development — source code mounted via volume in docker-compose
FROM base AS development
RUN npm install
COPY . .
EXPOSE 3000
CMD ["sh", "-c", "npm run dev -- --hostname 0.0.0.0 --port 3000"]

# Builder — NEXT_PUBLIC_* vars must be baked in at build time
FROM base AS builder
ARG NEXT_PUBLIC_API_URL
ARG NEXT_PUBLIC_GOOGLE_CLIENT_ID
ARG NEXT_PUBLIC_YANDEX_MAPS_API_KEY
ENV NEXT_PUBLIC_API_URL=$NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_GOOGLE_CLIENT_ID=$NEXT_PUBLIC_GOOGLE_CLIENT_ID
ENV NEXT_PUBLIC_YANDEX_MAPS_API_KEY=$NEXT_PUBLIC_YANDEX_MAPS_API_KEY
RUN npm ci
COPY . .
RUN npm run build

# Production — runs the built app
FROM node:20-alpine AS production
WORKDIR /app
ENV NODE_ENV=production
COPY --from=builder /app/package*.json ./
COPY --from=builder /app/.next ./.next
COPY --from=builder /app/public ./public
COPY --from=builder /app/node_modules ./node_modules
EXPOSE 3000
CMD ["npm", "run", "start"]
