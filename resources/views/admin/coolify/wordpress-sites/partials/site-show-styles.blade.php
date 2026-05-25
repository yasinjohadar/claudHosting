<style>
.site-files-table-wrap { max-height: 480px; overflow: auto; }
.site-files-monaco { min-height: 320px; }
.site-terminal-xterm { height: 420px; background: #1e1e1e; padding: 4px; }
.site-terminal-commands { max-height: 420px; overflow-y: auto; }
.site-show-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.12) 0%, rgba(13, 110, 253, 0.06) 50%, rgba(34, 197, 94, 0.05) 100%);
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.16);
}
.site-show-hero .site-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border);
}
.site-show-hero .site-status-pill--running {
    border-color: rgba(34, 197, 94, 0.35);
    color: #16a34a;
}
.site-show-hero .site-status-pill:not(.site-status-pill--running) {
    color: var(--default-text-color);
}
.site-show-hero .site-status-pill--running .site-pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    animation: coolify-pulse 1.8s infinite;
}
.site-show-hero .site-url-chip {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 0.5rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border);
    font-size: 0.8rem;
    max-width: 100%;
}
.site-show-hero .site-url-chip a {
    word-break: break-all;
}
.site-show-hero .site-url-chip--pending {
    border-color: rgba(245, 158, 11, 0.35);
    background: rgba(245, 158, 11, 0.06);
}
.site-show-hero .site-url-chip--coolify {
    border-color: rgba(34, 197, 94, 0.35);
    background: rgba(34, 197, 94, 0.06);
}
.site-show-actions .btn {
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.82rem;
}
.site-show-tabs-panel {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.site-show-tabs-panel .site-show-tabs-head {
    padding: 0.75rem 1rem 0;
    background: linear-gradient(180deg, rgba(var(--primary-rgb, 132, 90, 223), 0.04) 0%, transparent 100%);
    border-bottom: 1px solid var(--default-border);
}
.site-show-tabs .nav-link {
    white-space: nowrap;
    padding: 0.7rem 1.1rem;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text-muted);
    border: 0;
    border-bottom: 3px solid transparent;
    border-radius: 0.5rem 0.5rem 0 0;
    margin-bottom: -1px;
    transition: color 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}
