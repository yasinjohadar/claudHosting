<style>
    :root {
        --brand-primary: #0057B8;
        --brand-primary-dark: #003F88;
        --brand-primary-light: #2E9AD0;
        --brand-accent: #F59E0B;
        --brand-primary-glow: rgba(0, 87, 184, 0.18);
        --surface: #ffffff;
        --text-muted: #64748b;
        --border: #e2e8f0;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 24px 16px;
        color: #1e293b;
        background:
            radial-gradient(ellipse 80% 60% at 15% 20%, rgba(46, 154, 208, 0.22), transparent 55%),
            radial-gradient(ellipse 70% 50% at 85% 80%, rgba(0, 87, 184, 0.14), transparent 50%),
            linear-gradient(160deg, #eef6fc 0%, #f8fbff 45%, #f1f5f9 100%);
        position: relative;
        overflow-x: hidden;
    }

    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image: radial-gradient(rgba(0, 87, 184, 0.07) 1px, transparent 1px);
        background-size: 28px 28px;
        pointer-events: none;
        opacity: 0.45;
    }

    .auth-wrapper {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        width: min(100%, 1040px);
        min-height: 560px;
        background: var(--surface);
        border-radius: 24px;
        box-shadow:
            0 4px 6px rgba(15, 23, 42, 0.02),
            0 24px 60px rgba(0, 87, 184, 0.12),
            0 0 0 1px rgba(255, 255, 255, 0.8) inset;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.65);
        position: relative;
        z-index: 1;
        animation: cardIn 0.65s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(18px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .auth-graphic {
        background:
            linear-gradient(145deg, rgba(0, 87, 184, 0.92) 0%, #0a6bc9 42%, #2E9AD0 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #fff;
        padding: 56px 44px;
        position: relative;
        overflow: hidden;
    }

    .auth-graphic::before,
    .auth-graphic::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .auth-graphic::before {
        width: 420px;
        height: 420px;
        background: rgba(255, 255, 255, 0.08);
        top: -160px;
        right: -120px;
        animation: floatSlow 9s ease-in-out infinite;
    }

    .auth-graphic::after {
        width: 280px;
        height: 280px;
        background: rgba(255, 255, 255, 0.06);
        bottom: -90px;
        left: -70px;
        animation: floatSlow 11s ease-in-out infinite reverse;
    }

    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(1px);
        pointer-events: none;
    }

    .blob--1 {
        width: 120px;
        height: 120px;
        background: rgba(245, 158, 11, 0.22);
        top: 18%;
        left: 12%;
        animation: floatSlow 7s ease-in-out infinite;
    }

    .blob--2 {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.12);
        bottom: 22%;
        right: 14%;
        animation: floatSlow 8s ease-in-out infinite reverse;
    }

    @keyframes floatSlow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }

    .graphic-content {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 320px;
    }

    .graphic-logo-wrap {
        width: 96px;
        height: 96px;
        margin: 0 auto 28px;
        border-radius: 28px;
        background: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
        animation: floatSlow 6s ease-in-out infinite;
    }

    .graphic-logo-wrap img {
        width: 64px;
        height: 64px;
        object-fit: contain;
    }

    .graphic-icon-wrap {
        width: 96px;
        height: 96px;
        margin: 0 auto 28px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        animation: floatSlow 6s ease-in-out infinite;
    }

    .graphic-icon-wrap svg {
        width: 44px;
        height: 44px;
        color: #fff;
        opacity: 0.95;
    }

    .graphic-content h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 12px;
        line-height: 1.25;
        letter-spacing: -0.02em;
    }

    .graphic-content p {
        font-size: 0.95rem;
        opacity: 0.9;
        line-height: 1.7;
        color: rgba(255, 255, 255, 0.88);
    }

    .graphic-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
        margin-top: 28px;
    }

    .graphic-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(4px);
    }

    .auth-container {
        padding: 52px 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    .auth-brand {
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .auth-brand img {
        width: 54px;
        height: 54px;
        object-fit: contain;
        background: #ffffff;
        border-radius: 14px;
        padding: 6px;
        box-shadow: 0 4px 14px rgba(0, 87, 184, 0.12);
    }

    .auth-brand h1 {
        color: var(--brand-primary-dark);
        font-size: 1.45rem;
        font-weight: 800;
        margin-bottom: 4px;
        letter-spacing: -0.02em;
    }

    .auth-brand p {
        color: var(--text-muted);
        font-size: 0.84rem;
        font-weight: 500;
    }

    .auth-intro {
        font-size: 0.88rem;
        line-height: 1.75;
        color: var(--text-muted);
        margin-bottom: 24px;
        padding: 14px 16px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .auth-steps {
        list-style: none;
        margin: 0 0 24px;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .auth-steps li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.8rem;
        color: #475569;
        line-height: 1.5;
    }

    .auth-steps__num {
        flex-shrink: 0;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(0, 87, 184, 0.1);
        color: var(--brand-primary);
        font-size: 0.72rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-group {
        margin-bottom: 22px;
        text-align: right;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #334155;
        font-size: 0.82rem;
    }

    .input-wrap {
        position: relative;
    }

    .input-wrap svg {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: #94a3b8;
        pointer-events: none;
        transition: color 0.2s;
    }

    .form-group input {
        width: 100%;
        padding: 13px 44px 13px 16px;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        font-size: 0.92rem;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        text-align: right;
        background: #f8fafc;
        font-family: inherit;
        color: #0f172a;
    }

    .form-group input:focus {
        background: #fff;
        border-color: var(--brand-primary-light);
        box-shadow: 0 0 0 4px var(--brand-primary-glow);
        outline: none;
    }

    .input-wrap:focus-within svg {
        color: var(--brand-primary);
    }

    .form-group input::placeholder {
        color: #94a3b8;
    }

    .error-message {
        color: #dc2626;
        font-size: 0.75rem;
        margin-top: 6px;
        text-align: right;
        display: block;
    }

    .success-message {
        color: #166534;
        font-size: 0.84rem;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border-radius: 12px;
        border: 1px solid #86efac;
        line-height: 1.6;
    }

    .alert-error {
        display: block;
        padding: 12px 16px;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border: 1px solid #fecaca;
        border-radius: 12px;
        color: #b91c1c;
        font-size: 0.84rem;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-bottom: 24px;
        gap: 8px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--brand-primary);
    }

    .checkbox-group label {
        margin-bottom: 0;
        font-weight: 500;
        color: var(--text-muted);
        font-size: 0.84rem;
        cursor: pointer;
    }

    .form-footer {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .form-footer a {
        color: var(--brand-primary);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 600;
        transition: color 0.2s;
    }

    .form-footer a:hover {
        color: var(--brand-primary-dark);
        text-decoration: underline;
    }

    .btn {
        background: linear-gradient(135deg, var(--brand-primary-light) 0%, var(--brand-primary) 55%, var(--brand-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 32px;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
        width: 100%;
        box-shadow: 0 8px 24px rgba(0, 87, 184, 0.28);
        font-family: inherit;
        position: relative;
        overflow: hidden;
    }

    .btn::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 30%, rgba(255, 255, 255, 0.18) 50%, transparent 70%);
        transform: translateX(120%);
        transition: transform 0.55s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 63, 136, 0.32);
        filter: brightness(1.03);
    }

    .btn:hover::after {
        transform: translateX(-120%);
    }

    .btn:active {
        transform: translateY(0);
        box-shadow: 0 4px 14px rgba(0, 87, 184, 0.22);
    }

    .auth-footer {
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
        font-size: 0.75rem;
        color: #94a3b8;
        text-align: center;
    }

    /* توافق مع صفحة الدخول القديمة */
    .login-wrapper { display: grid; grid-template-columns: 1.05fr 0.95fr; width: min(100%, 1040px); min-height: 560px; background: var(--surface); border-radius: 24px; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.02), 0 24px 60px rgba(0, 87, 184, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.8) inset; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.65); position: relative; z-index: 1; animation: cardIn 0.65s cubic-bezier(0.22, 1, 0.36, 1); }
    .login-graphic { background: linear-gradient(145deg, rgba(0, 87, 184, 0.92) 0%, #0a6bc9 42%, #2E9AD0 100%); display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; padding: 56px 44px; position: relative; overflow: hidden; }
    .login-graphic::before, .login-graphic::after { content: ''; position: absolute; border-radius: 50%; pointer-events: none; }
    .login-graphic::before { width: 420px; height: 420px; background: rgba(255, 255, 255, 0.08); top: -160px; right: -120px; animation: floatSlow 9s ease-in-out infinite; }
    .login-graphic::after { width: 280px; height: 280px; background: rgba(255, 255, 255, 0.06); bottom: -90px; left: -70px; animation: floatSlow 11s ease-in-out infinite reverse; }
    .login-container { padding: 52px 48px; display: flex; flex-direction: column; justify-content: center; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); }
    .logo { margin-bottom: 36px; display: flex; align-items: center; gap: 14px; }
    .logo img { width: 54px; height: 54px; object-fit: contain; background: #ffffff; border-radius: 14px; padding: 6px; box-shadow: 0 4px 14px rgba(0, 87, 184, 0.12); }
    .logo h1 { color: var(--brand-primary-dark); font-size: 1.45rem; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.02em; }
    .logo p { color: var(--text-muted); font-size: 0.84rem; font-weight: 500; }
    .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 0.75rem; color: #94a3b8; text-align: center; }

    @media (max-width: 768px) {
        .auth-wrapper,
        .login-wrapper {
            grid-template-columns: 1fr;
            min-height: auto;
        }

        .auth-graphic,
        .login-graphic {
            padding: 36px 28px;
            min-height: 220px;
        }

        .graphic-logo-wrap,
        .graphic-icon-wrap {
            width: 76px;
            height: 76px;
            margin-bottom: 18px;
        }

        .graphic-logo-wrap img {
            width: 48px;
            height: 48px;
        }

        .graphic-icon-wrap svg {
            width: 36px;
            height: 36px;
        }

        .graphic-content h2 {
            font-size: 1.5rem;
        }

        .graphic-badges {
            margin-top: 18px;
        }

        .auth-container,
        .login-container {
            padding: 36px 28px;
        }

        .auth-stepper {
            padding: 12px 8px;
        }

        .auth-stepper__label {
            font-size: 0.62rem;
        }

        .auth-stepper__dot {
            width: 30px;
            height: 30px;
            font-size: 0.72rem;
        }

        .auth-channel--full {
            font-size: 0.78rem;
        }
    }

    .auth-channels {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 22px;
    }

    .auth-channel {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 12px;
        border: 2px solid var(--border);
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        color: #475569;
        font-family: inherit;
        font-size: 0.84rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        position: relative;
        overflow: hidden;
    }

    .auth-channel::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0;
        transition: opacity 0.25s;
        pointer-events: none;
    }

    .auth-channel__icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        transition: all 0.25s ease;
    }

    .auth-channel__icon svg {
        width: 22px;
        height: 22px;
        flex-shrink: 0;
    }

    .auth-channel--full {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: flex-start;
        padding: 14px 18px;
        gap: 14px;
    }

    .auth-channel--full .auth-channel__icon {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
    }

    .auth-channel.is-active {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .auth-channel--whatsapp.is-active {
        border-color: #25d366;
        color: #128c7e;
        background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%);
    }

    .auth-channel--whatsapp.is-active .auth-channel__icon {
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: #fff;
        box-shadow: 0 6px 16px rgba(37, 211, 102, 0.35);
    }

    .auth-channel--email.is-active {
        border-color: var(--brand-primary);
        color: var(--brand-primary-dark);
        background: linear-gradient(180deg, #eff6ff 0%, #f0f9ff 100%);
    }

    .auth-channel--email.is-active .auth-channel__icon {
        background: linear-gradient(135deg, var(--brand-primary-light), var(--brand-primary));
        color: #fff;
        box-shadow: 0 6px 16px rgba(0, 87, 184, 0.28);
    }

    .auth-channel--otp.is-active {
        border-color: #7c3aed;
        color: #5b21b6;
        background: linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%);
    }

    .auth-channel--otp.is-active .auth-channel__icon {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: #fff;
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.3);
    }

    .auth-channel:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        filter: grayscale(0.4);
    }

    .auth-stepper {
        display: flex;
        align-items: flex-start;
        gap: 0;
        margin-bottom: 24px;
        padding: 16px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
    }

    .auth-stepper__item {
        flex: 1;
        text-align: center;
        position: relative;
        padding: 0 8px;
    }

    .auth-stepper__item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 18px;
        left: -50%;
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        z-index: 0;
    }

    .auth-stepper__item.is-done:not(:last-child)::after,
    .auth-stepper__item.is-active:not(:last-child)::after {
        background: linear-gradient(90deg, var(--brand-primary-light), var(--brand-primary));
    }

    .auth-stepper__dot {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e2e8f0;
        color: #94a3b8;
        font-size: 0.8rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        position: relative;
        z-index: 1;
        transition: all 0.25s ease;
    }

    .auth-stepper__item.is-active .auth-stepper__dot {
        border-color: var(--brand-primary);
        background: linear-gradient(135deg, var(--brand-primary-light), var(--brand-primary));
        color: #fff;
        box-shadow: 0 4px 14px rgba(0, 87, 184, 0.3);
    }

    .auth-stepper__item.is-done .auth-stepper__dot {
        border-color: #22c55e;
        background: #22c55e;
        color: #fff;
    }

    .auth-stepper__label {
        font-size: 0.72rem;
        color: #64748b;
        line-height: 1.4;
        font-weight: 600;
    }

    .auth-stepper__item.is-active .auth-stepper__label {
        color: var(--brand-primary-dark);
    }

    .auth-panel {
        display: none;
        animation: panelIn 0.35s ease;
    }

    @keyframes panelIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .auth-panel.is-active {
        display: block;
    }

    .phone-country-block {
        margin-bottom: 18px;
    }

    .phone-country-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #334155;
        font-size: 0.82rem;
    }

    .phone-row {
        display: grid;
        grid-template-columns: 0.95fr 1.05fr;
        gap: 12px;
    }

    .phone-field-wrap {
        position: relative;
    }

    .phone-field-wrap select,
    .phone-field-wrap input {
        width: 100%;
        padding: 13px 14px;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        font-family: inherit;
        font-size: 0.92rem;
        background: #f8fafc;
        color: #0f172a;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .phone-field-wrap input {
        padding-left: 16px;
        text-align: left;
        direction: ltr;
    }

    .phone-field-wrap select:focus,
    .phone-field-wrap input:focus {
        background: #fff;
        border-color: var(--brand-primary-light);
        box-shadow: 0 0 0 4px var(--brand-primary-glow);
        outline: none;
    }

    .phone-field-wrap.is-invalid select,
    .phone-field-wrap.is-invalid input {
        border-color: #f87171;
        background: #fef2f2;
    }

    .form-hint {
        margin-top: 10px;
        font-size: 0.78rem;
        color: var(--text-muted);
        line-height: 1.55;
    }

    .auth-hint--warn {
        padding: 12px 14px;
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border-radius: 12px;
        border: 1px solid #fde68a;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }

    .auth-hint--warn::before {
        content: '💡';
        flex-shrink: 0;
    }

    .btn--wa {
        background: linear-gradient(135deg, #25d366 0%, #128c7e 55%, #0d9488 100%);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.32);
    }

    .btn--wa:hover {
        box-shadow: 0 12px 28px rgba(18, 140, 126, 0.38);
        filter: brightness(1.04);
    }

    .btn--outline {
        background: transparent;
        color: var(--brand-primary);
        border: 2px solid var(--brand-primary);
        box-shadow: none;
    }

    .btn--outline:hover {
        background: rgba(0, 87, 184, 0.06);
        box-shadow: none;
        transform: translateY(-1px);
    }

    .form-footer-sep {
        margin: 0 8px;
        color: var(--text-muted);
    }

    .auth-alt-login {
        margin-top: 24px;
        padding: 18px;
        border-radius: 16px;
        border: 1.5px dashed #cbd5e1;
        background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
        text-align: center;
    }

    .auth-alt-login__title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .auth-alt-login__desc {
        font-size: 0.76rem;
        color: var(--text-muted);
        margin-bottom: 14px;
        line-height: 1.5;
    }

    .auth-alt-login .btn {
        width: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        font-size: 0.88rem;
    }

    .otp-hero-phone {
        display: inline-block;
        padding: 4px 12px;
        background: rgba(255, 255, 255, 0.16);
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        margin-top: 8px;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
    }

    .otp-input {
        letter-spacing: 0.45em;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 800;
        padding: 18px 16px !important;
        background: linear-gradient(180deg, #f8fafc, #fff) !important;
    }

    .resend-box {
        margin-top: 20px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .resend-box .btn--outline {
        width: 100%;
        margin-bottom: 8px;
    }

    .resend-hint {
        margin-top: 8px;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .resend-row button:not(.btn) {
        background: none;
        border: none;
        color: var(--brand-primary);
        font-family: inherit;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
    }

    .resend-row button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .auth-graphic--otp {
        background: linear-gradient(145deg, #128c7e 0%, #25d366 42%, #0d9488 100%);
    }

    .auth-graphic--shield {
        background: linear-gradient(145deg, #5b21b6 0%, #7c3aed 42%, #8b5cf6 100%);
    }
</style>
