<style>
.cp-websites-table {
    --cp-row-hover: rgba(91, 95, 207, 0.05);
    font-size: 0.875rem;
}

.cp-websites-table thead th {
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

.cp-websites-table tbody td {
    padding: 0.9rem 1rem;
    vertical-align: middle;
    border-color: var(--default-border, rgba(0, 0, 0, 0.06));
}

.cp-websites-table tbody tr:hover {
    background: var(--cp-row-hover);
}

.cp-websites-table__col-actions {
    width: 1%;
    white-space: nowrap;
}

.cp-website-kpi {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem 1.15rem;
    border-radius: 0.65rem;
    border: 1px solid var(--default-border, #e9ecef);
    background: var(--custom-white, #fff);
    height: 100%;
    transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.cp-website-kpi:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    border-color: rgba(91, 95, 207, 0.25);
    transform: translateY(-1px);
}

.cp-website-kpi__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.cp-website-kpi__icon--total { background: rgba(91, 95, 207, 0.12); color: #5b5fcf; }
.cp-website-kpi__icon--active { background: rgba(25, 135, 84, 0.12); color: #198754; }
.cp-website-kpi__icon--wp { background: rgba(33, 117, 155, 0.12); color: #21759b; }
.cp-website-kpi__icon--clients { background: rgba(13, 202, 240, 0.15); color: #0aa2c0; }

.cp-website-kpi__value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
}

.cp-website-kpi__label {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    margin-top: 0.15rem;
}

.cp-website-domain {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.cp-website-domain__icon {
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

.cp-website-domain__icon--a { background: linear-gradient(145deg, #5b5fcf 0%, #4347a8 100%); }
.cp-website-domain__icon--b { background: linear-gradient(145deg, #0d6efd 0%, #0a58ca 100%); }
.cp-website-domain__icon--c { background: linear-gradient(145deg, #198754 0%, #146c43 100%); }
.cp-website-domain__icon--d { background: linear-gradient(145deg, #fd7e14 0%, #e8590c 100%); }
.cp-website-domain__icon--e { background: linear-gradient(145deg, #20c997 0%, #17a589 100%); }

.cp-website-domain__title {
    font-weight: 600;
    line-height: 1.3;
    direction: ltr;
    text-align: right;
}

.cp-website-domain__meta {
    font-size: 0.72rem;
    color: var(--text-muted, #6c757d);
}

.cp-website-wp-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.6rem;
    border-radius: 50rem;
}

.cp-website-wp-badge--running { background: rgba(33, 117, 155, 0.12); color: #21759b; }
.cp-website-wp-badge--provisioning { background: rgba(255, 193, 7, 0.15); color: #997404; }
.cp-website-wp-badge--failed { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.cp-website-wp-badge--none { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

.cp-website-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    justify-content: flex-end;
}

.cp-website-actions__btn {
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
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.cp-website-actions__btn--view {
    color: #5b5fcf;
    background: rgba(91, 95, 207, 0.08);
    border-color: rgba(91, 95, 207, 0.2);
}

.cp-website-actions__btn--view:hover { background: rgba(91, 95, 207, 0.16); color: #5b5fcf; }

.cp-website-actions__btn--wp {
    color: #21759b;
    background: rgba(33, 117, 155, 0.1);
    border-color: rgba(33, 117, 155, 0.25);
}

.cp-website-actions__btn--wp:hover { background: rgba(33, 117, 155, 0.18); color: #1a5f7a; }

.cp-website-actions__btn--wp-setup {
    color: #997404;
    background: rgba(255, 193, 7, 0.12);
    border-color: rgba(255, 193, 7, 0.35);
}

.cp-website-actions__btn--wp-setup:hover { background: rgba(255, 193, 7, 0.22); color: #856404; }

.cp-website-actions__btn--manage-wp {
    color: #5b5fcf;
    background: rgba(91, 95, 207, 0.1);
    border-color: rgba(91, 95, 207, 0.25);
}

.cp-website-actions__btn--manage-wp:hover { background: rgba(91, 95, 207, 0.18); color: #4347a8; }

.cp-website-actions__btn--external {
    color: var(--default-text-color, #333);
    background: var(--custom-white, #fff);
    border-color: var(--default-border, #dee2e6);
    padding-inline: 0.5rem;
}

.cp-website-actions__btn--external:hover {
    border-color: #198754;
    color: #198754;
}

.cp-website-actions__btn--manage {
    color: var(--default-text-color, #333);
    background: var(--custom-white, #fff);
    border-color: var(--default-border, #dee2e6);
    padding-inline: 0.55rem 0.5rem;
}

.cp-website-actions__btn--manage:hover,
.cp-website-actions__btn--manage.show {
    background: var(--default-background, #f8f9fa);
    border-color: #5b5fcf;
    color: #5b5fcf;
}

.cp-website-actions-menu {
    min-width: 14rem;
    padding: 0.35rem 0;
    border-radius: 0.5rem;
}

.cp-website-actions-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 1rem;
    font-size: 0.84rem;
}

.cp-website-filter {
    border: 1px solid var(--default-border, #e9ecef);
    border-radius: 0.65rem;
}

.cp-websites-empty td { padding: 3.5rem 1rem !important; }

.cp-websites-empty__icon {
    width: 3.5rem;
    height: 3.5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(91, 95, 207, 0.1);
    color: #5b5fcf;
    font-size: 1.5rem;
}

.cp-websites-footnote {
    font-size: 0.78rem;
    color: var(--text-muted, #6c757d);
    padding: 0.85rem 1rem;
    border-top: 1px solid var(--default-border, #e9ecef);
    background: var(--default-background, #f9fafb);
}
</style>
