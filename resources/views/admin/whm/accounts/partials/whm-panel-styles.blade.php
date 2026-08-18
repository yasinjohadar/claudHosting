{{-- Layout primitives for the reusable WHM panels (deliverability, resources,
     subscription). Un-prefixed on purpose so the same partials render correctly on
     the admin account page, the admin client/user pages and the client portal.
     @once → emitted a single time even with N accordion items on one page. --}}
@once
<style>
.whm-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 50rem;
    font-size: 0.8rem;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(0, 0, 0, 0.06);
}
.whm-stat-tile {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
    background: var(--custom-white, #fff);
    border: 1px solid rgba(0, 0, 0, 0.06);
    height: 100%;
}
.whm-stat-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.whm-stat-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.15rem;
}
.whm-stat-value {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
}
.whm-section {
    padding: 1.25rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.whm-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.whm-section:first-child {
    padding-top: 0;
}
.whm-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.85rem;
}
.whm-stats-panel {
    padding: 0.25rem 0;
}
.whm-mail-domain-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 0.75rem;
    padding: 1rem 1.15rem;
    margin-bottom: 1rem;
    background: var(--custom-white, #fff);
}
.whm-mail-domain-card:last-child {
    margin-bottom: 0;
}
.whm-mail-check {
    padding: 0.75rem 0;
    border-top: 1px dashed rgba(0, 0, 0, 0.08);
}
.whm-mail-check:first-of-type {
    border-top: none;
    padding-top: 0.25rem;
}
.whm-mail-check:last-child {
    padding-bottom: 0;
}
.whm-mail-check-label {
    font-weight: 700;
    font-size: 0.85rem;
    min-width: 5.5rem;
}
.whm-mail-field {
    margin-bottom: 0.5rem;
}
.whm-mail-field-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.2rem;
}
.whm-mail-value-wrap {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
}
.whm-mail-value {
    flex: 1 1 auto;
    min-width: 0;
    direction: ltr;
    text-align: left;
    unicode-bidi: isolate;
    font-family: var(--bs-font-monospace, ui-monospace, SFMono-Regular, monospace);
    font-size: 0.76rem;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-all;
    overflow-wrap: anywhere;
    max-height: 7.5rem;
    overflow-y: auto;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 0.5rem;
    padding: 0.45rem 0.6rem;
}
[data-theme-mode="dark"] .whm-mail-value,
.dark .whm-mail-value {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.10);
}
[data-theme-mode="dark"] .whm-mail-domain-card,
.dark .whm-mail-domain-card {
    border-color: rgba(255, 255, 255, 0.10);
}
[data-theme-mode="dark"] .whm-mail-check,
.dark .whm-mail-check {
    border-top-color: rgba(255, 255, 255, 0.10);
}

/* ---- subscription panel ------------------------------------------------- */

.whm-sub-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
    gap: 0.75rem;
}

/* A tile that carries state: the coloured edge is the fastest read on the panel. */
.whm-sub-tile {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    border-radius: 0.85rem;
    background: var(--custom-white, #fff);
    border: 1px solid rgba(0, 0, 0, 0.07);
    overflow: hidden;
}

.whm-sub-tile::before {
    content: "";
    position: absolute;
    inset-inline-start: 0;
    inset-block: 0;
    width: 3px;
    background: var(--whm-sub-tone, rgba(0, 0, 0, 0.12));
}

.whm-sub-tile--success { --whm-sub-tone: #22c55e; }
.whm-sub-tile--warning { --whm-sub-tone: #f59e0b; }
.whm-sub-tile--danger  { --whm-sub-tone: #ef4444; }
.whm-sub-tile--secondary { --whm-sub-tone: #94a3b8; }
.whm-sub-tile--primary { --whm-sub-tone: rgb(var(--primary-rgb, 132, 90, 223)); }

.whm-sub-tile__icon {
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 0.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.95rem;
    color: var(--whm-sub-tone, #64748b);
    background: color-mix(in srgb, var(--whm-sub-tone, #94a3b8) 14%, transparent);
}

.whm-sub-tile__body { min-width: 0; }

.whm-sub-tile__label {
    display: block;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #64748b);
    margin-bottom: 0.15rem;
}

.whm-sub-tile__value {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    line-height: 1.25;
    word-break: break-word;
}

.whm-sub-tile__hint {
    display: block;
    font-size: 0.72rem;
    color: var(--text-muted, #64748b);
    margin-top: 0.1rem;
}

/* How much of the paid period is used up. Only rendered when a real start date
   exists, so the bar never implies precision the data does not have. */
.whm-sub-progress {
    margin-top: 0.9rem;
}

.whm-sub-progress__track {
    height: 7px;
    border-radius: 50rem;
    background: rgba(0, 0, 0, 0.07);
    overflow: hidden;
}

.whm-sub-progress__bar {
    height: 100%;
    border-radius: 50rem;
    background: var(--whm-sub-tone, rgb(var(--primary-rgb, 132, 90, 223)));
    transition: width 0.4s ease;
}

.whm-sub-invoice {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.6rem 0.85rem;
    border-radius: 0.65rem;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: rgba(0, 0, 0, 0.015);
}

.whm-sub-invoice + .whm-sub-invoice { margin-top: 0.4rem; }

.whm-sub-invoice__amount {
    margin-inline-start: auto;
    font-weight: 700;
    white-space: nowrap;
}

[data-theme-mode="dark"] .whm-sub-tile,
.dark .whm-sub-tile {
    border-color: rgba(255, 255, 255, 0.10);
}

[data-theme-mode="dark"] .whm-sub-invoice,
.dark .whm-sub-invoice {
    border-color: rgba(255, 255, 255, 0.10);
    background: rgba(255, 255, 255, 0.04);
}

[data-theme-mode="dark"] .whm-sub-progress__track,
.dark .whm-sub-progress__track {
    background: rgba(255, 255, 255, 0.10);
}
</style>
@endonce
