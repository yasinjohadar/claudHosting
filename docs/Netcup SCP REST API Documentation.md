# Netcup SCP REST API Documentation

## Authentication

يستخدم الـ API نظام Bearer Token.

### 1. Generate Device Code

```bash
curl -X POST \
https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/auth/device \
-d client_id=scp \
-d scope="offline_access openid"
```

### Response

```json
{
  "device_code": "...",
  "user_code": "...",
  "verification_uri": "...",
  "verification_uri_complete": "...",
  "expires_in": 600,
  "interval": 5
}
```

### 2. Authorize Device

افتح:

```text
verification_uri_complete
```

وقم بتسجيل الدخول والموافقة على الصلاحيات.

### 3. Exchange Device Code For Token

```bash
curl -X POST \
https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/token \
-d grant_type=urn:ietf:params:oauth:grant-type:device_code \
-d device_code=<device_code> \
-d client_id=scp
```

### Response

```json
{
  "access_token": "...",
  "expires_in": 300,
  "refresh_token": "...",
  "scope": "profile offline_access email"
}
```

---

## Refresh Token

```bash
curl -X POST \
https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/token \
-d client_id=scp \
-d refresh_token=<refresh_token> \
-d grant_type=refresh_token
```

---

## Revoke Refresh Token

```bash
curl -X POST \
https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/revoke \
-d client_id=scp \
-d token=<refresh_token> \
-d token_type_hint=refresh_token
```

---

## User Information

```bash
curl \
https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/userinfo \
-H "Authorization: Bearer <access_token>"
```

---

# Miscellaneous

| Method | Endpoint | Description |
|----------|----------|-------------|
| GET | /api/ping | Check API availability |
| GET | /api/v1/maintenance | Maintenance information |
| GET | /api/v1/openapi | OpenAPI specification |
| POST | /api/v1/openapi/mcp | MCP OpenAPI |

---

# Server Disks

| Method | Endpoint |
|----------|----------|
| PATCH | /api/v1/servers/{serverId}/disks |
| GET | /api/v1/servers/{serverId}/disks |
| GET | /api/v1/servers/{serverId}/disks/supported-drivers |
| GET | /api/v1/servers/{serverId}/disks/{diskName} |
| POST | /api/v1/servers/{serverId}/disks/{diskName}/format |

---

# Firewall

| Method | Endpoint |
|----------|----------|
| PUT | /api/v1/servers/{serverId}/interfaces/{mac}/firewall |
| GET | /api/v1/servers/{serverId}/interfaces/{mac}/firewall |
| POST | /api/v1/servers/{serverId}/interfaces/{mac}/firewall:reapply |
| POST | /api/v1/servers/{serverId}/interfaces/{mac}/firewall:restore-copied-policies |
| POST | /api/v1/users/{userId}/firewall-policies |
| GET | /api/v1/users/{userId}/firewall-policies |
| PUT | /api/v1/users/{userId}/firewall-policies/{id} |
| DELETE | /api/v1/users/{userId}/firewall-policies/{id} |
| GET | /api/v1/users/{userId}/firewall-policies/{id} |

---

# ISO Management

| Method | Endpoint |
|----------|----------|
| POST | /api/v1/servers/{serverId}/iso |
| DELETE | /api/v1/servers/{serverId}/iso |
| GET | /api/v1/servers/{serverId}/iso |
| GET | /api/v1/servers/{serverId}/isoimages |
| GET | /api/v1/users/{userId}/isos |
| POST | /api/v1/users/{userId}/isos/{key} |
| DELETE | /api/v1/users/{userId}/isos/{key} |
| GET | /api/v1/users/{userId}/isos/{key} |
| PUT | /api/v1/users/{userId}/isos/{key}/{uploadId} |
| GET | /api/v1/users/{userId}/isos/{key}/{uploadId}/parts/{partNumber} |

---

# Images

| Method | Endpoint |
|----------|----------|
| POST | /api/v1/servers/{serverId}/image |
| GET | /api/v1/servers/{serverId}/imageflavours |
| POST | /api/v1/servers/{serverId}/user-image |
| GET | /api/v1/users/{userId}/images |
| POST | /api/v1/users/{userId}/images/{key} |
| DELETE | /api/v1/users/{userId}/images/{key} |

---

# Metrics

| Query parameter: `hours` (1–1440, default 6). Response is a map of ISO-8601 timestamps to per-resource values.

**Units (per Netcup documentation):**
- CPU: operations per second per core (`ops/s`, display as K/M/G)
- Disk: IOPS read/write per second
- Network: bytes per second (`B/s`)
- Packets: packets per second (`pps`)

