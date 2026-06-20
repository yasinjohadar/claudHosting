# VPS Monitoring in Admin Panel — Design

**Date:** 2026-05-31  
**Status:** Implemented

## Goal

Replace day-to-day reliance on Contabo/Hetzner/DO dashboards for **OS-level** monitoring (CPU, RAM, disk, load, network, Docker) from the admin VPS server detail page.

## Decisions

| Topic | Choice |
|-------|--------|
| Data source | SSH using global Coolify SSH key + `vps_servers.ip` |
| Live metrics | `HostMetricsCollector` (shared with Coolify) |
| History | `vps_metric_snapshots` table, 5-minute scheduler |
| Retention | 7 days (configurable) |
| UI | Progress bars + Chart.js (24h / 7d) + Docker table |

## Architecture

- `App\Services\Monitoring\HostMetricsCollector` — SSH script: mem, swap, load, uptime, CPU delta, network B/s, df, docker stats
- `App\Services\Infrastructure\VpsMetricsService` — cache, live, history, snapshot recording
- `InfrastructureMetricsController` — JSON API
- `RecordVpsMetricsSnapshotsCommand` + `RecordVpsMetricsSnapshotJob` — scheduled every 5 minutes
- `PruneVpsMetricSnapshotsCommand` — daily cleanup

## Routes

- `GET admin/infrastructure/servers/{uuid}/metrics` — live (`?refresh=1`)
- `GET admin/infrastructure/servers/{uuid}/metrics/history` — `?range=24h|7d`

## Config (`config/infrastructure.php`)

- `metrics_cache_seconds` (8)
- `metrics_refresh_seconds` (10)
- `metrics_snapshot_interval_minutes` (5)
- `metrics_retention_days` (7)

## Preconditions

1. VPS `status=running` and `ip` set (from provider sync)
2. Coolify SSH: key + user configured
3. Port 22 reachable from Laravel host to VPS IP
4. Linux VPS (script uses `/proc`, `free`, `df`)

Provider panels remain needed for billing, reinstall, and provider-level backups.

## Files

- `app/Services/Monitoring/HostMetricsCollector.php`
- `app/Services/Infrastructure/VpsMetricsService.php`
- `app/Http/Controllers/Admin/Infrastructure/InfrastructureMetricsController.php`
- `app/Models/VpsMetricSnapshot.php`
- `database/migrations/2026_06_01_100000_create_vps_metric_snapshots_table.php`
- `resources/views/admin/infrastructure/servers/partials/metrics-widget.blade.php`
