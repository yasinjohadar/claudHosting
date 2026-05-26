<style>
    .catalog-flow-hero {
        position: relative;
        border-radius: 1rem;
        padding: 1.75rem 1.75rem 1.5rem;
        margin-bottom: 1.5rem;
        overflow: hidden;
        background: linear-gradient(135deg,
            rgba(var(--primary-rgb, 132, 90, 223), 0.12) 0%,
            rgba(var(--primary-rgb, 132, 90, 223), 0.04) 45%,
            var(--custom-white, #fff) 100%);
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.15);
    }
    .catalog-flow-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        inset-inline-end: -8%;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.08);
        pointer-events: none;
    }
    .catalog-flow-back {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
        color: var(--text-muted, #6c757d);
        text-decoration: none;
        margin-bottom: 1rem;
        transition: color 0.2s ease;
    }
    .catalog-flow-back:hover { color: rgb(var(--primary-rgb, 132, 90, 223)); }
    .catalog-flow-icon {
        width: 64px;
        height: 64px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: #fff;
        background: linear-gradient(145deg,
            rgb(var(--primary-rgb, 132, 90, 223)),
            rgba(var(--primary-rgb, 132, 90, 223), 0.75));
        box-shadow: 0 8px 24px rgba(var(--primary-rgb, 132, 90, 223), 0.35);
        flex-shrink: 0;
    }
    .catalog-flow-icon--database {
        background: linear-gradient(145deg, #0d6efd, #4dabf7);
        box-shadow: 0 8px 24px rgba(13, 110, 253, 0.35);
    }
    .catalog-flow-icon--service {
        background: linear-gradient(145deg, rgb(var(--primary-rgb, 132, 90, 223)), #a78bfa);
        box-shadow: 0 8px 24px rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    }
    .catalog-flow-icon--application {
        background: linear-gradient(145deg, #198754, #51cf66);
        box-shadow: 0 8px 24px rgba(25, 135, 84, 0.35);
    }
    .catalog-flow-title { font-weight: 700; font-size: 1.5rem; margin-bottom: 0.35rem; }
    .catalog-flow-desc { color: var(--text-muted, #6c757d); font-size: 0.9rem; line-height: 1.6; max-width: 42rem; }
    .catalog-flow-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 2rem;
    }

    .catalog-stepper {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 2rem;
        padding: 0 0.25rem;
    }
    .catalog-stepper__item {
        flex: 1;
        text-align: center;
        position: relative;
        min-width: 0;
    }
    .catalog-stepper__item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        inset-inline-start: calc(50% + 22px);
        width: calc(100% - 44px);
        height: 3px;
        background: var(--default-border, #e9ecef);
        border-radius: 2px;
        z-index: 0;
    }
    .catalog-stepper__item.is-done:not(:last-child)::after {
        background: linear-gradient(90deg, rgb(var(--primary-rgb, 132, 90, 223)), rgba(var(--primary-rgb, 132, 90, 223), 0.5));
    }
    .catalog-stepper__circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        margin: 0 auto 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        position: relative;
        z-index: 1;
        background: var(--custom-white, #fff);
        border: 2px solid var(--default-border, #dee2e6);
        color: var(--text-muted, #6c757d);
        transition: all 0.25s ease;
    }
    .catalog-stepper__item.is-active .catalog-stepper__circle {
        border-color: rgb(var(--primary-rgb, 132, 90, 223));
        background: rgb(var(--primary-rgb, 132, 90, 223));
        color: #fff;
        box-shadow: 0 4px 14px rgba(var(--primary-rgb, 132, 90, 223), 0.4);
    }
    .catalog-stepper__item.is-done .catalog-stepper__circle {
        border-color: rgb(var(--primary-rgb, 132, 90, 223));
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        color: rgb(var(--primary-rgb, 132, 90, 223));
    }
    .catalog-stepper__label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted, #6c757d);
        line-height: 1.3;
        padding: 0 0.25rem;
    }
    .catalog-stepper__item.is-active .catalog-stepper__label,
    .catalog-stepper__item.is-done .catalog-stepper__label {
        color: var(--default-text-color, #333);
    }

    .catalog-panel {
        border-radius: 1rem;
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.1);
        background: var(--custom-white, #fff);
        overflow: hidden;
    }
    .catalog-panel__head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--default-border, #e9ecef);
        display: flex;
        align-items: center;
        gap: 0.65rem;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.03);
    }
    .catalog-panel__head-icon {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
        color: rgb(var(--primary-rgb, 132, 90, 223));
    }
    .catalog-panel__body { padding: 1.25rem 1.5rem; }

    .catalog-checklist { list-style: none; padding: 0; margin: 0; }
    .catalog-checklist li {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.65rem 0;
        border-bottom: 1px dashed var(--default-border, #e9ecef);
    }
    .catalog-checklist li:last-child { border-bottom: none; padding-bottom: 0; }
    .catalog-checklist__icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .catalog-steps-list { list-style: none; padding: 0; margin: 0; counter-reset: catalog-step; }
    .catalog-steps-list li {
        counter-increment: catalog-step;
        display: flex;
        gap: 1rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--default-border, #e9ecef);
    }
    .catalog-steps-list li:last-child { border-bottom: none; }
    .catalog-steps-list li::before {
        content: counter(catalog-step);
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        background: rgba(var(--primary-rgb, 132, 90, 223), 0.1);
        color: rgb(var(--primary-rgb, 132, 90, 223));
    }

    .catalog-sidebar-card {
        border-radius: 1rem;
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        background: var(--custom-white, #fff);
        padding: 1.25rem;
        position: sticky;
        top: 5rem;
    }
    .catalog-sidebar-meta {
        font-size: 0.8rem;
        color: var(--text-muted, #6c757d);
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--default-border, #e9ecef);
    }
    .catalog-sidebar-meta:last-of-type { border-bottom: none; }
    .catalog-sidebar-meta strong { color: var(--default-text-color, #333); font-weight: 600; }

    .catalog-summary-box {
        border-radius: 0.75rem;
        padding: 1rem 1.15rem;
        background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.06), rgba(var(--primary-rgb, 132, 90, 223), 0.02));
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        margin-bottom: 1.25rem;
    }
    .catalog-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0;
        font-size: 0.85rem;
    }
    .catalog-summary-row span:first-child { color: var(--text-muted, #6c757d); }
    .catalog-summary-row code {
        font-size: 0.75rem;
        max-width: 55%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .catalog-form-field {
        margin-bottom: 1.15rem;
    }
    .catalog-form-field label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .catalog-form-field label i { color: rgb(var(--primary-rgb, 132, 90, 223)); font-size: 0.9rem; }
    .catalog-wizard-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        padding-top: 1.25rem;
        margin-top: 1.25rem;
        border-top: 1px solid var(--default-border, #e9ecef);
    }
    .catalog-btn-next {
        padding-inline: 1.5rem;
        box-shadow: 0 4px 14px rgba(var(--primary-rgb, 132, 90, 223), 0.35);
    }
    .catalog-btn-create {
        padding-inline: 1.75rem;
        box-shadow: 0 4px 14px rgba(25, 135, 84, 0.35);
    }

    @media (max-width: 576px) {
        .catalog-stepper__label { font-size: 0.65rem; }
        .catalog-stepper__circle { width: 30px; height: 30px; font-size: 0.75rem; }
        .catalog-stepper__item:not(:last-child)::after { top: 15px; }
    }
</style>
