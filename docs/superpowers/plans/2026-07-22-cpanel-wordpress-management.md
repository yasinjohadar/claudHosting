# cPanel WordPress Management (Phase 1) Implementation Plan

> **For agentic workers:** Execute task-by-task. Skip git commits unless the user explicitly asks. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Discover WordPress sites inside cPanel accounts (Softaculous, WP Toolkit, manual scan) and expose a client+admin management page with open site / wp-admin / manager SSO.

**Architecture:** Local `whm_wordpress_sites` cache filled by `WhmWordpressDiscoveryService` adapters; portal actions via `WhmWordpressPortalService` using existing WHM `createUserSession` + Softaculous `sign_on` when available.

**Tech Stack:** Laravel, WhmApiService (UAPI + create_user_session), Blade client-portal / admin domain-ui patterns.

---

## File map

| File | Responsibility |
|------|----------------|
| `database/migrations/2026_07_22_100000_create_whm_wordpress_sites_table.php` | Schema |
| `app/Models/WhmWordpressSite.php` | Eloquent model |
| `app/Services/Whm/Wordpress/Contracts/WordpressDiscoveryAdapter.php` | Adapter interface |
| `app/Services/Whm/Wordpress/Adapters/SoftaculousAdapter.php` | Softaculous list |
| `app/Services/Whm/Wordpress/Adapters/WpToolkitAdapter.php` | WP Toolkit list |
| `app/Services/Whm/Wordpress/Adapters/ManualScanAdapter.php` | File scan for wp-config |
| `app/Services/Whm/Wordpress/WhmWordpressDiscoveryService.php` | Orchestrate + upsert |
| `app/Services/Whm/Wordpress/WhmWordpressPortalService.php` | Open / wp-admin / manager URLs |
| `app/Http/Controllers/Admin/Whm/WhmWordpressSiteController.php` | Admin UI |
| `app/Http/Controllers/Client/ClientWhmWordpressSiteController.php` | Client UI |
| Views under `resources/views/{admin,client}/...` | List + show |
| `routes/web.php`, `ClientPortalController`, `services.blade.php` | Wiring |

---

### Task 1: Migration + Model

- [ ] Create migration and `WhmWordpressSite` model with relations to `WhmAccount`
- [ ] Add `wordpressSites()` on `WhmAccount`
- [ ] Run `php artisan migrate`

### Task 2: Discovery adapters + services

- [ ] Interface + three adapters + discovery + portal services
- [ ] Softaculous: session cookie → installations API (sid 26)
- [ ] WP Toolkit: WHM `wp-toolkit` method if available
- [ ] Manual: DomainInfo docroots + Fileman check for `wp-config.php` (depth ≤ 2)

### Task 3: Controllers + routes

- [ ] Admin + Client controllers (ownership checks on client)
- [ ] Routes under `client.hosting.wordpress.*` and `admin.whm.accounts.wordpress.*`

### Task 4: Views + services tab integration

- [ ] Client index/show pages; admin index/show
- [ ] WordPress tab merges Coolify + WHM sites; hosting row adds WP button
- [ ] Link from admin WHM account show

### Task 5: Smoke verify

- [ ] `php artisan route:list --name=wordpress`
- [ ] `php artisan migrate --pretend` / migrate status OK
