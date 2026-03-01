/**
 * project-detail.js — تعبئة صفحة تفاصيل المشروع من معامل URL ?project=1..6
 */
(function() {
    var BASE_URL = 'https://cloudsofthosting.com';
    var projects = [
        {
            title: 'موقع شركة استشارات تقنية',
            badge: 'موقع شركة',
            desc: 'موقع تعريفي لشركة استشارات تقنية يستضيف صفحات الخدمات، المدونة، ونماذج التواصل على استضافة كلاودسوفت السحابية.',
            icon: 'fa-globe',
            image: 'assets/images/course-webdev.svg',
            gallery: ['assets/images/course-webdev.svg', 'assets/images/course-python.svg', 'assets/images/course-mobile.svg', 'assets/images/logo.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'استضافة سحابية مشتركة' },
                { label: 'التخزين', value: '20 GB SSD' },
                { label: 'الباندويث', value: 'غير محدود' },
                { label: 'شهادة SSL', value: 'مجانية (Let\'s Encrypt)' },
                { label: 'النسخ الاحتياطي', value: 'يومي تلقائي' },
                { label: 'الدعم الفني', value: '24/7 عبر التذكرة والبريد' }
            ],
            tags: ['استضافة سحابية', 'SSL مجاني', 'نسخ احتياطي يومي'],
            visitUrl: '#',
            visitLabel: 'زيارة الموقع',
            codeUrl: '#'
        },
        {
            title: 'متجر إلكتروني للأدوات التقنية',
            badge: 'متجر إلكتروني',
            desc: 'متجر إلكتروني متكامل مع نظام سلة مشتريات، دفع إلكتروني آمن، واستضافة محسّنة للأداء العالي في أوقات الذروة.',
            icon: 'fa-shopping-cart',
            image: 'assets/images/course-python.svg',
            gallery: ['assets/images/course-python.svg', 'assets/images/course-webdev.svg', 'assets/images/course-mobile.svg', 'assets/images/logo.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'استضافة مشتركة بلس' },
                { label: 'التخزين', value: '50 GB SSD' },
                { label: 'الباندويث', value: 'غير محدود' },
                { label: 'CDN', value: 'مُفعّل عالمياً' },
                { label: 'حماية WAF', value: 'مُفعّلة' },
                { label: 'الدعم الفني', value: 'أولوية للمتاجر' }
            ],
            tags: ['استضافة مشتركة بلس', 'CDN', 'حماية WAF'],
            visitUrl: '#',
            visitLabel: 'زيارة المتجر',
            codeUrl: '#'
        },
        {
            title: 'منصة تعليمية إلكترونية',
            badge: 'منصة سحابية',
            desc: 'منصة دورات تعليمية تعمل بالكامل على خوادم VPS مُدارة لدينا مع موازنة أحمال ودعم تسجيل عدد كبير من المستخدمين.',
            icon: 'fa-mobile-alt',
            image: 'assets/images/course-mobile.svg',
            gallery: ['assets/images/course-mobile.svg', 'assets/images/course-webdev.svg', 'assets/images/course-python.svg', 'assets/images/logo.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'VPS مُدار' },
                { label: 'قاعدة البيانات', value: 'منفصلة ومُحسّنة' },
                { label: 'موازنة الأحمال', value: 'مُفعّلة' },
                { label: 'المراقبة', value: '24/7' },
                { label: 'النسخ الاحتياطي', value: 'كل 6 ساعات' },
                { label: 'الدعم الفني', value: 'مُخصص للمنصات' }
            ],
            tags: ['VPS مُدار', 'قاعدة بيانات منفصلة', 'مراقبة 24/7'],
            visitUrl: '#',
            visitLabel: 'زيارة المنصة',
            codeUrl: '#'
        },
        {
            title: 'بوابة إدارة لشركة خدمات لوجستية',
            badge: 'موقع شركة',
            desc: 'بوابة ويب لإدارة العملاء والشحنات يتم استضافتها على خوادم سحابية مع تشفير كامل للاتصال وضمان توافر عالٍ.',
            icon: 'fa-tasks',
            image: 'assets/images/course-webdev.svg',
            gallery: ['assets/images/course-webdev.svg', 'assets/images/course-python.svg', 'assets/images/logo.svg', 'assets/images/course-mobile.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'استضافة سحابية' },
                { label: 'التخزين', value: '30 GB SSD' },
                { label: 'SSL', value: 'شهادة ممتدّة' },
                { label: 'النسخ الاحتياطي', value: 'كل 6 ساعات' },
                { label: 'التوافر', value: '99.9% SLA' },
                { label: 'الدعم الفني', value: 'مُخصص للشركات' }
            ],
            tags: ['استضافة سحابية', 'SSL', 'نسخ احتياطي كل 6 ساعات'],
            visitUrl: '#',
            visitLabel: 'زيارة البوابة',
            codeUrl: '#'
        },
        {
            title: 'أداة إدارة مهام سحابية',
            badge: 'خدمة سحابية',
            desc: 'تطبيق إدارة مهام يعمل كخدمة SaaS مستضافة مع خطط اشتراك مختلفة وبيانات محفوظة على خوادم آمنة.',
            icon: 'fa-robot',
            image: 'assets/images/course-python.svg',
            gallery: ['assets/images/course-python.svg', 'assets/images/course-mobile.svg', 'assets/images/course-webdev.svg', 'assets/images/logo.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'استضافة SaaS مُخصصة' },
                { label: 'خطط الاشتراك', value: 'متعددة ومُدارة' },
                { label: 'قاعدة البيانات', value: 'معزولة ومشفّرة' },
                { label: 'النسخ الاحتياطي', value: 'يومي + نقطة استعادة' },
                { label: 'الأمان', value: 'تشفير كامل + 2FA' },
                { label: 'الدعم الفني', value: 'مُخصص لـ SaaS' }
            ],
            tags: ['استضافة SaaS', 'خطط اشتراك'],
            visitUrl: '#',
            visitLabel: 'زيارة الأداة',
            codeUrl: '#'
        },
        {
            title: 'منصة حجز خدمات وصيانة',
            badge: 'متجر خدمات',
            desc: 'منصة لحجز مواعيد الصيانة المنزلية للشركات والأفراد، تعمل على خوادمنا مع تكامل بريد وإشعارات.',
            icon: 'fa-wallet',
            image: 'assets/images/course-mobile.svg',
            gallery: ['assets/images/course-mobile.svg', 'assets/images/course-webdev.svg', 'assets/images/course-python.svg', 'assets/images/logo.svg'],
            videoUrl: '',
            specs: [
                { label: 'نوع الاستضافة', value: 'استضافة أعمال' },
                { label: 'البريد', value: 'بريد احترافي مُدمج' },
                { label: 'الإشعارات', value: 'بريد + SMS اختياري' },
                { label: 'SSL', value: 'مجاني' },
                { label: 'النسخ الاحتياطي', value: 'يومي' },
                { label: 'الدعم الفني', value: 'مُخصص للمنصات' }
            ],
            tags: ['استضافة أعمال', 'بريد احترافي'],
            visitUrl: '#',
            visitLabel: 'زيارة المنصة',
            codeUrl: '#'
        }
    ];

    function getProjectId() {
        var params = new URLSearchParams(window.location.search);
        var id = parseInt(params.get('project') || '1', 10);
        return (id >= 1 && id <= 6) ? id : 1;
    }

    function updateMeta(p, id) {
        var url = BASE_URL + '/project-detail.html?project=' + id;
        var title = p.title + ' | مشاريع استضافة كلاودسوفت';
        var desc = 'تفاصيل مشروع استضافة: ' + p.title + ' — معرض صور، مواصفات وتقنيات الاستضافة على كلاودسوفت.';

        document.title = title;
        var metaDesc = document.getElementById('metaDescription');
        if (metaDesc) metaDesc.setAttribute('content', desc);

        var canonical = document.getElementById('canonicalLink');
        if (canonical) canonical.setAttribute('href', url);

        var ogUrl = document.getElementById('ogUrl');
        if (ogUrl) ogUrl.setAttribute('content', url);
        var ogTitle = document.getElementById('ogTitle');
        if (ogTitle) ogTitle.setAttribute('content', title);
        var ogDesc = document.getElementById('ogDesc');
        if (ogDesc) ogDesc.setAttribute('content', desc);

        var twTitle = document.getElementById('twitterTitle');
        if (twTitle) twTitle.setAttribute('content', title);
        var twDesc = document.getElementById('twitterDesc');
        if (twDesc) twDesc.setAttribute('content', desc);
    }

    function renderGallery(container, gallery) {
        if (!container || !gallery || !gallery.length) return;
        container.innerHTML = gallery.map(function(src, i) {
            return '<div class="project-detail-gallery-item"><img src="' + src + '" alt="معرض المشروع ' + (i + 1) + '" loading="lazy" class="img-fluid rounded-3"></div>';
        }).join('');
    }

    function renderVideo(container, videoUrl) {
        if (!container) return;
        if (videoUrl) {
            var embed = videoUrl.replace('watch?v=', 'embed/').split('&')[0];
            container.innerHTML = '<div class="project-detail-video-embed"><iframe src="' + embed + '" allowfullscreen></iframe></div>';
        } else {
            container.innerHTML = '<div class="project-detail-video-placeholder"><i class="fas fa-play-circle"></i><p>فيديو تعريفي — يُضاف لاحقاً</p></div>';
        }
    }

    function renderSpecs(tbody, specs) {
        if (!tbody || !specs || !specs.length) return;
        tbody.innerHTML = specs.map(function(s) {
            return '<tr><td><strong>' + s.label + '</strong></td><td>' + s.value + '</td></tr>';
        }).join('');
    }

    function renderTags(container, tags) {
        if (!container || !tags || !tags.length) return;
        container.innerHTML = tags.map(function(t) {
            return '<span class="project-detail-tag">' + t + '</span>';
        }).join('');
    }

    function renderLinks(container, p) {
        if (!container) return;
        var html = '<a href="' + p.visitUrl + '" target="_blank" rel="noopener noreferrer" class="btn-project btn-project-primary"><i class="fas fa-external-link-alt"></i> ' + p.visitLabel + '</a>';
        if (p.codeUrl) html += ' <a href="' + p.codeUrl + '" target="_blank" rel="noopener noreferrer" class="btn-project btn-project-outline"><i class="fab fa-github"></i> الكود</a>';
        container.innerHTML = html;
    }

    function init() {
        var id = getProjectId();
        var p = projects[id - 1];
        if (!p) return;

        document.getElementById('bannerTitle').textContent = p.title;
        document.getElementById('bannerDesc').textContent = p.desc;
        document.getElementById('breadcrumbProjectName').textContent = p.title;

        document.getElementById('introImage').src = p.image;
        document.getElementById('introImage').alt = p.title;
        document.getElementById('introBadge').textContent = p.badge;
        document.getElementById('introTitle').textContent = p.title;
        document.getElementById('introDesc').textContent = p.desc;

        renderGallery(document.getElementById('galleryContainer'), p.gallery);
        renderVideo(document.getElementById('videoContainer'), p.videoUrl);
        renderSpecs(document.getElementById('specsTableBody'), p.specs);
        renderTags(document.getElementById('techTagsContainer'), p.tags);
        renderLinks(document.getElementById('projectLinksContainer'), p);

        updateMeta(p, id);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
