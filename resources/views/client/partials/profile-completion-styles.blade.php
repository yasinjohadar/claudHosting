{{-- Styles for the profile-completion partial. Emitted once per render via @once. --}}
<style>
    .profile-completion {
        --pc-tone: rgb(var(--primary-rgb, 132, 90, 223));
        --pc-tone-soft: rgba(var(--primary-rgb, 132, 90, 223), 0.12);
        border: 1px solid var(--default-border, #e2e8f0);
        border-radius: 1.1rem;
        background: var(--custom-white, #fff);
        padding: 1.1rem 1.15rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .profile-completion--success { --pc-tone: #16a34a; --pc-tone-soft: rgba(22, 163, 74, 0.12); }
    .profile-completion--warning { --pc-tone: #d97706; --pc-tone-soft: rgba(217, 119, 6, 0.13); }
    .profile-completion--danger  { --pc-tone: #dc2626; --pc-tone-soft: rgba(220, 38, 38, 0.12); }

    .profile-completion__head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .profile-completion__badge {
        width: 42px;
        height: 42px;
        border-radius: 0.9rem;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: var(--pc-tone-soft);
        color: var(--pc-tone);
        font-size: 1.05rem;
    }

    .profile-completion__headline { flex: 1 1 auto; min-width: 0; }

    .profile-completion__headline strong {
        display: block;
        font-size: 0.98rem;
        font-weight: 800;
        color: var(--default-text-color, #0f172a);
    }

    .profile-completion__headline p {
        margin: 0.2rem 0 0;
        font-size: 0.82rem;
        line-height: 1.6;
        color: #64748b;
    }

    .profile-completion__percent {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--pc-tone);
        flex-shrink: 0;
        line-height: 1;
    }

    .profile-completion__track {
        height: 9px;
        border-radius: 999px;
        background: #eef2f7;
        overflow: hidden;
    }

    .profile-completion__bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--pc-tone), color-mix(in srgb, var(--pc-tone) 62%, #ffffff));
        /* Widen from zero so the number is felt, not just read. */
        animation: pc-grow 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
        min-width: 0.35rem;
    }

    @keyframes pc-grow {
        from { transform: scaleX(0); transform-origin: right center; }
        to   { transform: scaleX(1); transform-origin: right center; }
    }

    @media (prefers-reduced-motion: reduce) {
        .profile-completion__bar { animation: none; }
    }

    .profile-completion__list {
        list-style: none;
        margin: 1rem 0 0;
        padding: 0;
        display: grid;
        gap: 0.5rem;
    }

    .profile-completion__item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.6rem 0.75rem;
        border: 1px dashed var(--default-border, #e2e8f0);
        border-radius: 0.85rem;
        background: #f8fafc;
    }

    .profile-completion__item-icon {
        width: 32px;
        height: 32px;
        border-radius: 0.65rem;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: var(--pc-tone-soft);
        color: var(--pc-tone);
        font-size: 0.85rem;
    }

    .profile-completion__item-body { flex: 1 1 auto; min-width: 0; }

    .profile-completion__item-body strong {
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--default-text-color, #0f172a);
    }

    .profile-completion__item-body span {
        font-size: 0.76rem;
        color: #64748b;
        line-height: 1.5;
    }

    .profile-completion__item-action {
        flex-shrink: 0;
        font-size: 0.78rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--pc-tone);
        white-space: nowrap;
    }

    .profile-completion__item-action:hover { text-decoration: underline; }

    .profile-completion__done {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.85rem;
    }

    .profile-completion__done-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.73rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: rgba(22, 163, 74, 0.1);
        color: #15803d;
    }

    .profile-completion__optional {
        margin-top: 0.95rem;
        padding-top: 0.85rem;
        border-top: 1px solid var(--default-border, #e2e8f0);
    }

    .profile-completion__optional-title {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 0.45rem;
    }

    .profile-completion__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.75rem;
    }

    .profile-completion__chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.76rem;
        font-weight: 600;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        border: 1px solid var(--default-border, #e2e8f0);
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        transition: border-color 0.15s, color 0.15s, background 0.15s;
    }

    .profile-completion__chip:hover {
        border-color: var(--pc-tone);
        color: var(--pc-tone);
        background: var(--pc-tone-soft);
    }

    .profile-completion__cta { margin-top: 0.85rem; }

    /* Dashboard banner sits between the welcome hero and the KPI row. */
    .profile-completion--compact { margin-bottom: 1rem; }

    [data-theme-mode=dark] .profile-completion {
        background: var(--custom-white, #1c2126);
        border-color: rgba(255, 255, 255, 0.1);
    }

    [data-theme-mode=dark] .profile-completion__track { background: rgba(255, 255, 255, 0.08); }

    [data-theme-mode=dark] .profile-completion__item,
    [data-theme-mode=dark] .profile-completion__chip {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }

    [data-theme-mode=dark] .profile-completion__headline p,
    [data-theme-mode=dark] .profile-completion__item-body span {
        color: #94a3b8;
    }

    [data-theme-mode=dark] .profile-completion__optional {
        border-top-color: rgba(255, 255, 255, 0.1);
    }
</style>
