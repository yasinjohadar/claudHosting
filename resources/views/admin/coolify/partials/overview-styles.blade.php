<style>
.coolify-resource-page {
    padding-top: 0.25rem;
}
.coolify-dash-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.14) 0%, rgba(13, 110, 253, 0.08) 45%, rgba(25, 135, 84, 0.06) 100%);
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.18);
}
.coolify-api-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.85rem;
    border-radius: 2rem;
    font-size: 0.8rem;
    font-weight: 600;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border);
}
.coolify-api-pill--on .coolify-pulse {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5);
    animation: coolify-pulse 1.8s infinite;
}
@keyframes coolify-pulse {
    0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.45); }
    70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
.coolify-widget-link {
    display: block;
    height: 100%;
    text-decoration: none !important;
    color: inherit;
    border-radius: 1rem;
    outline: none;
}
.coolify-widget {
    position: relative;
    height: 100%;
    border-radius: 1rem !important;
    border: 1px solid var(--default-border) !important;
    overflow: hidden;
    transition: transform 0.28s cubic-bezier(0.34, 1.2, 0.64, 1), box-shadow 0.28s ease, border-color 0.28s ease;
    background: var(--custom-white, #fff);
}
.coolify-widget-link:hover .coolify-widget,
.coolify-widget-link:focus-visible .coolify-widget {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.1);
    border-color: var(--coolify-accent-border, rgba(132, 90, 223, 0.35)) !important;
}
.coolify-widget-accent {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: var(--coolify-accent, rgb(var(--primary-rgb, 132, 90, 223)));
    opacity: 0.85;
}
.coolify-widget-body {
    padding: 1.15rem 1.2rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    min-height: 118px;
}
.coolify-widget-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.coolify-widget-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: var(--coolify-accent-soft);
    color: var(--coolify-accent);
    border: 1px solid var(--coolify-accent-border);
    transition: transform 0.28s ease;
}
.coolify-widget-link:hover .coolify-widget-icon {
    transform: scale(1.06);
}
.coolify-widget-count {
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
    color: var(--default-text-color);
}
.coolify-widget-meta-html {
    font-size: 0.92rem;
    line-height: 1.45;
    color: var(--default-text-color);
}
.coolify-widget-label {
    font-size: 0.92rem;
    font-weight: 700;
    margin: 0;
    color: var(--default-text-color);
}
.coolify-widget-desc {
    font-size: 0.76rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.4;
}
.coolify-widget-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 0.35rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--coolify-accent);
    opacity: 0;
    transform: translateX(6px);
    transition: opacity 0.22s ease, transform 0.22s ease;
}
[dir="rtl"] .coolify-widget-foot { transform: translateX(-6px); }
.coolify-widget-link:hover .coolify-widget-foot {
    opacity: 1;
    transform: translateX(0);
}
.coolify-panel-widget .coolify-widget-body { min-height: 100px; }
.coolify-feed-item {
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--default-border);
    transition: background 0.15s ease;
}
.coolify-feed-item:last-child { border-bottom: 0; }
.coolify-feed-item:hover { background: rgba(var(--primary-rgb, 132, 90, 223), 0.04); }
.coolify-metric-bar {
    height: 6px;
    border-radius: 3px;
    background: rgba(0,0,0,0.06);
    overflow: hidden;
}
.coolify-metric-bar > span {
    display: block;
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}
.coolify-server-card {
    border: 1px solid var(--default-border);
    border-radius: 0.85rem;
    padding: 0.9rem 1rem;
    background: var(--custom-white, #fff);
}
.coolify-accent-primary { --coolify-accent: rgb(var(--primary-rgb, 132, 90, 223)); --coolify-accent-soft: rgba(var(--primary-rgb, 132, 90, 223), 0.1); --coolify-accent-border: rgba(var(--primary-rgb, 132, 90, 223), 0.2); }
.coolify-accent-info { --coolify-accent: #0ea5e9; --coolify-accent-soft: rgba(14, 165, 233, 0.12); --coolify-accent-border: rgba(14, 165, 233, 0.25); }
.coolify-accent-success { --coolify-accent: #22c55e; --coolify-accent-soft: rgba(34, 197, 94, 0.12); --coolify-accent-border: rgba(34, 197, 94, 0.25); }
.coolify-accent-warning { --coolify-accent: #f59e0b; --coolify-accent-soft: rgba(245, 158, 11, 0.12); --coolify-accent-border: rgba(245, 158, 11, 0.25); }
.coolify-accent-danger { --coolify-accent: #ef4444; --coolify-accent-soft: rgba(239, 68, 68, 0.12); --coolify-accent-border: rgba(239, 68, 68, 0.25); }
.coolify-accent-secondary { --coolify-accent: #64748b; --coolify-accent-soft: rgba(100, 116, 139, 0.12); --coolify-accent-border: rgba(100, 116, 139, 0.22); }

/* بطاقات معلومات (بدون رابط) */
.coolify-info-widget {
    position: relative;
    height: 100%;
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    transition: transform 0.28s cubic-bezier(0.34, 1.2, 0.64, 1), box-shadow 0.28s ease, border-color 0.28s ease;
}
.coolify-info-widget:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    border-color: var(--coolify-accent-border, rgba(132, 90, 223, 0.3));
}
.coolify-info-widget .coolify-widget-body { min-height: 130px; }
.coolify-info-rows {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    margin-top: 0.15rem;
}
.coolify-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.82rem;
}
.coolify-info-row-label {
    color: var(--text-muted);
    font-weight: 600;
    flex-shrink: 0;
}
.coolify-info-row-value {
    text-align: left;
    font-weight: 600;
    color: var(--default-text-color);
    word-break: break-all;
}
[dir="rtl"] .coolify-info-row-value { text-align: left; }
.coolify-info-row-value.mono {
    font-family: ui-monospace, monospace;
    font-size: 0.78rem;
    direction: ltr;
}
.coolify-info-widget .coolify-widget-count {
    font-size: 1rem;
    font-weight: 700;
    word-break: break-all;
    line-height: 1.35;
}
.coolify-copy-btn {
    border: 0;
    background: var(--coolify-accent-soft);
    color: var(--coolify-accent);
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
}
.coolify-copy-btn:hover { transform: scale(1.05); }
.coolify-reach-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.78rem;
    font-weight: 700;
}
.coolify-reach-pill .coolify-pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    animation: coolify-pulse 1.8s infinite;
}
</style>

