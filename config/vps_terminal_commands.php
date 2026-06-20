<?php

/**
 * VPS host SSH terminal — categorized quick commands.
 */
return [

    'groups' => [
        'system' => [
            'label' => 'معلومات النظام',
            'icon' => 'fe fe-monitor',
            'color' => 'primary',
            'commands' => [
                ['label' => 'معلومات النواة', 'command' => 'uname -a', 'description' => 'نواة النظام والمعمارية'],
                ['label' => 'اسم المضيف', 'command' => 'hostname -f', 'description' => 'FQDN للسيرفر'],
                ['label' => 'وقت التشغيل', 'command' => 'uptime', 'description' => 'Load average ومدة التشغيل'],
                ['label' => 'المستخدم الحالي', 'command' => 'whoami && id', 'description' => 'UID/GID للجلسة'],
                ['label' => 'التاريخ والوقت', 'command' => 'date -Is && timedatectl status 2>/dev/null | head -5', 'description' => 'الوقت والمنطقة الزمنية'],
                ['label' => 'إصدار OS', 'command' => 'cat /etc/os-release 2>/dev/null | head -10', 'description' => 'توزيعة Linux'],
            ],
        ],
        'resources' => [
            'label' => 'الموارد',
            'icon' => 'fe fe-activity',
            'color' => 'success',
            'commands' => [
                ['label' => 'الذاكرة', 'command' => 'free -h', 'description' => 'RAM و Swap'],
                ['label' => 'القرص', 'command' => 'df -hT', 'description' => 'استخدام الأقراص وأنواع FS'],
                ['label' => 'Inode', 'command' => 'df -hi', 'description' => 'استخدام inodes'],
                ['label' => 'المعالج', 'command' => 'lscpu 2>/dev/null | grep -E "Model name|CPU\\(s\\)|Thread|MHz" || nproc', 'description' => 'مواصفات CPU'],
                ['label' => 'أعلى العمليات', 'command' => 'ps aux --sort=-%mem | head -12', 'description' => 'أكثر العمليات استهلاكاً للذاكرة'],
                ['label' => 'Swap', 'command' => 'swapon --show 2>/dev/null; free -h | grep -i swap', 'description' => 'حالة Swap'],
            ],
        ],
        'docker_overview' => [
            'label' => 'Docker — نظرة عامة',
            'icon' => 'fab fa-docker',
            'color' => 'info',
            'commands' => [
                ['label' => 'الحاويات النشطة', 'command' => 'docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"', 'description' => 'قائمة الحاويات الجارية'],
                ['label' => 'كل الحاويات', 'command' => 'docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Image}}"', 'description' => 'بما فيها المتوقفة'],
                ['label' => 'إحصائيات لحظية', 'command' => 'docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"', 'description' => 'CPU/RAM/Net لكل حاوية'],
                ['label' => 'استخدام Docker', 'command' => 'docker system df', 'description' => 'Images / Containers / Volumes'],
                ['label' => 'إصدار Docker', 'command' => 'docker version && docker info 2>/dev/null | head -25', 'description' => 'Client/Server info'],
                ['label' => 'الشبكات', 'command' => 'docker network ls', 'description' => 'شبكات Docker'],
                ['label' => 'Volumes', 'command' => 'docker volume ls', 'description' => 'أحجام التخزين'],
            ],
        ],
        'docker_logs' => [
            'label' => 'Docker — السجلات',
            'icon' => 'fe fe-file-text',
            'color' => 'warning',
            'commands' => [
                ['label' => 'أحداث Docker', 'command' => 'docker events --since 10m --until 0s 2>/dev/null | tail -20 || echo "no recent events"', 'description' => 'آخر 20 حدث'],
                ['label' => 'سجلات Traefik/Proxy', 'command' => 'docker logs --tail 50 coolify-proxy 2>/dev/null || docker logs --tail 50 traefik 2>/dev/null || echo "proxy container not found"', 'description' => 'آخر 50 سطر'],
            ],
        ],
        'coolify' => [
            'label' => 'Coolify',
            'icon' => 'fe fe-layers',
            'color' => 'purple',
            'commands' => [
                ['label' => 'مسار Coolify', 'command' => 'ls -la /data/coolify 2>/dev/null || ls -la ~/coolify 2>/dev/null || echo "Coolify path not found"', 'description' => 'محتويات مجلد Coolify'],
                ['label' => 'حاويات Coolify', 'command' => 'docker ps --filter "name=coolify" --format "table {{.Names}}\t{{.Status}}"', 'description' => 'حاويات باسم coolify'],
                ['label' => 'Compose projects', 'command' => 'find /data/coolify -name docker-compose.yml 2>/dev/null | head -15', 'description' => 'مسارات compose'],
            ],
        ],
        'network' => [
            'label' => 'الشبكة',
            'icon' => 'fe fe-wifi',
            'color' => 'teal',
            'commands' => [
                ['label' => 'عناوين IP', 'command' => 'ip -br a 2>/dev/null || ifconfig -a 2>/dev/null | head -20', 'description' => 'واجهات الشبكة'],
                ['label' => 'المنافذ المفتوحة', 'command' => 'ss -tulpn 2>/dev/null | head -25', 'description' => 'TCP/UDP listening'],
                ['label' => 'Routing', 'command' => 'ip route show 2>/dev/null | head -15', 'description' => 'جدول التوجيه'],
                ['label' => 'DNS', 'command' => 'cat /etc/resolv.conf', 'description' => 'خوادم DNS'],
                ['label' => 'IP العام', 'command' => 'curl -s --max-time 5 ifconfig.me 2>/dev/null || curl -s --max-time 5 icanhazip.com', 'description' => 'العنوان الخارجي'],
                ['label' => 'Ping Google', 'command' => 'ping -c 3 8.8.8.8', 'description' => 'اختبار اتصال ICMP'],
            ],
        ],
        'logs' => [
            'label' => 'سجلات النظام',
            'icon' => 'fe fe-book-open',
            'color' => 'secondary',
            'commands' => [
                ['label' => 'Journal (آخر 40)', 'command' => 'journalctl -n 40 --no-pager 2>/dev/null || echo "journalctl unavailable"', 'description' => 'systemd journal'],
                ['label' => 'Kernel', 'command' => 'dmesg --ctime 2>/dev/null | tail -25', 'description' => 'رسائل النواة'],
                ['label' => 'Auth log', 'command' => 'tail -30 /var/log/auth.log 2>/dev/null || tail -30 /var/log/secure 2>/dev/null || echo "auth log not found"', 'description' => 'محاولات الدخول'],
            ],
        ],
        'security' => [
            'label' => 'الأمان',
            'icon' => 'fe fe-shield',
            'color' => 'danger',
            'commands' => [
                ['label' => 'UFW', 'command' => 'ufw status verbose 2>/dev/null || echo "ufw not installed"', 'description' => 'حالة الجدار الناري'],
                ['label' => 'Fail2ban', 'command' => 'fail2ban-client status 2>/dev/null || echo "fail2ban not installed"', 'description' => 'حالة fail2ban'],
                ['label' => 'SSH config', 'command' => 'grep -E "^PermitRootLogin|^PasswordAuthentication|^Port" /etc/ssh/sshd_config 2>/dev/null | grep -v "^#"', 'description' => 'إعدادات SSH (قراءة فقط)'],
            ],
        ],
        'maintenance' => [
            'label' => 'صيانة Docker',
            'icon' => 'fe fe-tool',
            'color' => 'warning',
            'commands' => [
                [
                    'label' => 'حذف الحاويات المتوقفة',
                    'command' => 'docker container prune -f',
                    'description' => 'إزالة containers متوقفة',
                    'confirm' => true,
                    'danger' => true,
                ],
                [
                    'label' => 'حذف الصور غير المستخدمة',
                    'command' => 'docker image prune -f',
                    'description' => 'dangling images فقط',
                    'confirm' => true,
                    'danger' => true,
                ],
                [
                    'label' => 'حذف الشبكات غير المستخدمة',
                    'command' => 'docker network prune -f',
                    'description' => 'شبكات بدون حاويات',
                    'confirm' => true,
                    'danger' => true,
                ],
            ],
        ],
    ],

    'blocked_patterns' => [
        '/\brm\s+-rf\s+\/\s*$/i',
        '/\brm\s+-rf\s+\/\s/i',
        '/\bmkfs\b/i',
        '/\bshutdown\b/i',
        '/\breboot\b/i',
        '/\bpoweroff\b/i',
        '/\bhalt\b/i',
        '/\bdd\s+if=/i',
        '/:\(\)\s*\{\s*:\|\s*:\s*&\s*\}\s*;/',
        '/\bchmod\s+-R\s+777\s+\//i',
        '/\bdocker\s+system\s+prune\s+-a\b/i',
    ],

];
