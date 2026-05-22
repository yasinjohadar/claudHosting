<style>
    .client-portal-hero {
        background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08) 0%, rgba(var(--primary-rgb), 0.02) 55%, transparent 100%);
        border: 1px solid rgba(var(--primary-rgb), 0.12);
        border-radius: 0.85rem;
        padding: 1.35rem 1.5rem;
    }
    .client-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.8125rem;
        font-weight: 600;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    [data-theme-mode="dark"] .client-stat-pill {
        background: var(--custom-white);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .client-services-card {
        background: var(--custom-white);
        border: 1px solid var(--default-border);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04);
    }
    .client-services-card__header {
        background: var(--custom-white);
        padding: 0.75rem 1rem 0;
        border-bottom: 1px solid var(--default-border);
    }
    .client-services-tabs {
        border-bottom: 0;
        gap: 0.25rem;
    }
    .client-services-tabs .nav-link {
        border: 0;
        border-radius: 0.5rem 0.5rem 0 0;
        padding: 0.65rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        white-space: nowrap;
        margin-bottom: -1px;
    }
    .client-services-tabs .nav-link:hover {
        color: rgb(var(--primary-rgb));
        background: rgba(var(--primary-rgb), 0.06);
    }
    .client-services-tabs .nav-link.active {
        color: rgb(var(--primary-rgb));
        background: var(--custom-white);
        border-bottom: 2px solid rgb(var(--primary-rgb));
        font-weight: 600;
    }
    .client-services-table thead th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: none;
        color: var(--text-muted);
        background: rgba(var(--primary-rgb), 0.04);
        border-bottom: 1px solid var(--default-border);
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }
    .client-services-table tbody td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--default-border);
        background: var(--custom-white);
    }
    .client-services-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .client-services-table tbody tr:hover td {
        background: rgba(var(--primary-rgb), 0.03);
    }
    .client-services-table .client-empty-state {
        padding: 2.5rem 1rem;
        text-align: center;
        color: var(--text-muted);
        background: var(--custom-white);
    }
    .client-uuid-chip {
        display: inline-block;
        max-width: 11rem;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        font-family: ui-monospace, monospace;
        background: rgba(var(--primary-rgb), 0.06);
        border: 1px solid rgba(var(--primary-rgb), 0.1);
        border-radius: 0.35rem;
        color: var(--text-muted);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .client-empty-state {
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: var(--text-muted);
    }
    .client-invoice-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom-width: 1px;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
    }
    .client-invoice-table tbody td {
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
        vertical-align: middle;
    }
</style>