.site-show-tabs .nav-link:hover {
    color: var(--primary);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.06);
}
.site-show-tabs .nav-link.active {
    color: rgb(var(--primary-rgb, 132, 90, 223));
    border-bottom-color: rgb(var(--primary-rgb, 132, 90, 223));
    background: var(--custom-white, #fff);
}
.site-show-tabs-panel .tab-content {
    padding: 1.25rem 1.35rem 1.35rem;
}
.site-show-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
    margin-bottom: 0.85rem;
    margin-top: 0.15rem;
}
.site-show-panel-table {
    border-radius: 0.75rem;
    border: 1px solid var(--default-border);
    overflow: hidden;
    background: var(--custom-white, #fff);
}
.site-show-panel-table .coolify-widget-accent {
    position: relative;
    height: 3px;
}
.site-show-panel-table .table {
    margin-bottom: 0;
}
.site-show-panel-table thead th {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    border-bottom: 1px solid var(--default-border);
}
.site-show-log-pre {
    padding: 1rem 1.1rem;
    font-size: 0.75rem;
    max-height: 280px;
    overflow: auto;
    white-space: pre-wrap;
    margin: 0;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.03);
    border: 1px solid var(--default-border);
    border-radius: 0 0 0.85rem 0.85rem;
    color: var(--default-text-color);
}
.site-show-log-card {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
}
.site-show-log-card > summary {
    padding: 0.85rem 1.1rem;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    list-style: none;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    border-bottom: 1px solid transparent;
}
.site-show-log-card[open] > summary {
    border-bottom-color: var(--default-border);
}
.site-show-log-card > summary::-webkit-details-marker { display: none; }
.site-wp-management .wp-inner-tabs {
    border-bottom: 1px solid var(--default-border);
    padding-bottom: 0;
}
.site-wp-management .wp-inner-tabs .nav-link {
    font-size: 0.82rem;
    font-weight: 600;
    border-bottom: 2px solid transparent;
}
.site-wp-management .wp-inner-tabs .nav-link.active {
    border-bottom-color: rgb(var(--primary-rgb, 132, 90, 223));
    color: rgb(var(--primary-rgb, 132, 90, 223));
}
.site-wp-management pre.bg-light,
.site-wp-management .bg-light.rounded {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04) !important;
    border: 1px solid var(--default-border);
}
/* بطاقات ملء العرض داخل التبويبات */
.site-show-stats .coolify-info-widget,
.site-show-tabs-panel .tab-pane .coolify-info-widget {
    width: 100%;
}
.site-show-tab-grid > [class*="col-"] {
    min-width: 0;
}
.site-show-env-table code {
    font-size: 0.8rem;
}
.site-show-env-table td {
    vertical-align: middle;
}
.site-show-env-table .env-val {
    direction: ltr;
    text-align: left;
    word-break: break-all;
    font-family: ui-monospace, monospace;
    font-size: 0.78rem;
}
.site-provision-card {
    border-radius: 1rem;
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.2);
    background: linear-gradient(180deg, rgba(var(--primary-rgb, 132, 90, 223), 0.06) 0%, var(--custom-white, #fff) 40%);
    padding: 1.15rem 1.25rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
.site-provision-card__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.site-provision-queue-badge {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.35rem 0.65rem;
    border-radius: 2rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    max-width: 20rem;
    line-height: 1.35;
}
.site-provision-queue-badge--working { border-color: rgba(34, 197, 94, 0.4); color: #16a34a; }
.site-provision-queue-badge--waiting_worker { border-color: rgba(245, 158, 11, 0.45); color: #b45309; }
.site-provision-queue-badge--stalled,
.site-provision-queue-badge--failed_job { border-color: rgba(239, 68, 68, 0.4); color: #dc2626; }
.site-provision-queue-badge--sync { border-color: rgba(59, 130, 246, 0.35); color: #2563eb; }
.site-provision-progress { height: 0.55rem; border-radius: 1rem; }
.site-provision-steps { display: grid; gap: 0.35rem; }
@media (min-width: 768px) {
    .site-provision-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.site-provision-step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.82rem;
    color: var(--text-muted);
}
.site-provision-step__icon {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 50%;
    background: var(--default-border);
    flex-shrink: 0;
}
.site-provision-step--done { color: #16a34a; }
.site-provision-step--done .site-provision-step__icon { background: #22c55e; }
.site-provision-step--active { color: rgb(var(--primary-rgb, 132, 90, 223)); font-weight: 700; }
.site-provision-step--active .site-provision-step__icon {
    background: rgb(var(--primary-rgb, 132, 90, 223));
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 132, 90, 223), 0.2);
    animation: coolify-pulse 1.5s infinite;
}
.site-provision-step--failed { color: #dc2626; }
.site-provision-step--failed .site-provision-step__icon { background: #ef4444; }
.site-provision-log-pre {
    font-size: 0.72rem;
    max-height: 160px;
    overflow: auto;
    white-space: pre-wrap;
    margin: 0;
    padding: 0.65rem 0.75rem;
    background: rgba(15, 23, 42, 0.04);
    border: 1px solid var(--default-border);
    border-radius: 0.5rem;
}
.site-provision-log-wrap { min-height: 4rem; }
.wp-pt-table-wrap { border: 1px solid var(--default-border); border-radius: 0.5rem; overflow: hidden; }
.wp-pt-table thead th {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.05);
}
.wp-pt-job-output {
    font-size: 0.72rem;
    max-height: 140px;
    overflow: auto;
    white-space: pre-wrap;
    padding: 0.65rem 0.75rem;
    background: rgba(15, 23, 42, 0.04);
    border: 1px solid var(--default-border);
    border-radius: 0.5rem;
}
.wp-pt-row-busy { opacity: 0.55; pointer-events: none; }
.wp-pass-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.wp-pass-suggestion {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.72rem;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}
#wpPassResult .wp-pass-result-label {
    margin-bottom: 0.35rem;
}
.wp-directory-type-tabs .nav-link {
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 2rem;
    padding: 0.35rem 0.9rem;
}
.wp-directory-results {
    min-height: 4rem;
}
.wp-directory-card {
    border: 1px solid var(--default-border);
    border-radius: 0.75rem;
    padding: 0.85rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: var(--custom-white, #fff);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.wp-directory-card:hover {
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}
.wp-directory-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    object-fit: cover;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
}
.wp-directory-card-title {
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 0.25rem;
}
.wp-directory-card-desc {
    font-size: 0.72rem;
    color: var(--text-muted);
    flex-grow: 1;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.wp-directory-card-meta {
    font-size: 0.7rem;
    color: var(--text-muted);
}
.wp-directory-card-actions {
    margin-top: auto;
    padding-top: 0.5rem;
}
.wp-directory-card--busy {
    opacity: 0.6;
    pointer-events: none;
}
</style>
