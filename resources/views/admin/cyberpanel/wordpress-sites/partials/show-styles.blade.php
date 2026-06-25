@include('admin.cyberpanel.websites.partials.show-styles')
<style>
/* ── Page shell ── */
.cp-wp-show-page .cp-wp-show-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.75rem;
}

.cp-wp-show-page .site-show-hero {
    background: linear-gradient(135deg, rgba(33, 117, 155, 0.14) 0%, rgba(91, 95, 207, 0.08) 45%, rgba(34, 197, 94, 0.06) 100%);
    border-color: rgba(33, 117, 155, 0.2);
    box-shadow: 0 8px 32px rgba(33, 117, 155, 0.08);
}

/* ── Main tabs panel ── */
.cp-wp-tabs-panel {
    border-radius: 1.15rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
}

.cp-wp-tabs-panel__head {
    padding: 1rem 1rem 0;
    background: linear-gradient(180deg, rgba(91, 95, 207, 0.05) 0%, transparent 100%);
    border-bottom: 1px solid var(--default-border, #e9ecef);
}

.cp-wp-tabs {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.5rem;
    padding-bottom: 0.85rem;
}

@media (max-width: 1199.98px) {
    .cp-wp-tabs {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 0.45rem;
        padding-bottom: 0.65rem;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    .cp-wp-tabs__item { min-width: 9.5rem; flex-shrink: 0; }
}

.cp-wp-tabs__item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.15rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid transparent;
    border-radius: 0.75rem;
    background: transparent;
    color: var(--text-muted, #6c757d);
    text-align: right;
    cursor: pointer;
    transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
}

.cp-wp-tabs__item::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    background: linear-gradient(135deg, rgba(33, 117, 155, 0.12) 0%, rgba(91, 95, 207, 0.1) 100%);
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.cp-wp-tabs__item:hover {
    color: var(--default-text-color, #1a1a2e);
    border-color: rgba(91, 95, 207, 0.2);
    transform: translateY(-1px);
}

.cp-wp-tabs__item:hover::before { opacity: 1; }

.cp-wp-tabs__item.active {
    color: #1a5f7a;
    border-color: rgba(33, 117, 155, 0.35);
    background: var(--custom-white, #fff);
    box-shadow: 0 4px 16px rgba(33, 117, 155, 0.12);
}

.cp-wp-tabs__item.active::before { opacity: 1; }

.cp-wp-tabs__item.active .cp-wp-tabs__icon {
    background: linear-gradient(145deg, #21759b 0%, #1a5f7a 100%);
    color: #fff;
    box-shadow: 0 3px 10px rgba(33, 117, 155, 0.35);
}

.cp-wp-tabs__top {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
}

.cp-wp-tabs__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    background: rgba(108, 117, 125, 0.1);
    color: var(--text-muted, #6c757d);
    flex-shrink: 0;
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.cp-wp-tabs__label {
    font-weight: 700;
    font-size: 0.84rem;
    line-height: 1.2;
}

.cp-wp-tabs__hint {
    font-size: 0.68rem;
    opacity: 0.85;
    padding-right: 2.5rem;
    line-height: 1.3;
}

.cp-wp-tabs__item--wordpress.active { color: #21759b; border-color: rgba(33, 117, 155, 0.4); }
.cp-wp-tabs__item--wordpress.active .cp-wp-tabs__icon {
    background: linear-gradient(145deg, #21759b 0%, #1a5f7a 100%);
}

.cp-wp-tabs__item--backup.active { color: #b45309; border-color: rgba(245, 158, 11, 0.4); }
.cp-wp-tabs__item--backup.active .cp-wp-tabs__icon {
    background: linear-gradient(145deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 3px 10px rgba(245, 158, 11, 0.35);
}

.cp-wp-tabs__item--hosting.active { color: #5b5fcf; border-color: rgba(91, 95, 207, 0.35); }
.cp-wp-tabs__item--hosting.active .cp-wp-tabs__icon {
    background: linear-gradient(145deg, #5b5fcf 0%, #4347a8 100%);
    box-shadow: 0 3px 10px rgba(91, 95, 207, 0.3);
}

.cp-wp-tabs__item--tools.active { color: #0d6efd; border-color: rgba(13, 110, 253, 0.35); }
.cp-wp-tabs__item--tools.active .cp-wp-tabs__icon {
    background: linear-gradient(145deg, #0d6efd 0%, #0a58ca 100%);
}

/* Tab content animation */
.cp-wp-tabs-panel .tab-content {
    padding: 1.35rem 1.5rem 1.5rem;
    min-height: 280px;
}

.cp-wp-tabs-panel .tab-pane {
    animation: cpWpTabIn 0.35s ease;
}

@keyframes cpWpTabIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Inner WP tabs (pills) ── */
.cp-wp-inner-tabs {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.4rem;
    overflow-x: auto;
    padding: 0.35rem;
    margin-bottom: 1.15rem;
    border-radius: 0.75rem;
    background: var(--default-background, #f4f6f9);
    border: 1px solid var(--default-border, #e9ecef);
    scrollbar-width: thin;
}

.cp-wp-inner-tabs__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.85rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    background: transparent;
    color: var(--text-muted, #6c757d);
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.18s ease;
}

.cp-wp-inner-tabs__btn:hover {
    color: var(--default-text-color, #333);
    background: rgba(255, 255, 255, 0.7);
}

.cp-wp-inner-tabs__btn.active {
    color: #21759b;
    background: var(--custom-white, #fff);
    border-color: rgba(33, 117, 155, 0.25);
    box-shadow: 0 2px 8px rgba(33, 117, 155, 0.1);
}

.cp-wp-inner-tabs .tab-pane {
    animation: cpWpTabIn 0.3s ease;
}

/* ── Overview panels ── */
.cp-wp-detail-card {
    border-radius: 0.85rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--default-background, #f9fafb);
    overflow: hidden;
    height: 100%;
}

.cp-wp-detail-card__head {
    padding: 0.85rem 1.1rem;
    font-weight: 700;
    font-size: 0.9rem;
    border-bottom: 1px solid var(--default-border, #e9ecef);
    background: linear-gradient(180deg, rgba(91, 95, 207, 0.04) 0%, transparent 100%);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cp-wp-detail-card__body { padding: 0.5rem 0; }

.cp-wp-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.65rem 1.1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    transition: background 0.15s ease;
}

.cp-wp-detail-row:last-child { border-bottom: 0; }
.cp-wp-detail-row:hover { background: rgba(91, 95, 207, 0.04); }

.cp-wp-detail-row__label {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.cp-wp-detail-row__value {
    font-weight: 600;
    font-size: 0.88rem;
    text-align: left;
}

/* Quick action tiles */
.cp-wp-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.65rem;
}

.cp-wp-action-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.1rem 0.75rem;
    border-radius: 0.85rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    text-decoration: none;
    color: inherit;
    text-align: center;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.cp-wp-action-tile:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: rgba(33, 117, 155, 0.3);
    color: inherit;
}

.cp-wp-action-tile:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.cp-wp-action-tile__icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.cp-wp-action-tile__icon--wp { background: rgba(33, 117, 155, 0.12); color: #21759b; }
.cp-wp-action-tile__icon--ssl { background: rgba(13, 202, 240, 0.15); color: #0aa2c0; }
.cp-wp-action-tile__icon--sync { background: rgba(91, 95, 207, 0.12); color: #5b5fcf; }

.cp-wp-action-tile__label {
    font-size: 0.8rem;
    font-weight: 600;
}

/* Maintenance toggle buttons */
.cp-wp-toggle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.65rem;
}

.cp-wp-toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.75rem 1rem;
    border-radius: 0.65rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    font-size: 0.84rem;
    font-weight: 500;
    text-align: right;
    cursor: pointer;
    transition: all 0.18s ease;
}

.cp-wp-toggle-btn:hover:not(:disabled) {
    border-color: rgba(33, 117, 155, 0.35);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.cp-wp-toggle-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.cp-wp-toggle-btn i { font-size: 1.1rem; opacity: 0.85; }

/* Tables & tools */
.wp-pt-table-wrap {
    max-height: 420px;
    overflow: auto;
    border: 1px solid var(--default-border, #e9ecef);
    border-radius: 0.65rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.03);
}

.wp-pt-table thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    background: var(--default-background, #f9fafb);
    position: sticky;
    top: 0;
    z-index: 1;
}

.cp-tool-card {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1.15rem;
    border-radius: 0.85rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    height: 100%;
    text-decoration: none;
    color: inherit;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.cp-tool-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
    border-color: rgba(91, 95, 207, 0.3);
    color: inherit;
}

.cp-tool-card__icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

/* Stats row hover */
.cp-wp-show-page .coolify-info-widget {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.cp-wp-show-page .coolify-info-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

/* API status badge in management */
.cp-wp-api-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 600;
}

.cp-wp-api-badge--ready {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.cp-wp-api-badge--ready::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22c55e;
    animation: cpWpPulse 1.8s infinite;
}

@keyframes cpWpPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.45; }
}

[data-theme-mode="dark"] .cp-wp-tabs-panel,
[data-theme-mode="dark"] .cp-wp-detail-card,
[data-theme-mode="dark"] .cp-wp-action-tile,
[data-theme-mode="dark"] .cp-tool-card {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme-mode="dark"] .cp-wp-inner-tabs {
    background: rgba(255, 255, 255, 0.04);
}
</style>
