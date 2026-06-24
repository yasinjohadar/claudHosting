<style>
.cf-projects-table {
    --cf-row-hover: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    font-size: 0.875rem;
}

.cf-projects-table thead th {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    border-bottom-width: 1px;
    padding: 0.85rem 1rem;
    white-space: nowrap;
    background: var(--default-background, #f9fafb);
}

.cf-projects-table tbody td {
    padding: 0.9rem 1rem;
    vertical-align: middle;
    border-color: var(--default-border, rgba(0, 0, 0, 0.06));
}

.cf-projects-table tbody tr:hover {
    background: var(--cf-row-hover);
}

.cf-projects-table__col-actions {
    width: 1%;
    white-space: nowrap;
}

.cf-project-kpi {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem 1.15rem;
    border-radius: 0.65rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    height: 100%;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.cf-project-kpi:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.2);
}

.cf-project-kpi__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.cf-project-kpi__icon--total {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    color: var(--primary-color, #845ade);
}

.cf-project-kpi__icon--active {
    background: rgba(25, 135, 84, 0.12);
    color: #198754;
}

.cf-project-kpi__icon--empty {
    background: rgba(108, 117, 125, 0.12);
    color: #6c757d;
}

.cf-project-kpi__icon--clients {
    background: rgba(13, 202, 240, 0.15);
    color: #0aa2c0;
}

.cf-project-kpi__value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--default-text-color, #1a1a2e);
}

.cf-project-kpi__label {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    margin-top: 0.15rem;
}

.cf-project-name {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.cf-project-name__icon {
    flex-shrink: 0;
    width: 2.35rem;
    height: 2.35rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.55rem;
    color: #fff;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.cf-project-name__icon--a { background: linear-gradient(145deg, #845ade 0%, #6b3fc9 100%); }
.cf-project-name__icon--b { background: linear-gradient(145deg, #0d6efd 0%, #0a58ca 100%); }
.cf-project-name__icon--c { background: linear-gradient(145deg, #198754 0%, #146c43 100%); }
.cf-project-name__icon--d { background: linear-gradient(145deg, #fd7e14 0%, #e8590c 100%); }
.cf-project-name__icon--e { background: linear-gradient(145deg, #20c997 0%, #17a589 100%); }

.cf-project-name__title {
    font-weight: 600;
    color: var(--default-text-color, #1a1a2e);
    line-height: 1.3;
}

.cf-project-name__uuid {
    font-size: 0.72rem;
    color: var(--text-muted, #6c757d);
    font-family: ui-monospace, monospace;
    max-width: 14rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cf-project-resources {
    display: inline-flex;
    flex-direction: column;
    gap: 0.2rem;
}

.cf-project-resources__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.78rem;
    font-weight: 500;
    padding: 0.28rem 0.65rem;
    border-radius: 50rem;
    width: fit-content;
}

.cf-project-resources__badge--active {
    background: rgba(25, 135, 84, 0.12);
    color: #198754;
}

.cf-project-resources__badge--empty {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.cf-project-resources__badge--error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.cf-project-resources__summary {
    font-size: 0.72rem;
    color: var(--text-muted, #6c757d);
    max-width: 11rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cf-project-resources__link {
    text-decoration: none;
    color: inherit;
}

.cf-project-resources__link:hover {
    color: #198754;
}

.cf-project-client__link {
    font-weight: 500;
    font-size: 0.82rem;
    text-decoration: none;
}

.cf-project-client__empty {
    font-size: 0.82rem;
    color: var(--text-muted, #6c757d);
}

.cf-project-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    justify-content: flex-end;
}

.cf-project-actions__btn {
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
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.cf-project-actions__btn--view {
    color: var(--primary-color, #845ade);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.2);
}

.cf-project-actions__btn--view:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.16);
    color: var(--primary-color, #845ade);
}

.cf-project-actions__btn--manage {
    color: var(--default-text-color, #333);
    background: var(--custom-white, #fff);
    border-color: var(--default-border, #dee2e6);
    padding-inline: 0.55rem 0.5rem;
}

.cf-project-actions__btn--manage:hover,
.cf-project-actions__btn--manage.show {
    background: var(--default-background, #f8f9fa);
    border-color: var(--primary-color, #845ade);
    color: var(--primary-color, #845ade);
}

.cf-project-actions-menu {
    min-width: 15.5rem;
    padding: 0.35rem 0;
    border: 1px solid var(--default-border, #e9ecef);
    border-radius: 0.5rem;
}

.cf-project-actions-menu .dropdown-header {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #6c757d);
    padding: 0.5rem 1rem 0.35rem;
}

.cf-project-actions-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 1rem;
    font-size: 0.84rem;
}

.cf-project-actions-menu .dropdown-item i {
    width: 1.1rem;
    text-align: center;
    opacity: 0.85;
}

.cf-project-actions-menu .dropdown-item:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
}

.cf-project-actions-menu .dropdown-item--danger {
    color: #dc3545;
}

.cf-project-actions-menu .dropdown-item--danger:hover {
    background: rgba(220, 53, 69, 0.08);
    color: #dc3545;
}

.cf-project-actions-menu__panel {
    padding: 0.65rem 1rem 0.85rem;
    border-top: 1px solid var(--default-border, #e9ecef);
    margin-top: 0.25rem;
}

.cf-projects-filter {
    border: 1px solid var(--default-border, #e9ecef);
    border-radius: 0.65rem;
    background: var(--custom-white, #fff);
}

.cf-projects-filter .form-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.35rem;
}

.cf-projects-empty td {
    padding: 3.5rem 1rem !important;
}

.cf-projects-empty__icon {
    width: 3.5rem;
    height: 3.5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
    color: var(--primary-color, #845ade);
    font-size: 1.5rem;
}

.cf-projects-footnote {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    padding: 0.85rem 1rem;
    border-top: 1px solid var(--default-border, #e9ecef);
    background: var(--default-background, #f9fafb);
}

[data-theme-mode="dark"] .cf-projects-table thead th {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme-mode="dark"] .cf-project-kpi,
[data-theme-mode="dark"] .cf-projects-filter {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.08);
}

[data-theme-mode="dark"] .cf-project-actions__btn--manage {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode="dark"] .cf-projects-footnote {
    background: rgba(255, 255, 255, 0.02);
}
</style>
