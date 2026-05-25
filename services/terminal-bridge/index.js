/**
 * ClaudHosting Terminal Bridge
 * WebSocket → SSH → docker exec -it CONTAINER shell
 *
 * Env: TERMINAL_BRIDGE_PORT, TERMINAL_BRIDGE_SECRET, SSH_PRIVATE_KEY_PATH, SSH_USER, SSH_PORT
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });

const http = require('http');
const crypto = require('crypto');
const fs = require('fs');
const express = require('express');
const { WebSocketServer } = require('ws');
const { Client } = require('ssh2');

const PORT = parseInt(process.env.TERMINAL_BRIDGE_PORT || '3099', 10);
const SECRET = process.env.TERMINAL_BRIDGE_SECRET || process.env.APP_KEY || '';
const SSH_KEY_PATH = process.env.SSH_PRIVATE_KEY_PATH || process.env.COOLIFY_SSH_KEY_PATH || '';
const SSH_USER = process.env.SSH_USER || process.env.COOLIFY_SSH_USER || 'root';
const SSH_PORT = parseInt(process.env.SSH_PORT || '22', 10);

const BLOCKED = [
  /\brm\s+-rf\s+\/\s*$/i,
  /\brm\s+-rf\s+\/\s/i,
  /\bmkfs\b/i,
  /\bshutdown\b/i,
  /\breboot\b/i,
  /\bdd\s+if=/i,
  /:\(\)\s*\{\s*:\|\s*:\s*&\s*\}\s*;/,
];

function base64UrlDecode(str) {
  str = str.replace(/-/g, '+').replace(/_/g, '/');
  while (str.length % 4) str += '=';
  return Buffer.from(str, 'base64').toString('utf8');
}

function verifyToken(token) {
  if (!SECRET || !token) return null;
  const parts = token.split('.');
  if (parts.length !== 3) return null;
  const [header, body, sig] = parts;
  const expected = crypto
    .createHmac('sha256', SECRET)
    .update(header + '.' + body)
    .digest('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '');
  if (sig !== expected) return null;
  try {
    const payload = JSON.parse(base64UrlDecode(body));
    if (payload.exp && payload.exp < Math.floor(Date.now() / 1000)) return null;
    return payload;
  } catch {
    return null;
  }
}

function isBlockedInput(data) {
  const line = String(data).trim();
  return BLOCKED.some((re) => re.test(line));
}

function openShell(payload, onData, onClose, onError) {
  if (!SSH_KEY_PATH || !fs.existsSync(SSH_KEY_PATH)) {
    onError('SSH key not found: ' + SSH_KEY_PATH);
    return null;
  }

  const conn = new Client();
  const containerId = payload.container_id;
  const cmd = `docker exec -it ${containerId} sh -c "command -v bash >/dev/null && exec bash || exec sh"`;

  conn
    .on('ready', () => {
      conn.exec(cmd, { pty: true }, (err, stream) => {
        if (err) {
          onError(err.message);
          conn.end();
          return;
        }
        stream.on('data', (d) => onData(d.toString('utf8')));
        stream.stderr.on('data', (d) => onData(d.toString('utf8')));
        stream.on('close', () => {
          conn.end();
          onClose();
        });
      });
    })
    .on('error', (err) => onError(err.message))
    .connect({
      host: payload.host,
      port: payload.ssh_port || SSH_PORT,
      username: payload.ssh_user || SSH_USER,
      privateKey: fs.readFileSync(SSH_KEY_PATH),
    });

  return {
    write: (data) => {
      if (isBlockedInput(data)) {
        onData('\r\n\x1b[31m[blocked: dangerous command]\x1b[0m\r\n');
        return;
      }
      conn.exec ? null : null;
    },
    conn,
  };
}

const app = express();
app.get('/health', (_, res) => res.json({ ok: true }));

const server = http.createServer(app);
const wss = new WebSocketServer({ server, path: '/session' });

wss.on('connection', (ws, req) => {
  const url = new URL(req.url, 'http://localhost');
  const token = url.searchParams.get('token');
  const payload = verifyToken(token);
  if (!payload) {
    ws.send(JSON.stringify({ type: 'error', data: 'Invalid or expired token' }));
    ws.close();
    return;
  }

  let stream = null;
  const conn = new Client();

  conn
    .on('ready', () => {
      const containerId = payload.container_id;
      const inner = 'export TERM=xterm-256color; command -v bash >/dev/null 2>&1 && exec bash || exec sh';
      const cmd = `docker exec -it ${containerId} sh -c ${JSON.stringify(inner)}`;
      conn.exec(cmd, { pty: true }, (err, sshStream) => {
        if (err) {
          ws.send(JSON.stringify({ type: 'error', data: err.message }));
          ws.close();
          conn.end();
          return;
        }
        stream = sshStream;
        sshStream.on('data', (d) => ws.send(JSON.stringify({ type: 'output', data: d.toString('utf8') })));
        sshStream.stderr?.on('data', (d) => ws.send(JSON.stringify({ type: 'output', data: d.toString('utf8') })));
        sshStream.on('close', () => {
          conn.end();
          ws.close();
        });
      });
    })
    .on('error', (err) => {
      ws.send(JSON.stringify({ type: 'error', data: err.message }));
      ws.close();
    })
    .connect({
      host: payload.host,
      port: payload.ssh_port || SSH_PORT,
      username: payload.ssh_user || SSH_USER,
      privateKey: fs.readFileSync(SSH_KEY_PATH),
    });

  ws.on('message', (raw) => {
    if (!stream) return;
    let data = raw;
    try {
      const msg = JSON.parse(raw);
      if (msg.type === 'input') data = msg.data;
    } catch (_) {}
    if (isBlockedInput(data)) {
      ws.send(JSON.stringify({ type: 'output', data: '\r\n[blocked]\r\n' }));
      return;
    }
    stream.write(data);
  });

  ws.on('close', () => {
    try {
      stream?.close();
      conn.end();
    } catch (_) {}
  });
});

server.listen(PORT, () => {
  console.log('Terminal bridge listening on port', PORT);
});
