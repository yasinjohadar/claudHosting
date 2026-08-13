<style>
.whm-settings-page {
    --whm-accent: #0057B8;
    --whm-accent-2: #2E9AD0;
    --whm-accent-soft: rgba(0, 87, 184, 0.1);
    --whm-border: var(--default-border, #e9ecef);
}

.whm-settings-hero {
    border-radius: 1.15rem;
    padding: 1.35rem 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(0, 87, 184, 0.14) 0%, rgba(46, 154, 208, 0.1) 50%, rgba(243, 156, 18, 0.06) 100%);
    border: 1px solid rgba(0, 87, 184, 0.18);
    box-shadow: 0 8px 32px rgba(0, 87, 184, 0.07);
}

.whm-settings-hero__icon {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.35rem;
    background: linear-gradient(145deg, #0057B8 0%, #2E9AD0 100%);
    box-shadow: 0 4px 14px rgba(0, 87, 184, 0.35);
    flex-shrink: 0;
}

.whm-settings-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.85rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: var(--custom-white, #fff);
    border: 1px solid var(--whm-border);
}

.whm-settings-status-pill--ok { border-color: rgba(34, 197, 94, 0.35); color: #16a34a; }
.whm-settings-status-pill--warn { border-color: rgba(245, 158, 11, 0.4); color: #b45309; }
.whm-settings-status-pill--muted { color: var(--text-muted, #6c757d); }

.whm-settings-status-pill__dot {
    width: 7px; height: 7px; border-radius: 50%; background: currentColor;
}
.whm-settings-status-pill--ok .whm-settings-status-pill__dot {
    animation: whm-settings-pulse 1.8s infinite;
}
@keyframes whm-settings-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.45; transform: scale(0.85); }
}

.whm-settings-kpi {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1rem; height: 100%;
    border-radius: 0.9rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--whm-border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.whm-settings-kpi__icon {
    width: 2.4rem; height: 2.4rem; border-radius: 0.65rem;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1.05rem;
}
.whm-settings-kpi__icon--api { background: rgba(0, 87, 184, 0.12); color: #0057B8; }
.whm-settings-kpi__icon--ssh { background: rgba(46, 154, 208, 0.14); color: #1a7aab; }
.whm-settings-kpi__icon--pkg { background: rgba(243, 156, 18, 0.14); color: #c47d0a; }
.whm-settings-kpi__icon--bill { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
.whm-settings-kpi__label { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.1rem; }
.whm-settings-kpi__value { font-size: 0.92rem; font-weight: 700; }

.whm-settings-panel {
    border-radius: 1rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--whm-border);
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.whm-settings-panel__head {
    padding: 0.65rem 0.85rem 0;
    border-bottom: 1px solid var(--whm-border);
    background: rgba(0, 87, 184, 0.03);
}
.whm-settings-tabs {
    display: flex; flex-wrap: wrap; gap: 0.25rem;
}
.whm-settings-tabs__btn {
    border: 0; background: transparent;
    padding: 0.7rem 1rem;
    font-size: 0.85rem; font-weight: 600;
    color: var(--text-muted, #6c757d);
    border-radius: 0.65rem 0.65rem 0 0;
    display: inline-flex; align-items: center; gap: 0.4rem;
}
.whm-settings-tabs__btn:hover { color: #0057B8; background: rgba(0, 87, 184, 0.06); }
.whm-settings-tabs__btn.active {
    color: #0057B8;
    background: var(--custom-white, #fff);
    box-shadow: 0 -1px 0 #fff, inset 0 -2px 0 #0057B8;
}

.whm-settings-panel__body { padding: 1.35rem 1.35rem 0.5rem; }
.whm-settings-tab-pane { display: none; }
.whm-settings-tab-pane.active { display: block; }

.whm-settings-section-title {
    display: flex; align-items: center; gap: 0.5rem;
    font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem;
}
.whm-settings-section-title i { color: #0057B8; }
.whm-settings-hint { font-size: 0.8rem; color: var(--text-muted); }
.whm-settings-field { margin-bottom: 1rem; }

.whm-settings-verify {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1rem; margin: 1rem 0 0.25rem;
    border-radius: 0.75rem;
    background: linear-gradient(90deg, rgba(0, 87, 184, 0.06), rgba(46, 154, 208, 0.05));
    border: 1px dashed rgba(0, 87, 184, 0.25);
}
.whm-settings-verify__result { font-size: 0.82rem; font-weight: 600; }

.whm-settings-footer {
    display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;
    padding: 1rem 1.35rem;
    border-top: 1px solid var(--whm-border);
    background: rgba(0, 87, 184, 0.02);
}
.whm-settings-footer__hint { font-size: 0.78rem; color: var(--text-muted); }

.whm-settings-side-card {
    border-radius: 1rem; padding: 1.15rem 1.25rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--whm-border);
    margin-bottom: 1rem;
}
.whm-settings-side-card h6 { font-weight: 700; margin-bottom: 0.65rem; }
.whm-settings-side-card ul { padding-inline-start: 1.1rem; margin: 0; }
.whm-settings-side-card li { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.35rem; }

[data-theme-mode=dark] .whm-settings-panel,
[data-theme-mode=dark] .whm-settings-kpi,
[data-theme-mode=dark] .whm-settings-side-card,
[data-theme-mode=dark] .whm-settings-status-pill {
    background: var(--custom-white);
}
</style>
