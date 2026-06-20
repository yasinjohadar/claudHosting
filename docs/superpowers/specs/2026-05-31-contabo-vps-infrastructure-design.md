# Contabo / Multi-Provider VPS Infrastructure — Design

**Date:** 2026-05-31  
**Status:** Implemented

## Goal

Admin panel control of VPS **power** (start, stop, shutdown, restart) for Contabo, Hetzner Cloud, and DigitalOcean — separate from Coolify application/docker management.

## Architecture

- `VpsProviderContract` + `ContaboVpsProvider`, `HetznerCloudVpsProvider`, `DigitalOceanVpsProvider`
- `VpsProviderRegistry`, `VpsSyncService`, `VpsActionService`
- `vps_servers` + `vps_action_logs` tables
- Credentials in `system_settings` group `infrastructure` (encrypted secrets)
- Routes: `admin/infrastructure/*`

## RBAC

- Admin authenticated users only (`auth` middleware)
- Stop action requires `confirm_stop=1` + modal in UI
- All power actions logged in `vps_action_logs`

## Coolify link

Optional `coolify_server_uuid` on `vps_servers` for quick link to Coolify server show page.
