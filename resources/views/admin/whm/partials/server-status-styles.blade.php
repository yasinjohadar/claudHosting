<style>
.whm-server-status .whm-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.85rem;
}
.whm-server-status .whm-metric-card,
.whm-server-status .whm-disk-row {
    padding: 1rem 1.1rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.06);
    background: var(--custom-white, #fff);
    height: 100%;
}
.whm-server-status .whm-metric-warning,
.whm-server-status .whm-disk-warning {
    background: rgba(255, 193, 7, 0.08);
    border-color: rgba(255, 193, 7, 0.35);
}
.whm-server-status .whm-metric-danger,
.whm-server-status .whm-disk-danger {
    background: rgba(220, 53, 69, 0.06);
    border-color: rgba(220, 53, 69, 0.25);
}
.whm-server-status .whm-metric-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
}
.whm-server-status .whm-metric-value {
    font-size: 1.1rem;
    font-weight: 700;
}
.whm-server-status .whm-metric-progress {
    height: 6px;
    border-radius: 3px;
}
.whm-server-status .fe.spin {
    animation: whm-spin 0.8s linear infinite;
}
@keyframes whm-spin {
    to { transform: rotate(360deg); }
}
</style>
