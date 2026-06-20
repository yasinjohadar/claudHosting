# Coolify Backup & Restore — Design Spec

**Date:** 2026-06-01  
**Status:** Implemented (product code)  
**Scope:** Backup Control Plane (Hub), Coolify API, SSH volumes, WordPress mysqldump, VPS provider snapshots

## 1. Backup matrix (granularity)

| Granularity | Mechanism | Storage | Restore |
|-------------|-----------|---------|---------|
| Single DB (Coolify) | `coolify_api` → Coolify scheduled backup + S3 | Coolify S3 (`save_s3`) | Automatic via SSH + execution file (`CoolifyDatabaseBackupRestoreService`) |
| Single app/service volume | `ssh_volume` | App S3 (`snapshot_storage_config_id`) | `CoolifyProjectRestoreService::restoreVolumes` |
| Single resource (custom) | Planner `scope=custom` + snapshot job | Mixed | Per-item strategy |
| Full project | Wizard `scope=single_project` / `all_projects` | Mixed | Scopes: all / project / selected |
| Full server | Planner `scope=server` + `server_uuid` | Mixed | Same restore pipeline |
| WordPress site DB | `DockerHostService::createDatabaseBackup` | `storage/app/wordpress-db-backups/` | `DockerHostService::restoreDatabaseBackup` |
| Laravel app | `BackupService` (`admin/backups`) | Configured backup storage | Existing module |
| VPS (infra) | Provider snapshot API / panel | Provider | Link from Hub → Infrastructure |

**Note:** Coolify has no “container” API entity; operational unit = application / service / database + Docker volumes.

## 2. 3-2-1 policy (product)

1. **3 copies:** production + S3 (Coolify or Hub) + optional VPS provider snapshot.
2. **2 media:** server disk + S3.
3. **1 off-site:** S3; optional second bucket via Laravel backup storage.

Hub blocks misleading “DB backup” when `coolify_s3_storage_uuid` is missing (`manifest_only` only records metadata).

## 3. MCP gaps

| MCP | Backup ops | Used in production PHP |
|-----|------------|-------------------------|
| `user-coolify-mcp` | None (lifecycle only) | No |
| Docker MCP (Cursor) | Dev-only | No — `DockerHostService` + SSH |

All backup/restore in production uses **Coolify REST API** (`CoolifyApiService`) and **SSH** (`CoolifySshExecutor`).

## 4. Hub tabs vs source of truth

| Hub tab | Source |
|---------|--------|
| Overview / Hub | Aggregated stats + readiness |
| Databases | `GET databases/{uuid}/backups`, executions |
| Project snapshots | `CoolifyProjectSnapshot` + S3 volumes |
| Schedules | `coolify_snapshot_schedules` + `coolify:run-scheduled-snapshots` |
| Snapshot log | Snapshots + `coolify_backup_audit_logs` + restore drills |
| VPS snapshots | `VpsServer` where `coolify_server_uuid` set → Infrastructure |

## 5. RPO / RTO (targets)

| Granularity | RPO | RTO |
|-------------|-----|-----|
| Coolify DB (daily cron) | 24h | 30–60 min |
| Project snapshot | 12–24h (schedule) | 1–3h |
| WP mysqldump | On-demand / daily | 15–30 min |
| VPS provider | 5–15 min if enabled | 15–60 min |

## 6. Layers

1. **App backup:** Coolify API DB, SSH volumes, WP dump.
2. **Automation:** Auto DB restore, pre-restore snapshot, restore drill job.
3. **Infra:** VPS snapshot link in Hub.

## 7. Implementation map

| Component | Path |
|-----------|------|
| DB restore | `CoolifyDatabaseBackupRestoreService` |
| Project restore | `CoolifyProjectRestoreService` |
| Pre-restore | `CoolifyPreRestoreSnapshotService` |
| Restore drill | `RunRestoreDrillJob`, `coolify:run-restore-drills` |
| Audit | `CoolifyBackupAuditLog`, `CoolifyBackupAuditService` |
| Single resource | `CoolifyResourceSnapshotController` |
| Server scope | `CoolifyProjectBackupPlanner::collectServerResources` |
