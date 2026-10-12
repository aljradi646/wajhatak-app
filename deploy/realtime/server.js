const http = require('node:http');
const { WebSocketServer, WebSocket } = require('ws');
const { verifyTicket, verifyPublishSignature } = require('./auth');

const port = Number(process.env.PORT || 3001);
const secret = String(process.env.REALTIME_SHARED_SECRET || '');
const maxBodyBytes = 64 * 1024;
const clients = new Map();

if (secret.length < 32) {
  throw new Error('REALTIME_SHARED_SECRET must be at least 32 characters.');
}

function sendJson(response, status, value) {
  const body = JSON.stringify(value);
  response.writeHead(status, {
    'content-type': 'application/json; charset=utf-8',
    'cache-control': 'no-store',
    'content-length': Buffer.byteLength(body),
  });
  response.end(body);
}

function readBody(request) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    request.on('data', chunk => {
      size += chunk.length;
      if (size > maxBodyBytes) {
        reject(new Error('body_too_large'));
        request.destroy();
        return;
      }
      chunks.push(chunk);
    });
    request.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    request.on('error', reject);
  });
}

const server = http.createServer(async (request, response) => {
  const url = new URL(request.url, 'http://' + (request.headers.host || 'localhost'));
  if (request.method === 'GET' && url.pathname === '/health') {
    return sendJson(response, 200, { ok: true, service: 'wajhatak-realtime' });
  }
  if (request.method !== 'POST' || url.pathname !== '/publish') {
    return sendJson(response, 404, { message: 'Not found.' });
  }

  let rawBody;
  try {
    rawBody = await readBody(request);
  } catch {
    return sendJson(response, 413, { message: 'Payload too large.' });
  }

  const timestamp = request.headers['x-realtime-timestamp'];
  const signature = request.headers['x-realtime-signature'];
  if (!verifyPublishSignature(rawBody, timestamp, signature, secret)) {
    return sendJson(response, 401, { message: 'Unauthorized.' });
  }

  let event;
  try {
    event = JSON.parse(rawBody);
  } catch {
    return sendJson(response, 400, { message: 'Invalid JSON.' });
  }

  if (!Number.isSafeInteger(event.user_id) || event.user_id < 1 ||
      typeof event.event !== 'string' || event.event.length > 100 ||
      !event.data || typeof event.data !== 'object' || Array.isArray(event.data)) {
    return sendJson(response, 422, { message: 'Invalid event payload.' });
  }

  const room = clients.get(String(event.user_id));
  const packet = JSON.stringify({
    event: event.event,
    data: event.data,
    timestamp: new Date().toISOString(),
  });
  let delivered = 0;
  if (room) {
    for (const socket of room) {
      if (socket.readyState === WebSocket.OPEN) {
        socket.send(packet, { compress: false }, error => {
          if (error) socket.terminate();
        });
        delivered += 1;
      }
    }
  }

  return sendJson(response, 202, { accepted: true, delivered });
});

const wss = new WebSocketServer({
  noServer: true,
  maxPayload: 16 * 1024,
  perMessageDeflate: false,
});

server.on('upgrade', (request, socket, head) => {
  const url = new URL(request.url, 'http://' + (request.headers.host || 'localhost'));
  if (url.pathname !== '/ws') {
    socket.write('HTTP/1.1 404 Not Found\r\nConnection: close\r\n\r\n');
    socket.destroy();
    return;
  }

  const originAllowlist = String(process.env.REALTIME_ALLOWED_ORIGINS || '')
    .split(',')
    .map(value => value.trim())
    .filter(Boolean);
  const origin = request.headers.origin;
  if (originAllowlist.length && origin && !originAllowlist.includes(origin)) {
    socket.write('HTTP/1.1 403 Forbidden\r\nConnection: close\r\n\r\n');
    socket.destroy();
    return;
  }

  const ticket = url.searchParams.get('ticket');
  const identity = verifyTicket(ticket, secret);
  if (!identity) {
    socket.write('HTTP/1.1 401 Unauthorized\r\nConnection: close\r\n\r\n');
    socket.destroy();
    return;
  }

  wss.handleUpgrade(request, socket, head, websocket => {
    websocket.userId = String(identity.userId);
    websocket.isAlive = true;
    wss.emit('connection', websocket, request);
  });
});

wss.on('connection', socket => {
  const id = socket.userId;
  if (!clients.has(id)) clients.set(id, new Set());
  clients.get(id).add(socket);

  socket.on('pong', () => { socket.isAlive = true; });
  socket.on('message', () => {
    // This channel is server-to-client only; clients cannot publish events.
    socket.close(1008, 'Read-only channel');
  });
  socket.on('close', () => {
    const room = clients.get(id);
    if (!room) return;
    room.delete(socket);
    if (room.size === 0) clients.delete(id);
  });
});

const heartbeat = setInterval(() => {
  for (const socket of wss.clients) {
    if (!socket.isAlive) {
      socket.terminate();
      continue;
    }
    socket.isAlive = false;
    socket.ping();
  }
}, 30000);
heartbeat.unref();

server.listen(port, '0.0.0.0', () => {
  process.stdout.write('Wajhatak realtime listening on ' + port + '\n');
});

function shutdown() {
  clearInterval(heartbeat);
  for (const socket of wss.clients) socket.close(1001, 'Server shutting down');
  server.close(() => process.exit(0));
  setTimeout(() => process.exit(1), 5000).unref();
}
process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
