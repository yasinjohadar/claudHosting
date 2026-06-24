<style>
.cf-project-show-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.12) 0%, rgba(13, 110, 253, 0.06) 50%, rgba(34, 197, 94, 0.05) 100%);
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.16);
}

.cf-project-show-hero .cf-project-show-icon {
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

.cf-project-show-hero .cf-project-show-icon--a { background: linear-gradient(145deg, #845ade 0%, #6b3fc9 100%); }
.cf-project-show-hero .cf-project-show-icon--b { background: linear-gradient(145deg, #0d6efd 0%, #0a58ca 100%); }
.cf-project-show-hero .cf-project-show-icon--c { background: linear-gradient(145deg, #198754 0%, #146c43 100%); }
.cf-project-show-hero .cf-project-show-icon--d { background: linear-gradient(145deg, #fd7e14 0%, #e8590c 100%); }
.cf-project-show-hero .cf-project-show-icon--e { background: linear-gradient(145deg, #20c997 0%, #17a589 100%); }

.cf-project-show-pill {
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

.cf-project-show-pill--active {
    border-color: rgba(34, 197, 94, 0.35);
    color: #16a34a;
}

.cf-project-show-pill--empty {
    border-color: rgba(108, 117, 125, 0.25);
    color: #6c757d;
}

.cf-project-show-pill--active .cf-project-pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    animation: coolify-pulse 1.8s infinite;
}

.cf-project-show-actions .btn {
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.82rem;
}

.cf-project-show-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.85rem;
}

.cf-project-show-panel {
    border-radius: 1rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    height: 100%;
}

.cf-project-show-panel__head {
    padding: 0.9rem 1.15rem;
    border-bottom: 1px solid var(--default-border, #e9ecef);
    background: linear-gradient(180deg, rgba(var(--primary-rgb, 132, 90, 223), 0.04) 0%, transparent 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.cf-project-show-panel__title {
    font-weight: 700;
    font-size: 0.95rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cf-project-show-panel__body {
    padding: 1.15rem;
}

.cf-project-show-panel__body--flush {
    padding: 0;
}

.cf-project-env-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.cf-project-env-chip {
    display: inline-flex;
    flex-direction: column;
    gap: 0.2rem;
    padding: 0.75rem 1rem;
    border-radius: 0.65rem;
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.2);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.05);
    color: var(--default-text-color, #1a1a2e);
    text-decoration: none;
    min-width: 10rem;
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.cf-project-env-chip:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    color: var(--primary-color, #845ade);
    transform: translateY(-1px);
}

.cf-project-env-chip__name {
    font-weight: 700;
    font-size: 0.88rem;
}

.cf-project-env-chip__uuid {
    font-size: 0.68rem;
    color: var(--text-muted, #6c757d);
    font-family: ui-monospace, monospace;
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cf-project-client-panel .asset-client-assign--panel .form-label {
    margin-bottom: 0.35rem;
}

.cf-project-client-panel .cf-project-client-display {
    margin-bottom: 0.75rem;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    background: var(--default-background, #f9fafb);
    border: 1px solid var(--default-border, #e9ecef);
}

.cf-project-resources-panel .table thead th {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    background: var(--default-background, #f9fafb);
}

.cf-project-snapshots-table thead th {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    background: var(--default-background, #f9fafb);
}

.cf-project-api-details summary {
    cursor: pointer;
    list-style: none;
    padding: 0.9rem 1.15rem;
    font-weight: 700;
    background: linear-gradient(180deg, rgba(var(--primary-rgb, 132, 90, 223), 0.04) 0%, transparent 100%);
    border-bottom: 1px solid var(--default-border, #e9ecef);
}

.cf-project-api-details summary::-webkit-details-marker {
    display: none;
}

.cf-project-api-details summary::after {
    content: '▼';
    float: left;
    font-size: 0.7rem;
    color: var(--text-muted, #6c757d);
    transition: transform 0.2s ease;
}

.cf-project-api-details[open] summary::after {
    transform: rotate(180deg);
}

.cf-project-block-alert {
    border-radius: 0.65rem;
    border: 1px solid rgba(245, 158, 11, 0.3);
    background: rgba(245, 158, 11, 0.08);
    padding: 0.85rem 1rem;
    font-size: 0.84rem;
    white-space: pre-wrap;
}

[data-theme-mode="dark"] .cf-project-show-panel,
[data-theme-mode="dark"] .cf-project-api-details {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme-mode="dark"] .cf-project-client-panel .cf-project-client-display {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
}
</style>
