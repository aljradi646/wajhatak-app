FROM node:20-alpine
ENV NODE_ENV=production
WORKDIR /app
COPY deploy/realtime/package.json ./package.json
RUN npm install --omit=dev --no-audit --no-fund
COPY deploy/realtime/server.js ./server.js
COPY deploy/realtime/auth.js ./auth.js
EXPOSE 3001
USER node
CMD ["node", "server.js"]
