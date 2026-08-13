# تصميم: إدارة ووردبريس cPanel بنفس لوحة Coolify

**التاريخ:** 2026-07-22  
**الحالة:** معتمد — قيد التنفيذ

## الهدف
نفس لوحة إدارة Coolify WordPress (كل التبويبات والإجراءات عدا Docker) لمواقع `WhmWordpressSite` عبر SSH + WP-CLI على سيرفر WHM.

## المعمارية
- إعدادات SSH في WHM settings (host من WHM API host، user/key/port)
- `WhmSshExecutor` → `WhmWordpressCliService` (su/cPanel user + wp-cli.phar في مسار الموقع)
- `WhmWordpressManagementService` يعيد استخدام `wordpress_cli.php` + `WordpressCliActionRunner`
- واجهة: نفس `management.blade.php` مع `WhmWordpressSiteRouteMap`
- إخفاء تبويب Docker في سياق cPanel

## خارج النطاق
- Docker compose lifecycle (لا ينطبق)
