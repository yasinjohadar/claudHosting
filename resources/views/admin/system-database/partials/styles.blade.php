<style>
.sysdb-hero {
    border-radius: 1rem;
    padding: 1.35rem 1.5rem;
    margin-top: 1.5rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.1) 55%, rgba(14, 165, 233, 0.08) 100%);
    border: 1px solid rgba(34, 197, 94, 0.22);
}
.sysdb-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.78rem;
    font-weight: 600;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border);
}
.sysdb-stat-card {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 1.1rem 1.2rem;
    height: 100%;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}
.sysdb-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}
.sysdb-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
}
.sysdb-stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-top: 0.25rem;
}
.sysdb-size-bar {
    height: 6px;
    border-radius: 3px;
    background: rgba(0, 0, 0, 0.06);
    overflow: hidden;
    min-width: 80px;
}
.sysdb-size-bar > span {
    display: block;
    height: 100%;
    border-radius: 3px;
    background: rgb(var(--primary-rgb, 132, 90, 223));
    transition: width 0.4s ease;
}
.sysdb-table-row {
    cursor: pointer;
    transition: background 0.15s ease;
}
.sysdb-table-row:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.05);
}
.sysdb-sortable {
    cursor: pointer;
    user-select: none;
}
.sysdb-sortable:hover {
    color: rgb(var(--primary-rgb, 132, 90, 223));
}
.sysdb-detail-section {
    margin-bottom: 1.25rem;
}
.sysdb-detail-section h6 {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    margin-bottom: 0.65rem;
}
.sysdb-col-type {
    font-family: ui-monospace, monospace;
    font-size: 0.8rem;
    direction: ltr;
    text-align: left;
}
</style>
