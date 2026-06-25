<style>
/* ── Page shell ── */
.cp-settings-page {
    --cp-accent: #21759b;
    --cp-accent-dark: #1a5f7a;
    --cp-accent-soft: rgba(33, 117, 155, 0.1);
    --cp-border: var(--default-border, #e9ecef);
}

.cp-settings-hero {
    border-radius: 1.15rem;
    padding: 1.35rem 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(33, 117, 155, 0.14) 0%, rgba(91, 95, 207, 0.08) 45%, rgba(34, 197, 94, 0.05) 100%);
    border: 1px solid rgba(33, 117, 155, 0.2);
    box-shadow: 0 8px 32px rgba(33, 117, 155, 0.08);
}

.cp-settings-hero__icon {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.35rem;
    background: linear-gradient(145deg, #21759b 0%, #1a5f7a 100%);
    box-shadow: 0 4px 14px rgba(33, 117, 155, 0.35);
    flex-shrink: 0;
}

.cp-settings-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.85rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: var(--custom-white, #fff);
    border: 1px solid var(--cp-border);
}

.cp-settings-status-pill--ok {
    border-color: rgba(34, 197, 94, 0.35);
    color: #16a34a;
}

.cp-settings-status-pill--warn {
    border-color: rgba(245, 158, 11, 0.4);
    color: #b45309;
}

.cp-settings-status-pill--muted {
    color: var(--text-muted, #6c757d);
}

.cp-settings-status-pill__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
}

.cp-settings-status-pill--ok .cp-settings-status-pill__dot {
    animation: cp-settings-pulse 1.8s infinite;
}

@keyframes cp-settings-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.45; transform: scale(0.85); }
}

