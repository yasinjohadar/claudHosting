@once
<style>
.whm-email-copy-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    max-width: 100%;
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.03);
}
[data-theme-mode="dark"] .whm-email-copy-wrap,
.dark .whm-email-copy-wrap {
    background: rgba(255, 255, 255, 0.06);
}
.whm-copy-email {
    flex-shrink: 0;
    width: 1.75rem;
    height: 1.75rem;
    padding: 0;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.25);
    color: var(--primary-color, #845adf);
    background: transparent;
    border-radius: 0.35rem;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.whm-copy-email:hover {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    border-color: var(--primary-color, #845adf);
}
.whm-copy-email.whm-copy-done {
    color: #198754;
    border-color: #198754;
    background: rgba(25, 135, 84, 0.12);
}
</style>
@endonce
