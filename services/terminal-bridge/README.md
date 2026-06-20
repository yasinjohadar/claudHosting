# Terminal Bridge

WebSocket service for interactive shell sessions over SSH.

## Paths

| Path | Purpose |
|------|---------|
| `/session` | WordPress — SSH to VPS then `docker exec` into container |
| `/host-session` | VPS — direct SSH shell on the server |

## Configuration (no .env required)

All settings are managed from the admin panel:

**Coolify → مركز الإعدادات → Terminal Bridge**

On save, Laravel writes `storage/app/terminal-bridge/runtime.json` with JWT secret, bridge port, and SSH credentials path.

The Node service reloads this file every 15 seconds. `.env` is only a dev fallback.

## Run

```bash
cd services/terminal-bridge
npm install
npm start
```

Health check: `GET http://127.0.0.1:3099/health`
