const test = require('node:test');
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const { base64UrlEncode, verifyTicket, verifyPublishSignature } = require('./auth');

const secret = 'test-secret-that-is-at-least-32-characters-long';

test('accepts a valid short-lived user ticket', () => {
  const payload = { sub: 42, exp: 1060, nonce: '12345678901234567890' };
  const encoded = base64UrlEncode(JSON.stringify(payload));
  const signature = base64UrlEncode(crypto.createHmac('sha256', secret).update(encoded).digest());
  assert.deepEqual(verifyTicket(encoded + '.' + signature, secret, 1000), {
    userId: 42, expiresAt: 1060, nonce: payload.nonce,
  });
});

test('rejects an expired or tampered ticket', () => {
  const payload = { sub: 42, exp: 999, nonce: '12345678901234567890' };
  const encoded = base64UrlEncode(JSON.stringify(payload));
  const signature = base64UrlEncode(crypto.createHmac('sha256', secret).update(encoded).digest());
  assert.equal(verifyTicket(encoded + '.' + signature, secret, 1000), null);
  assert.equal(verifyTicket(encoded + '.tampered', secret, 1000), null);
});

test('authenticates publish requests and rejects stale timestamps', () => {
  const body = JSON.stringify({ user_id: 42, event: 'message.received', data: { conversation_id: 9 } });
  const timestamp = '1000000000';
  const signature = crypto.createHmac('sha256', secret).update(timestamp + '.' + body).digest('hex');
  assert.equal(verifyPublishSignature(body, timestamp, signature, secret, 1000000000), true);
  assert.equal(verifyPublishSignature(body, timestamp, signature, secret, 1000000040), false);
});
