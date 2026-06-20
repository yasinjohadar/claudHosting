<style>
.coolify-settings-card-link {
    display: block;
    height: 100%;
    text-decoration: none !important;
    color: inherit;
}
.coolify-settings-card {
    height: 100%;
    border: 1px solid var(--default-border, #e2e8f0);
    border-radius: 0.85rem;
    padding: 1.1rem 1rem;
    background: var(--custom-white, #fff);
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    position: relative;
    overflow: hidden;
}
.coolify-settings-card::before {
    content: '';
    position: absolute;
    inset-inline-start: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--csc-accent, rgb(var(--primary-rgb, 132, 90, 223)));
}
.coolify-settings-card-link:hover .coolify-settings-card {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.2);
}
.coolify-settings-card__icon {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    background: var(--csc-soft, rgba(var(--primary-rgb, 132, 90, 223), 0.12));
    color: var(--csc-accent, rgb(var(--primary-rgb, 132, 90, 223)));
}
.coolify-settings-card__body { flex: 1; min-width: 0; }
.coolify-settings-card__title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0 0 0.3rem;
}
.coolify-settings-card__desc {
    font-size: 0.72rem;
    color: var(--text-muted, #64748b);
    margin: 0;
    line-height: 1.45;
}
.coolify-settings-card__arrow {
    opacity: 0.35;
    align-self: center;
}
.coolify-settings-card--primary { --csc-accent: rgb(var(--primary-rgb, 132, 90, 223)); --csc-soft: rgba(var(--primary-rgb, 132, 90, 223), 0.12); }
.coolify-settings-card--success { --csc-accent: #22c55e; --csc-soft: rgba(34, 197, 94, 0.12); }
.coolify-settings-card--info { --csc-accent: #0ea5e9; --csc-soft: rgba(14, 165, 233, 0.12); }
.coolify-settings-card--warning { --csc-accent: #f59e0b; --csc-soft: rgba(245, 158, 11, 0.14); }
.coolify-settings-card--danger { --csc-accent: #ef4444; --csc-soft: rgba(239, 68, 68, 0.12); }
.coolify-settings-card--secondary { --csc-accent: #64748b; --csc-soft: rgba(100, 116, 139, 0.12); }
.coolify-settings-card--teal { --csc-accent: #14b8a6; --csc-soft: rgba(20, 184, 166, 0.12); }
.coolify-settings-card--purple { --csc-accent: #a855f7; --csc-soft: rgba(168, 85, 247, 0.12); }
.coolify-section-layout .coolify-settings-footer {
    position: sticky;
    bottom: 0;
    z-index: 1019;
    background: var(--custom-white, #fff);
    border-top: 1px solid var(--default-border);
    margin: 1.25rem -1.25rem -1.25rem;
    padding: 1rem 1.25rem;
}
</style>
