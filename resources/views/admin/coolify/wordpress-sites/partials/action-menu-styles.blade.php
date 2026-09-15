<style>
.wp-site-actions__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.35rem 0.65rem;
    border-radius: 0.4rem;
    font-size: 0.8rem;
    font-weight: 500;
    line-height: 1.2;
    border: 1px solid transparent;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.wp-site-actions__btn--view {
    color: var(--primary-color, #845ade);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.2);
}

.wp-site-actions__btn--view:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.16);
    color: var(--primary-color, #845ade);
}

.wp-site-actions__btn--external {
    color: var(--text-muted, #6c757d);
    background: var(--custom-white, #fff);
    border-color: var(--default-border, #e9ecef);
}

.wp-site-actions__btn--external:hover {
    background: var(--default-background, #f8f9fa);
    color: var(--default-text-color, #333);
}

.wp-site-actions__btn--manage {
    color: var(--default-text-color, #333);
    background: var(--custom-white, #fff);
    border-color: var(--default-border, #dee2e6);
    padding-inline: 0.55rem 0.5rem;
}

.wp-site-actions__btn--manage:hover,
.wp-site-actions__btn--manage.show {
    background: var(--default-background, #f8f9fa);
    border-color: var(--primary-color, #845ade);
    color: var(--primary-color, #845ade);
}

.wp-site-actions__btn--manage::after {
    margin-inline-start: 0.35rem;
    vertical-align: 0.15em;
}

.wp-site-actions-menu {
    min-width: 15.5rem;
    padding: 0.35rem 0;
    border: 1px solid var(--default-border, #e9ecef);
    border-radius: 0.5rem;
}

.wp-site-actions-menu .dropdown-header {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #6c757d);
    padding: 0.5rem 1rem 0.35rem;
}

.wp-site-actions-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 1rem;
    font-size: 0.84rem;
}

.wp-site-actions-menu .dropdown-item i {
    width: 1.1rem;
    text-align: center;
    opacity: 0.85;
}

.wp-site-actions-menu .dropdown-item:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
}

.wp-site-actions-menu__panel {
    padding: 0.65rem 1rem 0.85rem;
    border-top: 1px solid var(--default-border, #e9ecef);
    margin-top: 0.25rem;
}

.wp-site-actions-menu__panel form {
    margin-bottom: 0.4rem;
}

.wp-site-actions-menu__panel form:last-child {
    margin-bottom: 0;
}

.wp-site-actions-menu__panel .btn {
    width: 100%;
    justify-content: center;
}

[data-theme-mode="dark"] .wp-site-actions__btn--external,
[data-theme-mode="dark"] .wp-site-actions__btn--manage {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.1);
}
</style>