| Method | Endpoint |
|----------|----------|
| GET | /api/v1/servers/{serverId}/metrics/cpu?hours=6 |
| GET | /api/v1/servers/{serverId}/metrics/disk?hours=6 |
| GET | /api/v1/servers/{serverId}/metrics/network?hours=6 |
| GET | /api/v1/servers/{serverId}/metrics/network/packet?hours=6 |

---

# Networking

## RDNS

```text
POST   /api/v1/rdns/ipv4
DELETE /api/v1/rdns/ipv4/{ip}
GET    /api/v1/rdns/ipv4/{ip}

POST   /api/v1/rdns/ipv6
DELETE /api/v1/rdns/ipv6/{ip}
GET    /api/v1/rdns/ipv6/{ip}
```

## Interfaces

```text
POST   /api/v1/servers/{serverId}/interfaces
GET    /api/v1/servers/{serverId}/interfaces
PUT    /api/v1/servers/{serverId}/interfaces/{mac}
DELETE /api/v1/servers/{serverId}/interfaces/{mac}
GET    /api/v1/servers/{serverId}/interfaces/{mac}
```

## Failover IP

```text
GET    /api/v1/users/{userId}/failoverips/ipv4
PATCH  /api/v1/users/{userId}/failoverips/ipv4/{id}

GET    /api/v1/users/{userId}/failoverips/ipv6
PATCH  /api/v1/users/{userId}/failoverips/ipv6/{id}
```

---

# Snapshots

```text
POST   /api/v1/servers/{serverId}/snapshots
GET    /api/v1/servers/{serverId}/snapshots
DELETE /api/v1/servers/{serverId}/snapshots/{name}
GET    /api/v1/servers/{serverId}/snapshots/{name}
POST   /api/v1/servers/{serverId}/snapshots/{name}/export
POST   /api/v1/servers/{serverId}/snapshots/{name}/revert
POST   /api/v1/servers/{serverId}/snapshots:dryrun
```

---

# Servers

```text
GET    /api/v1/servers
PATCH  /api/v1/servers/{serverId}
GET    /api/v1/servers/{serverId}

GET    /api/v1/servers/{serverId}/gpu-driver

GET    /api/v1/servers/{serverId}/guest-agent
GET    /api/v1/servers/{serverId}/guest-agent/status

GET    /api/v1/servers/{serverId}/logs

POST   /api/v1/servers/{serverId}/rescuesystem
DELETE /api/v1/servers/{serverId}/rescuesystem
GET    /api/v1/servers/{serverId}/rescuesystem

POST   /api/v1/servers/{serverId}/storageoptimization
```

---

# Tasks

```text
GET /api/v1/tasks
GET /api/v1/tasks/{uuid}
PUT /api/v1/tasks/{uuid}:cancel
```

---

# Users

```text
PUT    /api/v1/users/{userId}
GET    /api/v1/users/{userId}
GET    /api/v1/users/{userId}/logs

POST   /api/v1/users/{userId}/ssh-keys
GET    /api/v1/users/{userId}/ssh-keys
DELETE /api/v1/users/{userId}/ssh-keys/{id}
```

---

# Important Notes

- Client ID المستخدم في التوثيق الرسمي هو:

```text
scp
```

- لا يوجد Client Secret في طريقة Device Authorization Flow المستخدمة من Netcup.
- يتم الحصول على Access Token و Refresh Token بعد موافقة المستخدم.
- الـ Refresh Token صالح طالما يتم استخدامه مرة واحدة كل 30 يوماً.

---

## Implementation Notes (Laravel)

| Layer | Path |
|-------|------|
| HTTP client | `app/Services/Infrastructure/Netcup/NetcupScpClient.php` |
| Domain services | `app/Services/Infrastructure/Netcup/Netcup*Service.php` |
| Provider adapter | `app/Services/Infrastructure/NetcupVpsProvider.php` |
| Admin console API | `InfrastructureNetcupController` → `/admin/infrastructure/servers/{uuid}/netcup/*` |
| Device Flow UI | `admin/infrastructure/settings?provider=netcup` |

### Example: PATCH server power state

```http
PATCH /api/v1/servers/{serverId}?stateOption=POWEROFF
Authorization: Bearer <access_token>
Content-Type: application/json

{"state":"OFF"}
```

### Example: POST image setup

```http
POST /api/v1/servers/{serverId}/image
Content-Type: application/json

{
  "imageFlavourId": "debian-12",
  "hostname": "my-vps",
  "sshKeyIds": [1]
}
```

Response may include `taskUuid` — poll `GET /api/v1/tasks/{uuid}` until `status` is `DONE`.