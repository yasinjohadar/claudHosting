# تصميم ميزات Docker/Coolify MCP — المرحلة 1

**التاريخ:** 2026-05-31  
**النظام:** claudHosting (Laravel)  
**النطاق:** لوحة الإدارة + بوابة العميل

## الهدف

دمج قدرات Docker MCP (عبر SSH على سيرفر Coolify) وCoolify MCP (عبر `CoolifyApiService` الموجود) في المنتج دون استدعاء MCP من PHP في الإنتاج.

## الميزات — المرحلة 1

### D1 — موارد الحاوية (stats + health)

| السطح | الصلاحية | الوصف |
|-------|----------|--------|
| Admin | `auth` + مسار wordpress-sites | JSON: `GET .../docker/stats` — CPU/RAM/Net/Block من `docker stats` |
| Client | مالك الموقع (`user_id` على `CoolifyWordpressSite`) | نفس الـ endpoint عبر مسار client (قراءة فقط) |

**التنفيذ:** `DockerHostService::getContainerStats()`, `::getSiteHealth()`  
**المصدر:** SSH + `docker stats --no-stream`, `docker inspect` Health

### D2 — نسخ قاعدة بيانات لموقع WordPress

| السطح | الصلاحية | الوصف |
|-------|----------|--------|
| Admin | كامل | `POST .../docker/db-backup` → Job → ملف `.sql.gz` في `storage/app/wordpress-db-backups/{uuid}/` |
| Client | مالك الموقع | نفس الإجراء (بدون restore في المرحلة 1) |

**التنفيذ:** `DockerHostService::createDatabaseBackup()` عبر `docker compose exec` mysqldump  
**الاستعادة:** admin فقط — `POST .../docker/db-restore` (مرحلة 1 admin)

### C1 — إدارة خدمات وقواعد البيانات (بوابة العميل)

| الإجراء | Config key | API |
|---------|------------|-----|
| start/stop/restart service | `coolify.client_portal.actions.service_lifecycle` | `CoolifyApiService` |
| service logs | `service_logs` | `serviceLogs` أو logs endpoint |
| database start/stop/restart | `database_lifecycle` | `startDatabase` etc. |

**التحقق:** `ClientCoolifyProject` + `CoolifyTeamService::assertProjectInClientTeam`

### C2 — سجل النشرات (بوابة العميل)

| الإجراء | Config | API |
|---------|--------|-----|
| عرض آخر النشرات للتطبيق | `view_deployments` | `listDeploymentsByApplication` |
| نشر | `deploy` (موجود) | `deploy()` |

### C6 — لوحة عمليات موحّدة

دمج في `CoolifyOperationsService::build()`:

- `docker_infrastructure`: ملخص `docker system df` + تحذيرات قرص (≥85%)
- `docker_health_summary`: عدد مواقع WP بصحة غير سليمة
- الإبقاء على metrics الحالية + Coolify API health

## RBAC

- **Admin:** كل مسارات `admin/coolify/*` + docker restore
- **Client:** موارد مرتبطة بـ `ClientCoolifyProject` أو `CoolifyWordpressSite.user_id`
- **Jobs:** queue `coolify_management` أو `wordpress_management` من الإعدادات

## الملفات

| ملف | دور |
|-----|-----|
| `app/Services/Coolify/DockerHostService.php` | منطق Docker عبر SSH |
| `app/Jobs/WordpressSiteDatabaseBackupJob.php` | نسخ DB غير متزامن |
| `app/Http/Controllers/Admin/Coolify/CoolifyWordpressSiteDockerController.php` | API admin |
| `ClientCoolifyProjectController` | توسيع C1/C2 |
| `config/coolify.php` | مفاتيح `client_portal.actions` |
| `CoolifyOperationsService` | C6 docker summary |

## خارج النطاق (مرحلة 2+)

انظر `docs/superpowers/specs/2026-05-31-mcp-phase2-backlog.md`
