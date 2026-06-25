<style>
.cp-show-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(91, 95, 207, 0.12) 0%, rgba(33, 117, 155, 0.06) 50%, rgba(34, 197, 94, 0.05) 100%);
    border: 1px solid rgba(91, 95, 207, 0.16);
}

.cp-show-hero .cp-show-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
}

.cp-show-icon--a { background: linear-gradient(145deg, #5b5fcf 0%, #4347a8 100%); }
.cp-show-icon--b { background: linear-gradient(145deg, #0d6efd 0%, #0a58ca 100%); }
.cp-show-icon--c { background: linear-gradient(145deg, #198754 0%, #146c43 100%); }
.cp-show-icon--d { background: linear-gradient(145deg, #fd7e14 0%, #e8590c 100%); }
.cp-show-icon--e { background: linear-gradient(145deg, #20c997 0%, #17a589 100%); }

.cp-show-domain {
    direction: ltr;
    text-align: right;
    font-size: 0.82rem;
    color: var(--text-muted, #6c757d);
    font-family: ui-monospace, monospace;
}

.cp-show-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border, #e9ecef);
}

.cp-show-pill--active {
    border-color: rgba(34, 197, 94, 0.35);
    color: #16a34a;
}

.cp-show-pill--suspended {
    border-color: rgba(255, 193, 7, 0.4);
    color: #997404;
}

.cp-show-pill--terminated {
    border-color: rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.cp-show-pill--active .cp-show-pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    animation: cp-show-pulse 1.8s infinite;
}

@keyframes cp-show-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.85); }
}

.cp-show-actions .btn {
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.82rem;
}

.cp-show-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.85rem;
}

.cp-show-panel {
    border-radius: 1rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    height: 100%;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.cp-show-panel:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
}

.cp-show-panel__head {
    padding: 0.9rem 1.15rem;
    border-bottom: 1px solid var(--default-border, #e9ecef);
    background: linear-gradient(180deg, rgba(91, 95, 207, 0.04) 0%, transparent 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.cp-show-panel__head--wp {
    background: linear-gradient(180deg, rgba(33, 117, 155, 0.08) 0%, transparent 100%);
}

.cp-show-panel__head--ssl {
    background: linear-gradient(180deg, rgba(34, 197, 94, 0.06) 0%, transparent 100%);
}

.cp-show-panel__title {
    font-weight: 700;
    font-size: 0.95rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cp-show-panel__body {
    padding: 1.15rem;
}

.cp-show-client-display {
    margin-bottom: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    background: var(--default-background, #f9fafb);
    border: 1px solid var(--default-border, #e9ecef);
}

.cp-show-feature {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}

.cp-show-feature__icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.cp-show-feature__icon--wp {
    background: rgba(33, 117, 155, 0.12);
    color: #21759b;
}

.cp-show-feature__icon--ssl-on {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}

.cp-show-feature__icon--ssl-off {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.cp-show-feature__content {
    flex: 1;
    min-width: 12rem;
}

.cp-show-feature__status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.3rem 0.7rem;
    border-radius: 50rem;
    margin-bottom: 0.5rem;
}

.cp-show-feature__status--running { background: rgba(33, 117, 155, 0.12); color: #21759b; }
.cp-show-feature__status--provisioning { background: rgba(255, 193, 7, 0.15); color: #997404; }
.cp-show-feature__status--failed { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.cp-show-feature__status--none { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
.cp-show-feature__status--ssl-on { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
.cp-show-feature__status--ssl-off { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

.cp-show-feature__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.cp-show-btn-wp {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.84rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(145deg, #21759b 0%, #1a5f7a 100%);
    border: none;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(33, 117, 155, 0.3);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.cp-show-btn-wp:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 117, 155, 0.4);
}

.cp-show-credentials {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 0.65rem;
    border: 1px dashed rgba(91, 95, 207, 0.3);
    background: rgba(91, 95, 207, 0.03);
}

.cp-show-credentials__hint {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    margin-top: 0.5rem;
}

.cp-show-install-form .form-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
}

.cp-show-danger {
    border-radius: 0.75rem;
    border: 1px solid rgba(220, 53, 69, 0.25);
    background: rgba(220, 53, 69, 0.04);
    padding: 1rem 1.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.cp-show-danger__text {
    font-size: 0.84rem;
    color: var(--text-muted, #6c757d);
}

.cp-show-danger__text strong {
    color: #dc3545;
}

.cp-show-renew-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.84rem;
    font-weight: 600;
    color: #198754;
    text-decoration: none;
    margin-top: 0.75rem;
    padding: 0.4rem 0;
    transition: color 0.15s ease;
}

.cp-show-renew-link:hover {
    color: #146c43;
}

[data-theme-mode="dark"] .cp-show-panel {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme-mode="dark"] .cp-show-client-display,
[data-theme-mode="dark"] .cp-show-credentials {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
}
</style>
