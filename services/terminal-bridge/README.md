# Terminal Bridge

WebSocket service for interactive shell inside WordPress Docker containers.

## Setup

```bash
cd services/terminal-bridge
npm install
```

## Environment (`.env` in project root)

```env
TERMINAL_BRIDGE_ENABLED=true
TERMINAL_BRIDGE_URL=http://127.0.0.1:3099
TERMINAL_BRIDGE_SECRET=your-long-random-secret
TERMINAL_BRIDGE_PORT=3099
SSH_PRIVATE_KEY_PATH=/path/to/server.pem
SSH_USER=root
SSH_PORT=22
```

`TERMINAL_BRIDGE_SECRET` must match Laravel `TERMINAL_BRIDGE_SECRET` (or use a dedicated random string in both places).

## Run

```bash
npm start
# or with PM2:
# pm2 start index.js --name claud-terminal-bridge
```

Health check: `GET http://127.0.0.1:3099/health`

## Architecture

Laravel issues a signed JWT → browser opens WebSocket to this service → SSH to VPS → `docker exec -it` into the WordPress container.
