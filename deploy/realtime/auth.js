const crypto = require('node:crypto');

function base64UrlEncode(value) {
  return Buffer.from(value).toString('base64').replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
}

function safeEqual(left, right) {
  const a = Buffer.from(String(left));
  const b = Buffer.from(String(right));
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

function verifyTicket(ticket, secret, now = Math.floor(Date.now() / 1000)) {
  if (typeof ticket !== 'string' || ticket.length > 2048 || !secret) return null;
  const parts = ticket.split('.');
  if (parts.length !== 2) return null;
  const encoded = parts[0];
  const signature = parts[1];
  const expected = base64UrlEncode(crypto.createHmac('sha256', secret).update(encoded).digest());
  if (!safeEqual(signature, expected)) return null;

  try {
    const normalized = encoded.replace(/-/g, '+').replace(/_/g, '/');
    const payload = JSON.parse(Buffer.from(normalized, 'base64').toString('utf8'));
    if (!Number.isSafeInteger(payload.sub) || payload.sub < 1) return null;
    if (!Number.isSafeInteger(payload.exp) || payload.exp < now || payload.exp > now + 120) return null;
    if (typeof payload.nonce !== 'string' || payload.nonce.length < 16) return null;
    return { userId: payload.sub, expiresAt: payload.exp, nonce: payload.nonce };
  } catch {
    return null;
  }
}

function verifyPublishSignature(rawBody, timestamp, signature, secret, now = Math.floor(Date.now() / 1000)) {
  if (!secret || !/^\d{10}$/.test(String(timestamp))) return false;
  const seconds = Number(timestamp);
  if (!Number.isSafeInteger(seconds) || Math.abs(now - seconds) > 30) return false;
  const expected = crypto.createHmac('sha256', secret).update(String(timestamp) + '.' + rawBody).digest('hex');
  return safeEqual(signature, expected);
}

module.exports = { base64UrlEncode, verifyTicket, verifyPublishSignature };
