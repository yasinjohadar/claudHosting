<div class="admin-kpi-grid mb-4">
    <a href="{{ route('admin.customers.index') }}" class="admin-kpi-link">
        <div class="admin-kpi-card admin-kpi-card--purple">
            <div class="admin-kpi-card-inner">
                <div>
                    <div class="admin-kpi-label">العملاء</div>
                    <div class="admin-kpi-value">{{ number_format($stats['total_customers'] ?? 0) }}</div>
                    <div class="admin-kpi-sub">عميل مسجّل</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-users"></i></div>
            </div>
        </div>
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="admin-kpi-link">
        <div class="admin-kpi-card admin-kpi-card--green">
            <div class="admin-kpi-card-inner">
                <div>
                    <div class="admin-kpi-label">الفواتير</div>
                    <div class="admin-kpi-value">{{ number_format($stats['total_invoices'] ?? 0) }}</div>
                    <div class="admin-kpi-sub">إيراد الشهر: {{ number_format($stats['revenue_monthly'] ?? 0, 2) }}</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-file-text"></i></div>
            </div>
        </div>
    </a>
    <a href="{{ route('admin.coolify.overview') }}" class="admin-kpi-link">
        <div class="admin-kpi-card admin-kpi-card--blue">
            <div class="admin-kpi-card-inner">
                <div>
                    <div class="admin-kpi-label">Coolify</div>
                    <div class="admin-kpi-value">{{ number_format($coolifyStats['applications'] ?? 0) }}</div>
                    <div class="admin-kpi-sub">{{ number_format($coolifyStats['projects'] ?? 0) }} مشروع · {{ number_format($coolifyStats['servers'] ?? 0) }} سيرفر</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-layers"></i></div>
            </div>
        </div>
    </a>
    <a href="{{ route('admin.tickets.index') }}" class="admin-kpi-link">
        <div class="admin-kpi-card admin-kpi-card--orange">
            <div class="admin-kpi-card-inner">
                <div>
                    <div class="admin-kpi-label">التذاكر</div>
                    <div class="admin-kpi-value">{{ number_format($stats['total_tickets'] ?? 0) }}</div>
                    <div class="admin-kpi-sub">تذكرة في النظام</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-message-circle"></i></div>
            </div>
        </div>
    </a>
</div>
