# OVHcloud + Netcup Infrastructure — Design

**Date:** 2026-06-01  
**Status:** Implementing

## Scope

- **OVHcloud first:** VPS + Dedicated + Public Cloud — sync, power, settings (Application Key/Secret/Consumer Key + endpoint).
- **Netcup second:** SCP REST API — OAuth tokens, sync, power.
- **Shared:** Same `vps_servers` table, monitoring via SSH, UI tabs, lifecycle (reinstall/order) where API allows.

## external_id format

- OVH: `vps:{serviceName}`, `dedicated:{name}`, `cloud:{projectId}:{instanceId}`
- Netcup: `scp:{serverId}`

## Packages

- `ovh/ovh` for OVH API signing
- Netcup: HTTP + OAuth2 token refresh (league/oauth2-client via composer)
