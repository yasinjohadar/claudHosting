<style>
.snapshot-show-hero {
    border-radius: 1.15rem;
    padding: 1.5rem 1.65rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(125deg, rgba(14, 165, 233, 0.14) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.12) 45%, rgba(34, 197, 94, 0.08) 100%);
    border: 1px solid rgba(14, 165, 233, 0.22);
    position: relative;
    overflow: hidden;
}
.snapshot-show-hero::after {
    content: '';
    position: absolute;
    top: -40%;
    left: -10%;
    width: 45%;
    height: 180%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
    transform: rotate(12deg);
    animation: snapshot-shine 4s ease-in-out infinite;
    pointer-events: none;
}
@keyframes snapshot-shine {
    0%, 100% { opacity: 0; transform: translateX(-120%) rotate(12deg); }
    50% { opacity: 1; transform: translateX(280%) rotate(12deg); }
}
.snapshot-live-ring {
    flex-shrink: 0;
}
.snapshot-live-ring > svg {
    transform: rotate(-90deg);
    width: 88px;
    height: 88px;
}
.snapshot-live-ring .ring-bg {
    fill: none;
    stroke: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    stroke-width: 8;
}
.snapshot-live-ring .ring-fg {
    fill: none;
    stroke: url(#snapshotRingGrad);
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.snapshot-stat-tile {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 1rem 1.1rem;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}
.snapshot-stat-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
}
.snapshot-stat-tile.is-active {
    border-color: rgba(245, 158, 11, 0.45);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
}
.snapshot-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.snapshot-stat-icon--status { background: rgba(var(--primary-rgb, 132, 90, 223), 0.12); color: rgb(var(--primary-rgb, 132, 90, 223)); }
.snapshot-stat-icon--ok { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
.snapshot-stat-icon--fail { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
.snapshot-stat-icon--run { background: rgba(245, 158, 11, 0.14); color: #d97706; }
.snapshot-stat-value {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1;
    transition: transform 0.25s ease;
}
.snapshot-stat-value.bump {
    animation: snapshot-bump 0.35s ease;
}
@keyframes snapshot-bump {
    0% { transform: scale(1); }
    50% { transform: scale(1.12); }
    100% { transform: scale(1); }
}
.snapshot-progress-panel {
    border-radius: 1rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 1.15rem 1.25rem;
    margin-bottom: 1.25rem;
}
.snapshot-progress-track {
    height: 10px;
    border-radius: 999px;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
    overflow: hidden;
    position: relative;
}
.snapshot-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #0ea5e9, rgb(var(--primary-rgb, 132, 90, 223)), #22c55e);
    background-size: 200% 100%;
    transition: width 0.55s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.snapshot-progress-fill.is-animated {
    animation: snapshot-gradient 2s linear infinite;
}
@keyframes snapshot-gradient {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}
.snapshot-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    display: inline-block;
    margin-inline-end: 0.35rem;
}
.snapshot-live-dot.is-polling {
    background: #0ea5e9;
    animation: snapshot-pulse 1.2s ease-in-out infinite;
}
@keyframes snapshot-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.85); }
}
.snapshot-restore-panel {
    border-radius: 1rem;
    border: 1px solid rgba(245, 158, 11, 0.28);
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.06) 0%, var(--custom-white, #fff) 40%);
    overflow: hidden;
}
.snapshot-restore-panel .card-header {
    background: rgba(245, 158, 11, 0.08);
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
}
.snapshot-items-table tbody tr {
    transition: background 0.2s ease, box-shadow 0.2s ease;
}
.snapshot-items-table tbody tr.row-backup-running,
.snapshot-items-table tbody tr.row-running {
    background: rgba(14, 165, 233, 0.06);
    box-shadow: inset 3px 0 0 #0ea5e9;
}
.snapshot-items-table tbody tr.row-backup-pending,
.snapshot-items-table tbody tr.row-pending {
    background: rgba(148, 163, 184, 0.06);
}
.snapshot-items-table tbody tr.row-backup-failed,
.snapshot-items-table tbody tr.row-failed {
    background: rgba(239, 68, 68, 0.05);
    box-shadow: inset 3px 0 0 #ef4444;
}
.snapshot-items-table tbody tr.row-backup-completed,
.snapshot-items-table tbody tr.row-completed {
    box-shadow: inset 3px 0 0 #22c55e;
}
.snapshot-items-table tbody tr.row-updated {
    animation: snapshot-row-flash 0.8s ease;
}
@keyframes snapshot-row-flash {
    0% { background: rgba(14, 165, 233, 0.18); }
    100% { background: transparent; }
}
.snapshot-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.65rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 700;
}
.snapshot-status-pill::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}
.snapshot-status-pill.status-completed { background: rgba(34, 197, 94, 0.12); color: #15803d; }
.snapshot-status-pill.status-failed { background: rgba(239, 68, 68, 0.12); color: #b91c1c; }
.snapshot-status-pill.status-running { background: rgba(245, 158, 11, 0.15); color: #b45309; }
.snapshot-status-pill.status-running::before { animation: snapshot-pulse 1s infinite; }
.snapshot-status-pill.status-pending { background: rgba(148, 163, 184, 0.15); color: #64748b; }
.snapshot-status-pill.status-cancelled { background: rgba(100, 116, 139, 0.15); color: #475569; }
.snapshot-items-table tbody tr.row-backup-cancelled,
.snapshot-items-table tbody tr.row-cancelled {
    background: rgba(100, 116, 139, 0.06);
    box-shadow: inset 3px 0 0 #94a3b8;
}
.snapshot-resource-name {
    font-weight: 600;
    font-size: 0.9rem;
}
.snapshot-type-chip {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 0.4rem;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
    color: rgb(var(--primary-rgb, 132, 90, 223));
}
.snapshot-strategy-chip {
    font-size: 0.72rem;
    padding: 0.2rem 0.5rem;
    border-radius: 0.4rem;
    background: rgba(14, 165, 233, 0.1);
    color: #0369a1;
}
.snapshot-strategy-chip.strategy-manifest_only {
    background: rgba(148, 163, 184, 0.15);
    color: #475569;
}

/* Restore progress panel (amber) */
.snapshot-restore-progress-panel {
    border-radius: 1rem;
    border: 1px solid rgba(245, 158, 11, 0.35);
    background: linear-gradient(125deg, rgba(245, 158, 11, 0.12) 0%, rgba(251, 191, 36, 0.06) 50%, var(--custom-white, #fff) 100%);
    padding: 1.15rem 1.25rem;
    margin-bottom: 1.25rem;
}
.snapshot-restore-ring > svg {
    transform: rotate(-90deg);
    width: 76px;
    height: 76px;
}
.snapshot-restore-ring .ring-bg {
    fill: none;
    stroke: rgba(245, 158, 11, 0.15);
    stroke-width: 7;
}
.snapshot-restore-ring .ring-fg {
    fill: none;
    stroke: #f59e0b;
    stroke-width: 7;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.55s cubic-bezier(0.4, 0, 0.2, 1);
}
.snapshot-restore-progress-track {
    height: 8px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.15);
    overflow: hidden;
}
.snapshot-restore-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #f59e0b, #fbbf24, #ea580c);
    background-size: 200% 100%;
    transition: width 0.55s cubic-bezier(0.4, 0, 0.2, 1);
}
.snapshot-restore-progress-fill.is-animated {
    animation: snapshot-gradient 2s linear infinite;
}
.snapshot-restore-stat-chip {
    border-radius: 0.75rem;
    border: 1px solid var(--default-border);
    background: var(--custom-white, #fff);
    padding: 0.65rem 0.75rem;
    text-align: center;
}
.snapshot-live-dot.restore-dot.is-polling {
    background: #f59e0b;
}

/* Table: restore column highlights */
.snapshot-items-table tbody tr.row-restore-running {
    background: rgba(245, 158, 11, 0.08);
    box-shadow: inset 3px 0 0 #f59e0b;
}
.snapshot-items-table tbody tr.row-restore-pending {
    background: rgba(251, 191, 36, 0.06);
}
.snapshot-items-table tbody tr.row-restore-failed {
    box-shadow: inset 3px 0 0 #ea580c;
}
.snapshot-items-table tbody tr.row-restore-completed {
    box-shadow: inset 3px 0 0 #d97706;
}
.snapshot-items-table tbody tr.row-restore-skipped {
    opacity: 0.92;
}
.snapshot-status-pill.restore-pill.status-skipped {
    background: rgba(148, 163, 184, 0.15);
    color: #64748b;
}
.snapshot-status-pill.restore-pill.status-skipped::before {
    background: #94a3b8;
}
.item-restore-error-row td {
    background: rgba(245, 158, 11, 0.06);
}

/* Operation completion modal */
.snapshot-operation-modal-content {
    border-radius: 1.1rem;
}
.snapshot-op-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.25rem;
    margin: 0 auto;
}
.snapshot-op-icon--success {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}
.snapshot-op-icon--warning {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
}
.snapshot-op-icon--danger {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}
.snapshot-op-icon--muted {
    background: rgba(100, 116, 139, 0.12);
    color: #64748b;
}
.snapshot-op-stat-box {
    border-radius: 0.65rem;
    border: 1px solid var(--default-border);
    padding: 0.5rem 0.65rem;
    background: var(--custom-white, #fff);
}
.snapshot-op-error-list li {
    padding: 0.5rem 0.65rem;
    border-radius: 0.5rem;
    background: rgba(239, 68, 68, 0.06);
    border: 1px solid rgba(239, 68, 68, 0.12);
}
</style>
