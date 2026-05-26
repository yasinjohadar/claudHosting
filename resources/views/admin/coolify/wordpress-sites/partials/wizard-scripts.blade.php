<script>
(function() {
    const base = @json($baseDomain);
    const DOMAIN_PLATFORM = @json(\App\Models\CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM);
    const DOMAIN_CUSTOM = @json(\App\Models\CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM);

    // ——— نطاق فرعي (المنطق الأصلي) ———
    const nameEl = document.getElementById('displayName');
    const slugEl = document.getElementById('siteSlug');
    const preview = document.getElementById('urlPreview');

    function slugify(s) {
        return s.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || 'site';
    }

    function updateSubdomainPreview() {
        if (!preview || !slugEl) return;
        preview.textContent = 'https://' + (slugEl.value || '—') + '.' + base;
    }

    if (nameEl && slugEl) {
        nameEl.addEventListener('input', function() {
            if (!slugEl.dataset.touched) slugEl.value = slugify(nameEl.value);
            updateSubdomainPreview();
        });
        slugEl.addEventListener('input', function() {
            slugEl.dataset.touched = '1';
            updateSubdomainPreview();
        });
        updateSubdomainPreview();
    }

    // ——— تبديل نوع النطاق ———
    const domainTypeInput = document.getElementById('domainTypeInput');
    const subdomainBlock = document.getElementById('wpWizardSubdomainFields');
    const customBlock = document.getElementById('wpWizardCustomFields');
    const domainRadios = document.querySelectorAll('.domain-type-radio');

    function normalizeDomainInput(value) {
        return (value || '').toLowerCase().trim()
            .replace(/^https?:\/\//, '')
            .replace(/\/.*$/, '')
            .replace(/:\d+$/, '');
    }

    function slugFromDomain(domain) {
        const apex = domain.replace(/^www\./, '');
        return apex.replace(/\./g, '-').replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'site';
    }

    function updateCustomPreview() {
        const apexInput = document.getElementById('customDomainApex');
        const urlPreview = document.getElementById('customUrlPreview');
        const fbPreview = document.getElementById('customFbPreview');
        const internalSlug = document.getElementById('internalSlug');
        if (!apexInput) return;

        let apex = normalizeDomainInput(apexInput.value);
        apex = apex.replace(/^www\./, '');
        const useWww = document.querySelector('input[name="custom_host_choice"]:checked')?.value === 'www';
        const primary = useWww && apex ? 'www.' + apex : apex;

        if (urlPreview) urlPreview.textContent = primary ? 'https://' + primary : 'https://—';
        if (fbPreview) fbPreview.textContent = apex ? 'https://files.' + apex : 'https://files.—';
        if (internalSlug && !internalSlug.dataset.touched && apex) {
            internalSlug.value = slugFromDomain(apex);
        }
    }

    function setDomainType(type) {
        if (domainTypeInput) domainTypeInput.value = type;
        if (subdomainBlock) subdomainBlock.style.display = type === DOMAIN_CUSTOM ? 'none' : '';
        if (customBlock) customBlock.style.display = type === DOMAIN_CUSTOM ? '' : 'none';

        const subdomainPanel = subdomainBlock?.closest('.wp-wizard-panel');
        if (subdomainPanel) {
            subdomainPanel.querySelectorAll('#wpWizardSubdomainFields [required], #wpWizardCustomFields [required]').forEach(el => {
                el.required = false;
            });
        }
        if (type === DOMAIN_CUSTOM) {
            customBlock?.querySelectorAll('[required]').forEach(el => { el.required = true; });
            subdomainBlock?.querySelectorAll('[required]').forEach(el => { el.required = false; });
            updateCustomPreview();
        } else {
            subdomainBlock?.querySelectorAll('[required]').forEach(el => { el.required = true; });
            customBlock?.querySelectorAll('[required]').forEach(el => { el.required = false; });
        }
    }

    domainRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) setDomainType(this.value);
        });
    });

    const customApex = document.getElementById('customDomainApex');
    const internalSlug = document.getElementById('internalSlug');
    if (customApex) {
        customApex.addEventListener('input', updateCustomPreview);
        document.querySelectorAll('input[name="custom_host_choice"]').forEach(el => {
            el.addEventListener('change', updateCustomPreview);
        });
    }
    if (internalSlug) {
        internalSlug.addEventListener('input', function() { internalSlug.dataset.touched = '1'; });
    }
    if (domainTypeInput) {
        setDomainType(domainTypeInput.value || DOMAIN_PLATFORM);
    }

    // ——— مشروع مشترك (الخطوة 2) ———
    const wrap = document.getElementById('sharedProjectWrap');

    function toggleShared() {
        if (!wrap) return;
        const isShared = document.querySelector('input[name="project_mode"]:checked')?.value === 'shared';
        wrap.style.display = isShared ? 'block' : 'none';
        const sel = wrap.querySelector('select[name="project_uuid"]');
        if (sel) sel.required = isShared;
    }

    document.querySelectorAll('input[name="project_mode"]').forEach(el => el.addEventListener('change', toggleShared));
    toggleShared();
})();
</script>
