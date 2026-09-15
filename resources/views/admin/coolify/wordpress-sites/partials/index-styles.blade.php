@include('admin.coolify.wordpress-sites.partials.action-menu-styles')
<style>
.wp-sites-table {
    --wp-row-hover: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    font-size: 0.875rem;
}

.wp-sites-table thead th {
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

.wp-sites-table tbody td {
    padding: 0.9rem 1rem;
    vertical-align: middle;
    border-color: var(--default-border, rgba(0, 0, 0, 0.06));
}

.wp-sites-table tbody tr:hover {
    background: var(--wp-row-hover);
}

.wp-sites-table__col-actions {
    width: 1%;
    white-space: nowrap;
}

.wp-site-name {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.wp-site-name__icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: linear-gradient(145deg, #21759b 0%, #1a5f7a 100%);
    color: #fff;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(33, 117, 155, 0.25);
}

.wp-site-name__title {
    font-weight: 600;
    color: var(--default-text-color, #1a1a2e);
    line-height: 1.3;
}

.wp-site-name__slug {
    font-size: 0.75rem;
    color: var(--text-muted, #6c757d);
    font-family: ui-monospace, monospace;
}

.wp-site-url {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wp-site-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 500;
    padding: 0.28rem 0.65rem;
    border-radius: 50rem;
}

.wp-site-status::before {
    content: '';
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    flex-shrink: 0;
}

.wp-site-status--running {
    background: rgba(25, 135, 84, 0.12);
    color: #198754;
}

.wp-site-status--running::before {
    background: #198754;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.25);
}

.wp-site-status--provisioning {
    background: rgba(255, 193, 7, 0.15);
    color: #997404;
}

.wp-site-status--provisioning::before {
    background: #ffc107;
    animation: wp-status-pulse 1.2s ease-in-out infinite;
}

.wp-site-status--failed {
    background: rgba(220, 53, 69, 0.12);
    color: #dc3545;
}

.wp-site-status--failed::before {
    background: #dc3545;
}

.wp-site-status--default {
    background: rgba(108, 117, 125, 0.12);
    color: #6c757d;
}

.wp-site-status--default::before {
    background: #6c757d;
}

@keyframes wp-status-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.45; }
}

.wp-site-client {
    max-width: 10rem;
}

.wp-site-client__link {
    font-weight: 500;
    font-size: 0.82rem;
}

.wp-site-client__empty {
    font-size: 0.82rem;
}

.wp-site-project {
    font-size: 0.8rem;
    max-width: 9rem;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wp-site-project-uuid {
    font-size: 0.72rem;
    padding: 0.15rem 0.4rem;
    border-radius: 0.25rem;
    background: var(--default-background, #f3f4f6);
    cursor: help;
}

.wp-site-date {
    font-size: 0.8rem;
    color: var(--text-muted, #6c757d);
    white-space: nowrap;
}

.wp-site-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    justify-content: flex-end;
}

.asset-client-assign--panel .form-label {
    margin-bottom: 0.35rem;
}

.asset-client-assign--panel .asset-client-select {
    min-width: 100%;
    max-width: 100%;
}

.asset-client-assign--panel .asset-client-save {
    margin-top: 0.5rem;
}

.wp-sites-empty td {
    padding: 3rem 1rem !important;
}

[data-theme-mode="dark"] .wp-sites-table thead th {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme-mode="dark"] .wp-site-project-uuid {
    background: rgba(255, 255, 255, 0.06);
}
</style>