/* ── KPI strip ── */
.cp-settings-kpi {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 0.85rem;
    border: 1px solid var(--cp-border);
    background: var(--custom-white, #fff);
    height: 100%;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.cp-settings-kpi:hover {
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    border-color: rgba(33, 117, 155, 0.2);
}

.cp-settings-kpi__icon {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.cp-settings-kpi__icon--conn { background: rgba(33, 117, 155, 0.12); color: #21759b; }
.cp-settings-kpi__icon--pass { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
.cp-settings-kpi__icon--api { background: rgba(91, 95, 207, 0.12); color: #5b5fcf; }
.cp-settings-kpi__icon--ssl { background: rgba(14, 165, 233, 0.12); color: #0ea5e9; }

.cp-settings-kpi__label {
    font-size: 0.72rem;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.1rem;
}

.cp-settings-kpi__value {
    font-size: 0.82rem;
    font-weight: 700;
}

/* ── Main panel + tabs ── */
.cp-settings-panel {
    border-radius: 1.15rem;
    border: 1px solid var(--cp-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
}

.cp-settings-panel__head {
    padding: 1rem 1rem 0;
    background: linear-gradient(180deg, rgba(33, 117, 155, 0.05) 0%, transparent 100%);
    border-bottom: 1px solid var(--cp-border);
}

.cp-settings-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    padding-bottom: 0.85rem;
}

.cp-settings-tabs__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.95rem;
    border: 1px solid transparent;
    border-radius: 2rem;
    background: transparent;
    color: var(--text-muted, #6c757d);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.15s, box-shadow 0.2s;
}

.cp-settings-tabs__btn:hover {
    color: var(--cp-accent-dark);
    border-color: rgba(33, 117, 155, 0.2);
    background: var(--cp-accent-soft);
    transform: translateY(-1px);
}

.cp-settings-tabs__btn.active {
    color: var(--cp-accent-dark);
    border-color: rgba(33, 117, 155, 0.35);
    background: var(--custom-white, #fff);
    box-shadow: 0 3px 12px rgba(33, 117, 155, 0.12);
}

.cp-settings-panel__body {
    padding: 1.35rem 1.35rem 0;
}

.cp-settings-tab-pane {
    display: none;
    animation: cp-settings-fade 0.25s ease;
}

.cp-settings-tab-pane.active {
    display: block;
}

@keyframes cp-settings-fade {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Form fields ── */
.cp-settings-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--default-text-color, #1a1a2e);
}

.cp-settings-section-title i {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--cp-accent-soft);
    color: var(--cp-accent);
    font-size: 0.9rem;
}

.cp-settings-field {
    margin-bottom: 1.1rem;
}

.cp-settings-field .form-label {
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.cp-settings-field .form-control,
.cp-settings-field .form-select {
    border-radius: 0.55rem;
    border-color: var(--cp-border);
    transition: border-color 0.15s, box-shadow 0.15s;
}

.cp-settings-field .form-control:focus,
.cp-settings-field .form-select:focus {
    border-color: rgba(33, 117, 155, 0.45);
    box-shadow: 0 0 0 3px rgba(33, 117, 155, 0.12);
}

.cp-settings-input-group {
    position: relative;
}

.cp-settings-input-group .cp-toggle-pass {
    position: absolute;
    inset-inline-start: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: var(--text-muted, #6c757d);
    padding: 0;
    line-height: 1;
    cursor: pointer;
    z-index: 2;
}

.cp-settings-input-group .form-control {
    padding-inline-start: 2.25rem;
}

.cp-settings-hint {
    font-size: 0.75rem;
    color: var(--text-muted, #6c757d);
    margin-top: 0.35rem;
    line-height: 1.5;
}

.cp-settings-hint--success { color: #16a34a; }

/* ── Toggle switch ── */
.cp-settings-switch {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
    border: 1px solid var(--cp-border);
    background: rgba(248, 250, 252, 0.8);
    height: 100%;
}

.cp-settings-switch__label {
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 0.15rem;
}

.cp-settings-switch__desc {
    font-size: 0.72rem;
    color: var(--text-muted, #6c757d);
    margin: 0;
}

.cp-settings-switch .form-check-input {
    width: 2.5rem;
    height: 1.35rem;
    cursor: pointer;
}

/* ── Info card ── */
.cp-settings-info-card {
    border-radius: 0.85rem;
    border: 1px solid rgba(14, 165, 233, 0.25);
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.06) 0%, rgba(33, 117, 155, 0.04) 100%);
    padding: 1rem 1.1rem;
    margin-bottom: 1.1rem;
}

.cp-settings-info-card__title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0369a1;
    margin-bottom: 0.5rem;
}

.cp-settings-info-card ol {
    margin: 0;
    padding-inline-start: 1.15rem;
    font-size: 0.75rem;
    color: var(--text-muted, #6c757d);
    line-height: 1.6;
}

/* ── Sticky footer ── */
.cp-settings-footer {
    position: sticky;
    bottom: 0;
    z-index: 10;
    margin: 1.25rem -1.35rem 0;
    padding: 1rem 1.35rem;
    border-top: 1px solid var(--cp-border);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, #fff 100%);
    backdrop-filter: blur(8px);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.cp-settings-footer__hint {
    font-size: 0.75rem;
    color: var(--text-muted, #6c757d);
}

.cp-settings-footer__hint.dirty {
    color: #b45309;
    font-weight: 600;
}

/* ── Sidebar cards ── */
.cp-settings-side-card {
    border-radius: 1rem;
    border: 1px solid var(--cp-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    margin-bottom: 1rem;
}

.cp-settings-side-card__head {
    padding: 0.85rem 1rem;
    font-size: 0.82rem;
    font-weight: 700;
    border-bottom: 1px solid var(--cp-border);
    background: rgba(248, 250, 252, 0.6);
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.cp-settings-side-card__body {
    padding: 1rem;
}

.cp-settings-test-result {
    display: none;
    margin-top: 0.85rem;
    padding: 0.75rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.78rem;
    line-height: 1.5;
}

.cp-settings-test-result.show { display: block; }
.cp-settings-test-result--ok {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #15803d;
}
.cp-settings-test-result--fail {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #b91c1c;
}
.cp-settings-test-result--loading {
    background: rgba(33, 117, 155, 0.08);
    border: 1px solid rgba(33, 117, 155, 0.2);
    color: var(--cp-accent-dark);
}

.cp-settings-quick-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.75rem;
    border-radius: 0.65rem;
    border: 1px solid var(--cp-border);
    text-decoration: none !important;
    color: inherit;
    margin-bottom: 0.5rem;
    transition: background 0.15s, border-color 0.15s, transform 0.15s;
}

.cp-settings-quick-link:last-child { margin-bottom: 0; }

.cp-settings-quick-link:hover {
    background: var(--cp-accent-soft);
    border-color: rgba(33, 117, 155, 0.25);
    transform: translateX(-2px);
}

.cp-settings-quick-link__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(108, 117, 125, 0.1);
    color: var(--cp-accent);
    flex-shrink: 0;
}

.cp-settings-quick-link__text {
    font-size: 0.82rem;
    font-weight: 600;
}

.cp-settings-quick-link__sub {
    font-size: 0.7rem;
    color: var(--text-muted, #6c757d);
}

.cp-settings-panel-url {
    direction: ltr;
    text-align: right;
    font-family: ui-monospace, monospace;
    font-size: 0.78rem;
    word-break: break-all;
    color: var(--cp-accent-dark);
    background: var(--cp-accent-soft);
    padding: 0.5rem 0.65rem;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
}
</style>
