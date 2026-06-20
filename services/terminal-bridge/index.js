/**
 * ClaudHosting Terminal Bridge
 * WebSocket → SSH → container shell (/session) or host shell (/host-session)
 *
 * Config: storage/app/terminal-bridge/runtime.json (synced from Laravel admin)
 * Fallback env only for local dev without runtime file.
 */
require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });

const http = require('http');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const express = require('express');
const { WebSocketServer } = require('ws');
const { Client } = require('ssh2');

const RUNTIME_PATH = path.join(__dirname, '../../storage/app/terminal-bridge/runtime.json');

function loadRuntime() {
  try {
    if (fs.existsSync(RUNTIME_PATH)) {
      return JSON.parse(fs.readFileSync(RUNTIME_PATH, 'utf8'));
    }
  } catch (_) {}
  return {};
}

function getBridgeConfig() {
  const rt = loadRuntime();
  return {
    PORT: parseInt(rt.port || process.env.TERMINAL_BRIDGE_PORT || '3099', 10),
    SECRET: rt.secret || process.env.TERMINAL_BRIDGE_SECRET || process.env.APP_KEY || '',
    SSH_KEY_PATH: rt.ssh_private_key_path || process.env.SSH_PRIVATE_KEY_PATH || process.env.COOLIFY_SSH_KEY_PATH || '',
    SSH_USER: rt.ssh_user || process.env.SSH_USER || process.env.COOLIFY_SSH_USER || 'root',
    SSH_PORT: parseInt(rt.ssh_port || process.env.SSH_PORT || '22', 10),
  };
}

let bridgeConfig = getBridgeConfig();
setInterval(() => { bridgeConfig = getBridgeConfig(); }, 15000);

const BLOCKED = [
  /\brm\s+-rf\s+\/\s*$/i,
  /\brm\s+-rf\s+\/\s/i,
  /\bmkfs\b/i,
  /\bshutdown\b/i,
  /\breboot\b/i,
  /\bpoweroff\b/i,
  /\bhalt\b/i,
  /\bdd\s+if=/i,
  /:\(\)\s*\{\s*:\|\s*:\s*&\s*\}\s*;/,
  /\bchmod\s+-R\s+777\s+\//i,
  /\bdocker\s+system\s+prune\s+-a\b/i,
];

function base64UrlDecode(str) {
  str = str.replace(/-/g, '+').replace(/_/g, '/');
  while (str.length % 4) str += '=';
  return Buffer.from(str, 'base64').toString('utf8');
}

function verifyToken(token) {
  const SECRET = bridgeConfig.SECRET;
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
  const lines = String(data).split(/\r?\n/);
  return lines.some((line) => {
    const trimmed = line.trim();
    if (trimmed === '') return false;
    return BLOCKED.some((re) => re.test(trimmed));
  });
}

function readSshKey() {
  const SSH_KEY_PATH = bridgeConfig.SSH_KEY_PATH;
  if (!SSH_KEY_PATH || !fs.existsSync(SSH_KEY_PATH)) {
    return null;
  }
  return fs.readFileSync(SSH_KEY_PATH);
}

function connectPayload(payload) {
  return {
    host: payload.host,
    port: payload.ssh_port || bridgeConfig.SSH_PORT,
    username: payload.ssh_user || bridgeConfig.SSH_USER,
    privateKey: readSshKey(),
  };
}

function attachWsSession(ws, payload, openStream) {
  if (!readSshKey()) {
    ws.send(JSON.stringify({ type: 'error', data: 'SSH key not found — configure SSH in admin panel and save' }));
    ws.close();
    return;
  }

  let stream = null;
  const conn = new Client();

  conn
    .on('ready', () => {
      openStream(conn, (err, sshStream) => {
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
    .connect(connectPayload(payload));

  ws.on('message', (raw) => {
    if (!stream) return;
    let data = raw;
    try {
      const msg = JSON.parse(raw);
      if (msg.type === 'input') data = msg.data;
      if (msg.type === 'resize' && msg.cols && msg.rows && stream.setWindow) {
        stream.setWindow(msg.rows, msg.cols, 0, 0);
        return;
      }
    } catch (_) {}
    if (isBlockedInput(data)) {
      ws.send(JSON.stringify({ type: 'output', data: '\r\n\x1b[31m[blocked: dangerous command]\x1b[0m\r\n' }));
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
}

function handleContainerSession(ws, payload) {
  if (!payload.container_id) {
    ws.send(JSON.stringify({ type: 'error', data: 'Missing container_id' }));
    ws.close();
    return;
  }
  attachWsSession(ws, payload, (conn, cb) => {
    const inner = 'export TERM=xterm-256color; command -v bash >/dev/null 2>&1 && exec bash || exec sh';
    const cmd = `docker exec -it ${payload.container_id} sh -c ${JSON.stringify(inner)}`;
    conn.exec(cmd, { pty: true }, cb);
  });
}

function handleHostSession(ws, payload) {
  attachWsSession(ws, payload, (conn, cb) => {
    conn.shell({ term: 'xterm-256color', cols: 120, rows: 32 }, cb);
  });
}

function handleConnection(ws, req, mode) {
  const url = new URL(req.url, 'http://localhost');
  const token = url.searchParams.get('token');
  const payload = verifyToken(token);
  if (!payload) {
    ws.send(JSON.stringify({ type: 'error', data: 'Invalid or expired token' }));
    ws.close();
    return;
  }
  if (mode === 'host' || payload.mode === 'host') {
    handleHostSession(ws, payload);
  } else {
    handleContainerSession(ws, payload);
  }
}

const app = express();
app.get('/health', (_, res) => res.json({ ok: true, paths: ['/session', '/host-session'] }));

const server = http.createServer(app);
const wssContainer = new WebSocketServer({ noServer: true });
const wssHost = new WebSocketServer({ noServer: true });

wssContainer.on('connection', (ws, req) => handleConnection(ws, req, 'container'));
wssHost.on('connection', (ws, req) => handleConnection(ws, req, 'host'));

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url, 'http://localhost');
  if (url.pathname === '/session') {
    wssContainer.handleUpgrade(req, socket, head, (ws) => {
      wssContainer.emit('connection', ws, req);
    });
  } else if (url.pathname === '/host-session') {
    wssHost.handleUpgrade(req, socket, head, (ws) => {
      wssHost.emit('connection', ws, req);
    });
  } else {
    socket.destroy();
  }
});

server.listen(bridgeConfig.PORT, () => {
  console.log('Terminal bridge listening on port', bridgeConfig.PORT, '(/session, /host-session)');
  console.log('Runtime config:', fs.existsSync(RUNTIME_PATH) ? RUNTIME_PATH : 'env fallback');
});
