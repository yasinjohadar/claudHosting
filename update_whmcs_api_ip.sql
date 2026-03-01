-- تحديث Allowed IPs لـ API Credentials في قاعدة بيانات WHMCS
-- استخدم هذا SQL إذا كان لديك وصول مباشر لقاعدة بيانات WHMCS

-- 1. أولاً، ابحث عن API Credential الخاص بك:
SELECT id, identifier, allowed_ips 
FROM tblapicredentials 
WHERE identifier = 'wCHgQn6rE1hMJe1mumqtXKQIoyeBrtJV';

-- 2. ثم حدّث allowed_ips (استبدل ID بالرقم المناسب من النتيجة أعلاه):
-- للسماح بـ IP محدد:
UPDATE tblapicredentials 
SET allowed_ips = '213.142.157.77' 
WHERE identifier = 'wCHgQn6rE1hMJe1mumqtXKQIoyeBrtJV';

-- أو للسماح بأي IP (غير آمن، للتجربة فقط):
UPDATE tblapicredentials 
SET allowed_ips = '' 
WHERE identifier = 'wCHgQn6rE1hMJe1mumqtXKQIoyeBrtJV';

-- للسماح بعدة IPs (افصلهم بفواصل):
UPDATE tblapicredentials 
SET allowed_ips = '213.142.157.77,127.0.0.1' 
WHERE identifier = 'wCHgQn6rE1hMJe1mumqtXKQIoyeBrtJV';
