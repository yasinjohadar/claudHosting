<script>
(function () {
    const tabMap = {
        overview: '#siteTabOverview',
        wordpress: '#siteTabWordpress',
        backups: '#siteTabBackups',
        hosting: '#siteTabHosting',
        tools: '#siteTabCyberPanel',
    };
    const reverseMap = Object.fromEntries(Object.entries(tabMap).map(([k, v]) => [v, k]));
    const tabList = document.getElementById('cpWpSiteTabs');
    const tabButtons = tabList ? tabList.querySelectorAll('.cp-wp-tabs__item[data-bs-toggle="tab"]') : [];

    function setActiveTabButton(target) {
        tabButtons.forEach(btn => {
            const isActive = btn.getAttribute('data-bs-target') === target;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function syncUrl(tabKey) {
        const url = new URL(window.location.href);
        if (tabKey && tabKey !== 'overview') {
            url.searchParams.set('tab', tabKey);
        } else {
            url.searchParams.delete('tab');
        }
        history.replaceState(null, '', url);
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function (e) {
            const target = e.target.getAttribute('data-bs-target');
            const key = e.target.dataset.cpTab || reverseMap[target];
            setActiveTabButton(target);
            if (key) syncUrl(key);
        });
        btn.addEventListener('click', function () {
            tabList?.querySelectorAll('.cp-wp-tabs__item').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab && tabMap[tab]) {
        const btn = document.querySelector('[data-bs-target="' + tabMap[tab] + '"]');
        if (btn && window.bootstrap?.Tab) {
            new bootstrap.Tab(btn).show();
            setActiveTabButton(tabMap[tab]);
        }
    }

    document.querySelectorAll('#cpWpSiteTabContent > .tab-pane').forEach(pane => {
        pane.addEventListener('shown.bs.tab', () => {
            pane.style.animation = 'none';
            void pane.offsetWidth;
            pane.style.animation = '';
        });
    });
})();
</script>
