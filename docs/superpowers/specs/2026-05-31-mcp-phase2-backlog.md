# MCP Features — Phase 2 & 3 Backlog

**Related spec:** `2026-05-31-mcp-features-inventory-design.md`  
**Phase 1 status:** Implemented in application code (DockerHostService, client portal, operations dashboard).

## Phase 2

| ID | Feature | Surface | Notes |
|----|---------|---------|-------|
| D3 | Disk/volume alerts in operations + email | Admin | Threshold 85% root; integrate `CoolifyOperationsNotificationService` |
| D4 | Whitelisted `docker exec` commands | Admin full; Client WP-CLI only | Policy table in config; audit log |
| C3 | Client read-only server resources | Client | `serverResources` for assigned project server |
| C7 | Deploy/service failure notifications | Admin + Client | Webhook + in-app; reuse ops notification service |

## Phase 3

| ID | Feature | Surface | Notes |
|----|---------|---------|-------|
| C4 | Client app creation wizard | Client | public/Git/Docker compose templates; quota per plan |
| D5 | Compose config + healthcheck diagnostics page | Admin | `docker compose config` via SSH |
| D6 | Multi-host `DOCKER_PROFILES` in panel settings | Admin | Map Coolify server UUID → SSH profile name |
| D2b | DB restore from panel (admin) | Admin | Upload `.sql.gz` + `docker compose exec` restore job |

## MCP tooling (developer only)

- Keep `@hypnosis/docker-mcp-server` with `DOCKER_MCP_PROFILES_FILE` for Cursor agents
- Optional: document Docker Desktop `docker-mcp` plugin as alternative in `.cursor/mcp.json.example`
- Never call MCP from PHP production paths

## Security reminders

- Rotate secrets out of committed `mcp.json`; use `${env:...}` placeholders
- Client lifecycle actions require `ClientCoolifyProject` assignment + team check
- DB backups stored under `storage/app/wordpress-db-backups/` — restrict download routes to authorized users
