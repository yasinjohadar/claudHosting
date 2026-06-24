<style>
.cf-app-create-hero {
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.14);
}

.cf-app-create-shell {
    border: 0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
    background: var(--custom-white, #fff);
}

.cf-app-create-shell::before {
    content: '';
    display: block;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color, #845ade) 0%, #22c55e 50%, #0ea5e9 100%);
}

.cf-app-create-tabs-wrap {
    padding: 1rem 1rem 0;
    background: linear-gradient(180deg, rgba(var(--primary-rgb, 132, 90, 223), 0.04) 0%, transparent 100%);
    border-bottom: 1px solid var(--default-border, #e9ecef);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.cf-app-create-tabs {
    display: flex;
    gap: 0.5rem;
    min-width: min-content;
    padding-bottom: 1rem;
}

.cf-app-create-tab {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.75rem 1rem;
    min-width: 11.5rem;
    max-width: 14rem;
    border-radius: 0.75rem;
    border: 1px solid var(--default-border, #e2e8f0);
    background: var(--custom-white, #fff);
    color: var(--default-text-color, #1e293b);
    text-decoration: none;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    flex-shrink: 0;
}

.cf-app-create-tab:hover {
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    color: var(--default-text-color, #1e293b);
    transform: translateY(-1px);
}

.cf-app-create-tab.is-active {
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.45);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
    box-shadow: 0 4px 14px rgba(var(--primary-rgb, 132, 90, 223), 0.12);
}

.cf-app-create-tab__icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    background: var(--default-background, #f1f5f9);
    color: var(--text-muted, #64748b);
    transition: background 0.15s ease, color 0.15s ease;
}

.cf-app-create-tab.is-active .cf-app-create-tab__icon {
    background: var(--primary-color, #845ade);
    color: #fff;
}

.cf-app-create-tab--dark.is-active .cf-app-create-tab__icon { background: #24292f; }
.cf-app-create-tab--warning.is-active .cf-app-create-tab__icon { background: #f59e0b; }
.cf-app-create-tab--info.is-active .cf-app-create-tab__icon { background: #0ea5e9; }
.cf-app-create-tab--success.is-active .cf-app-create-tab__icon { background: #22c55e; }
.cf-app-create-tab--secondary.is-active .cf-app-create-tab__icon { background: #64748b; }

.cf-app-create-tab__text {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
}

.cf-app-create-tab__label {
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
}

.cf-app-create-tab__desc {
    font-size: 0.68rem;
    color: var(--text-muted, #64748b);
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cf-app-create-tab__check {
    position: absolute;
    top: 0.4rem;
    left: 0.4rem;
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 50%;
    background: var(--primary-color, #845ade);
    color: #fff;
    font-size: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cf-app-create-form {
    padding: 1.35rem 1.5rem 1.5rem;
}

.cf-app-form-section {
    margin-bottom: 1.5rem;
}

.cf-app-form-section:last-of-type {
    margin-bottom: 0;
}

.cf-app-form-section__title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted, #64748b);
    margin-bottom: 0.85rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--default-border, #e9ecef);
}

.cf-app-form-section__title i {
    font-size: 0.9rem;
    opacity: 0.85;
}

.cf-app-type-panel {
    border-radius: 0.75rem;
    border: 1px dashed rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.03);
    padding: 1.15rem;
    margin-bottom: 1.5rem;
}

.cf-app-type-panel__head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 0.88rem;
    margin-bottom: 0.85rem;
    color: var(--primary-color, #845ade);
}

.cf-app-form-hint {
    font-size: 0.75rem;
    color: var(--text-muted, #64748b);
    margin-top: 0.25rem;
}

.cf-app-create-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.15rem;
    border-top: 1px solid var(--default-border, #e9ecef);
}

.cf-app-create-actions__primary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.cf-app-instant-deploy {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.75rem 1rem;
    border-radius: 0.65rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--default-background, #f8fafc);
    height: 100%;
}

.cf-app-instant-deploy .form-check-input {
    margin-top: 0;
}

.cf-app-instant-deploy__text strong {
    display: block;
    font-size: 0.84rem;
}

.cf-app-instant-deploy__text small {
    color: var(--text-muted, #64748b);
}

@media (max-width: 767.98px) {
    .cf-app-create-form {
        padding: 1rem;
    }

    .cf-app-create-tab {
        min-width: 10rem;
    }
}

[data-theme-mode="dark"] .cf-app-create-shell,
[data-theme-mode="dark"] .cf-app-create-tab {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme-mode="dark"] .cf-app-create-tab.is-active {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.15);
}

[data-theme-mode="dark"] .cf-app-instant-deploy {
    background: rgba(255, 255, 255, 0.04);
}
</style>
