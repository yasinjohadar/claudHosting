<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@2.15.1/devicon.min.css" crossorigin="anonymous">
<style>
.wp-wizard-page {
    max-width: 52rem;
    margin-inline: auto;
}

.wp-wizard-back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.82rem;
    color: var(--text-muted, #6c757d);
    text-decoration: none;
    margin-bottom: 1rem;
    transition: color 0.15s ease;
}

.wp-wizard-back:hover {
    color: var(--primary-color, #845ade);
}

.wp-wizard-shell {
    border: 0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
}

.wp-wizard-shell::before {
    content: '';
    display: block;
    height: 4px;
    background: linear-gradient(90deg, #21759b 0%, #845ade 45%, #f38020 100%);
}

.wp-wizard-hero {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.35rem 1.5rem 0.25rem;
}

.wp-wizard-hero__icon {
    width: 3.25rem;
    height: 3.25rem;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.85rem;
    background: linear-gradient(145deg, #21759b, #1a5f7a);
    color: #fff;
    font-size: 1.65rem;
    box-shadow: 0 6px 18px rgba(33, 117, 155, 0.35);
}

.wp-wizard-hero__title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 0.2rem;
    color: var(--default-text-color, #1e293b);
}

.wp-wizard-hero__sub {
    margin: 0;
    font-size: 0.84rem;
    color: var(--text-muted, #64748b);
}

.wp-wizard-stepper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 1.25rem 1.5rem 1.5rem;
    position: relative;
}

.wp-wizard-stepper__track {
    position: absolute;
    top: 2.35rem;
    left: 2.5rem;
    right: 2.5rem;
    height: 3px;
    background: var(--default-border, #e2e8f0);
    border-radius: 3px;
    z-index: 0;
}

.wp-wizard-stepper__progress {
    height: 100%;
    background: linear-gradient(90deg, #21759b, var(--primary-color, #845ade));
    border-radius: 3px;
    transition: width 0.35s ease;
}

.wp-wizard-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 1;
    min-width: 0;
}

.wp-wizard-step__dot {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    font-weight: 700;
    border: 2px solid var(--default-border, #e2e8f0);
    background: var(--custom-white, #fff);
    color: var(--text-muted, #94a3b8);
    transition: all 0.25s ease;
    margin-bottom: 0.5rem;
}

.wp-wizard-step--done .wp-wizard-step__dot,
.wp-wizard-step--active .wp-wizard-step__dot {
    border-color: transparent;
    color: #fff;
    box-shadow: 0 4px 12px rgba(33, 117, 155, 0.3);
}

.wp-wizard-step--done .wp-wizard-step__dot {
    background: linear-gradient(145deg, #22c55e, #16a34a);
}

.wp-wizard-step--active .wp-wizard-step__dot {
    background: linear-gradient(145deg, #21759b, #1a5f7a);
    transform: scale(1.06);
}

.wp-wizard-step__label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted, #94a3b8);
    line-height: 1.3;
    max-width: 6.5rem;
}

.wp-wizard-step--active .wp-wizard-step__label,
.wp-wizard-step--done .wp-wizard-step__label {
    color: var(--default-text-color, #334155);
}

.wp-wizard-body {
    padding: 0 1.5rem 1.5rem;
}

.wp-wizard-panel {
    background: var(--default-background, #f8fafc);
    border: 1px solid var(--default-border, #e2e8f0);
    border-radius: 0.75rem;
    padding: 1.35rem 1.25rem;
}

.wp-wizard-panel__head {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 1.15rem;
}

.wp-wizard-panel__head-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    color: var(--primary-color, #845ade);
    font-size: 1rem;
}

.wp-wizard-panel__title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
    color: var(--default-text-color, #1e293b);
}

.wp-wizard-panel__desc {
    font-size: 0.78rem;
    color: var(--text-muted, #64748b);
    margin: 0.1rem 0 0;
}

.wp-wizard-field {
    margin-bottom: 1.1rem;
}

.wp-wizard-field:last-child {
    margin-bottom: 0;
}

.wp-wizard-field .form-label {
    font-size: 0.84rem;
    font-weight: 600;
    margin-bottom: 0.4rem;
}

.wp-wizard-input-icon {
    position: relative;
}

.wp-wizard-input-icon > i {
    position: absolute;
    top: 50%;
    inset-inline-start: 0.85rem;
    transform: translateY(-50%);
    color: var(--text-muted, #94a3b8);
    font-size: 0.95rem;
    pointer-events: none;
    z-index: 2;
}

.wp-wizard-input-icon .form-control {
    padding-inline-start: 2.5rem;
}

.wp-wizard-slug-group .input-group-text {
    background: var(--custom-white, #fff);
    font-size: 0.82rem;
    color: var(--text-muted, #64748b);
    border-color: var(--default-border, #dee2e6);
}

.wp-wizard-slug-group .form-control {
    font-family: ui-monospace, monospace;
    font-size: 0.88rem;
}

.wp-wizard-url-preview {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 50rem;
    background: rgba(33, 117, 155, 0.08);
    border: 1px solid rgba(33, 117, 155, 0.15);
    font-size: 0.78rem;
}

.wp-wizard-url-preview i {
    color: #21759b;
}

.wp-wizard-url-preview code {
    background: transparent;
    color: #1a5f7a;
    font-size: 0.78rem;
    padding: 0;
}

.wp-wizard-options {
    display: grid;
    gap: 0.75rem;
}

.wp-wizard-option {
    position: relative;
    margin: 0;
    cursor: pointer;
}

.wp-wizard-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.wp-wizard-option__card {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1rem 1.1rem;
    border: 2px solid var(--default-border, #e2e8f0);
    border-radius: 0.65rem;
    background: var(--custom-white, #fff);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.wp-wizard-option input:checked + .wp-wizard-option__card {
    border-color: var(--primary-color, #845ade);
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 132, 90, 223), 0.12);
}

.wp-wizard-option__icon {
    width: 2.35rem;
    height: 2.35rem;
    flex-shrink: 0;
    border-radius: 0.55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.wp-wizard-option__icon--new {
    background: rgba(33, 117, 155, 0.12);
    color: #21759b;
}

.wp-wizard-option__icon--shared {
    background: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    color: var(--primary-color, #845ade);
}

.wp-wizard-option__title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.15rem;
    color: var(--default-text-color, #1e293b);
}

.wp-wizard-option__text {
    font-size: 0.78rem;
    color: var(--text-muted, #64748b);
    margin: 0;
    line-height: 1.45;
}

.wp-wizard-summary {
    background: linear-gradient(135deg, rgba(33, 117, 155, 0.08) 0%, rgba(var(--primary-rgb, 132, 90, 223), 0.06) 100%);
    border: 1px solid rgba(33, 117, 155, 0.18);
    border-radius: 0.75rem;
    padding: 1.1rem 1.15rem;
    margin-bottom: 1.15rem;
}

.wp-wizard-summary__name {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--default-text-color, #1e293b);
}

.wp-wizard-summary__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
}

.wp-wizard-summary__pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.78rem;
    padding: 0.3rem 0.65rem;
    border-radius: 50rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border, #e2e8f0);
    color: var(--default-text-color, #475569);
}

.wp-wizard-summary__pill code {
    font-size: 0.75rem;
    background: transparent;
    padding: 0;
    color: #21759b;
}

.wp-wizard-tech-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 1.15rem;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    background: var(--custom-white, #fff);
    border: 1px dashed var(--default-border, #e2e8f0);
}

.wp-wizard-tech-strip span {
    font-size: 0.72rem;
    color: var(--text-muted, #64748b);
    font-weight: 600;
}

.wp-wizard-tech-strip i {
    font-size: 1.35rem;
    line-height: 1;
}

.wp-wizard-tech-strip img {
    width: 1.35rem;
    height: 1.35rem;
    object-fit: contain;
}

.wp-wizard-cf-card {
    border: 1px solid rgba(243, 128, 32, 0.25);
    border-radius: 0.75rem;
    background: linear-gradient(180deg, rgba(243, 128, 32, 0.06) 0%, transparent 100%);
    overflow: hidden;
}

.wp-wizard-cf-card__head {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgba(243, 128, 32, 0.15);
}

.wp-wizard-cf-card__head img {
    width: 1.75rem;
    height: 1.75rem;
}

.wp-wizard-cf-card__head h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
}

.wp-wizard-cf-card__body {
    padding: 1rem;
}

.wp-wizard-hint {
    font-size: 0.78rem;
    color: var(--text-muted, #64748b);
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
    margin-top: 1rem;
    line-height: 1.5;
}

.wp-wizard-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 1.35rem;
    padding-top: 1.15rem;
    border-top: 1px solid var(--default-border, #e2e8f0);
}

.wp-wizard-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--text-muted, #64748b);
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border, #dee2e6);
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.wp-wizard-btn-back:hover {
    background: var(--default-background, #f8fafc);
    color: var(--default-text-color, #334155);
}

.wp-wizard-btn-next,
.wp-wizard-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.88rem;
    font-weight: 600;
    border: none;
    box-shadow: 0 4px 14px rgba(var(--primary-rgb, 132, 90, 223), 0.25);
}

.wp-wizard-btn-submit {
    background: linear-gradient(145deg, #22c55e, #16a34a);
    box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}

.wp-wizard-btn-submit:hover {
    background: linear-gradient(145deg, #16a34a, #15803d);
}

#sharedProjectWrap {
    margin-top: 0.75rem;
    padding: 1rem;
    border-radius: 0.5rem;
    background: var(--custom-white, #fff);
    border: 1px solid var(--default-border, #e2e8f0);
}

[data-theme-mode="dark"] .wp-wizard-panel,
[data-theme-mode="dark"] .wp-wizard-option__card,
[data-theme-mode="dark"] #sharedProjectWrap {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme-mode="dark"] .wp-wizard-summary__pill {
    background: rgba(255, 255, 255, 0.05);
}

@media (max-width: 575.98px) {
    .wp-wizard-stepper__track {
        display: none;
    }

    .wp-wizard-step__label {
        font-size: 0.65rem;
    }

    .wp-wizard-hero {
        flex-direction: column;
        text-align: center;
    }

    .wp-wizard-actions {
        flex-direction: column-reverse;
    }

    .wp-wizard-actions .wp-wizard-btn-next,
    .wp-wizard-actions .wp-wizard-btn-submit {
        width: 100%;
        justify-content: center;
    }
}
</style>
