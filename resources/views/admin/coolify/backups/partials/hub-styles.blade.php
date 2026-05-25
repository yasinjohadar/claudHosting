<style>
.backup-hub-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.12) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.1) 50%, rgba(34, 197, 94, 0.06) 100%);
    border: 1px solid rgba(14, 165, 233, 0.2);
}
.backup-hub-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 600;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border);
}
.backup-hub-pill--ok {
    border-color: rgba(34, 197, 94, 0.35);
    color: #15803d;
    background: rgba(34, 197, 94, 0.08);
}
.backup-hub-pill--warn {
    border-color: rgba(245, 158, 11, 0.35);
    color: #b45309;
    background: rgba(245, 158, 11, 0.08);
}
.backup-hub-tabs {
    border: 0;
    gap: 0.35rem;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scrollbar-width: thin;
}
.backup-hub-tabs .nav-link {
    border: 1px solid var(--default-border);
    border-radius: 2rem !important;
    padding: 0.45rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    white-space: nowrap;
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}
.backup-hub-tabs .nav-link:hover {
    color: var(--default-text-color);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    transform: translateY(-1px);
}
.backup-hub-tabs .nav-link.active {
    background: rgb(var(--primary-rgb, 132, 90, 223));
    border-color: rgb(var(--primary-rgb, 132, 90, 223));
    color: #fff;
    box-shadow: 0 6px 18px rgba(var(--primary-rgb, 132, 90, 223), 0.35);
}
.backup-hub-card {
    position: relative;
    height: 100%;
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    transition: transform 0.28s cubic-bezier(0.34, 1.2, 0.64, 1), box-shadow 0.28s ease, border-color 0.28s ease;
}
.backup-hub-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.1);
    border-color: var(--coolify-accent-border, rgba(132, 90, 223, 0.3));
}
.backup-hub-card--featured {
    border-width: 2px;
    box-shadow: 0 8px 28px rgba(var(--primary-rgb, 132, 90, 223), 0.12);
}
.backup-hub-card-accent {
    height: 4px;
    background: var(--coolify-accent, rgb(var(--primary-rgb, 132, 90, 223)));
}
.backup-hub-card-body {
    padding: 1.25rem 1.3rem 1.15rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    min-height: 220px;
}
.backup-hub-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.backup-hub-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    background: var(--coolify-accent-soft);
    color: var(--coolify-accent);
    border: 1px solid var(--coolify-accent-border);
    flex-shrink: 0;
    transition: transform 0.25s ease;
}
.backup-hub-card:hover .backup-hub-card-icon {
    transform: scale(1.08) rotate(-3deg);
}
.backup-hub-card-title {
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0 0 0.25rem;
    color: var(--default-text-color);
}
.backup-hub-card-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.55;
}
.backup-hub-card-stat {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--coolify-accent);
    line-height: 1;
}
.backup-hub-card-stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted);
}
.backup-hub-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.backup-hub-tag {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 1rem;
    background: var(--coolify-accent-soft);
    color: var(--coolify-accent);
    border: 1px solid var(--coolify-accent-border);
}
.backup-hub-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: auto;
    padding-top: 0.35rem;
}
.backup-hub-flow {
    border-radius: 1rem;
    border: 1px dashed var(--default-border);
    padding: 1rem 1.25rem;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.03);
}
.backup-hub-flow-step {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-size: 0.82rem;
    font-weight: 600;
}
.backup-hub-flow-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    background: rgb(var(--primary-rgb, 132, 90, 223));
    color: #fff;
    flex-shrink: 0;
}
.backup-hub-flow-arrow {
    color: var(--text-muted);
    font-size: 1rem;
    opacity: 0.5;
}
@media (max-width: 767.98px) {
    .backup-hub-flow .d-flex { flex-direction: column; align-items: flex-start !important; }
    .backup-hub-flow-arrow { transform: rotate(90deg); }
}
.backup-hub-hero--database {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.14) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.08) 100%);
    border-color: rgba(14, 165, 233, 0.25);
}
.backup-hub-hero--projects {
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.14) 0%, rgba(14, 165, 233, 0.08) 100%);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.22);
}
.backup-hub-hero--schedules {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.12) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.06) 100%);
    border-color: rgba(34, 197, 94, 0.22);
}
.backup-hub-hero--snapshots {
    background: linear-gradient(135deg, rgba(100, 116, 139, 0.12) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.08) 100%);
    border-color: rgba(100, 116, 139, 0.25);
}
.backup-stat-card {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 1.1rem 1.2rem;
    height: 100%;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.backup-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}
.backup-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
}
.backup-stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-top: 0.25rem;
}
.backup-panel-card {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
}
.backup-panel-card .card-header {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    border-bottom: 1px solid var(--default-border);
    padding: 0.85rem 1.15rem;
}
.backup-panel-card .card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}
.backup-filter-panel {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 1.15rem 1.25rem;
}
.backup-project-card {
    position: relative;
    height: 100%;
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.backup-project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.1);
    border-color: rgba(var(--primary-rgb, 132, 90, 223), 0.3);
}
.backup-project-card-accent {
    height: 3px;
    background: rgb(var(--primary-rgb, 132, 90, 223));
}
.backup-project-card-body {
    padding: 1.15rem 1.2rem;
}
.backup-table-card {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    overflow: hidden;
}
.backup-table-card .table thead th {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    border-bottom: 1px solid var(--default-border);
}
.backup-table-card tbody tr {
    transition: background 0.15s ease;
}
.backup-table-card tbody tr:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
}
.backup-empty-state {
    padding: 3rem 1.5rem;
    text-align: center;
    color: var(--text-muted);
}
.backup-empty-state i {
    font-size: 2.5rem;
    opacity: 0.35;
    display: block;
    margin-bottom: 0.75rem;
}
</style>
