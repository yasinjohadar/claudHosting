@extends('admin.layouts.master')
@section('page-title') {{ $domainName }} @stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    use App\Http\Controllers\Admin\Namecom\NamecomDomainController as NamecomCtrl;
    $expiresRaw = $domain['expireDate'] ?? null;
    $status = NamecomCtrl::formatStatus($domain);
    $contacts = $domain['contacts'] ?? [];
    $ns = $domain['nameservers'] ?? [];
    $expiringSoon = NamecomCtrl::isExpiringSoon($expiresRaw, 30);
    $statusClass = match(true) {
        !($status['is_active'] ?? true) => 'expired',
        $expiringSoon => 'warning',
        default => 'active',
    };
    $daysLeft = null;
    if ($expiresRaw) {
        try {
            $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($expiresRaw), false);
        } catch (\Throwable) {}
    }
    $contactRoles = [
        'registrant' => ['label' => 'المالك', 'sub' => 'Registrant', 'class' => 'registrant'],
        'admin' => ['label' => 'إداري', 'sub' => 'Admin', 'class' => 'admin'],
        'tech' => ['label' => 'تقني', 'sub' => 'Tech', 'class' => 'tech'],
        'billing' => ['label' => 'فوترة', 'sub' => 'Billing', 'class' => 'billing'],
    ];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.namecom.domains.index') }}">نطاقات name.com</a>
                        <span class="text-muted mx-1">/</span>
                        <span>{{ $domainName }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title" dir="ltr">{{ $domainName }}</h1>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $status['label'] }}</span>
                    @if($daysLeft !== null && ($status['is_active'] ?? false))
                    <span class="text-muted small ms-2">
                        @if($daysLeft >= 0)
                            · {{ $daysLeft }} يوم متبقٍ
                        @else
                            · منتهي منذ {{ abs($daysLeft) }} يوم
                        @endif
                    </span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.namecom.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-settings me-1"></i> إعدادات name.com
                    </a>
                    <a href="{{ route('admin.namecom.domains.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> رجوع للقائمة
                    </a>
                </div>
            </div>
        </div>

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-calendar"></i></span>
                <div>
                    <div class="domain-kpi__label">تاريخ الإنشاء</div>
                    <div class="domain-kpi__value">{{ NamecomCtrl::formatDate($domain['createDate'] ?? null) }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--{{ $statusClass === 'expired' ? 'warning' : ($expiringSoon ? 'warning' : 'success') }}">
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">تاريخ الانتهاء</div>
                    <div class="domain-kpi__value">{{ NamecomCtrl::formatDate($expiresRaw) }}</div>
                    @if($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30)
                    <div class="domain-kpi__sub">ينتهي قريباً</div>
                    @endif
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-dollar-sign"></i></span>
                <div>
                    <div class="domain-kpi__label">سعر التجديد</div>
                    <div class="domain-kpi__value">{{ NamecomCtrl::formatMoney($domain['renewalPrice'] ?? null) }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-shield"></i></span>
                <div>
                    <div class="domain-kpi__label">الحماية</div>
                    <div class="domain-kpi__value domain-kpi__value--sm">
                        {{ NamecomCtrl::formatBool($domain['locked'] ?? false) === 'نعم' ? '🔒 مقفل' : 'مفتوح' }}
                        · Whois {{ NamecomCtrl::formatBool($domain['privacyEnabled'] ?? false) === 'نعم' ? 'خاص' : 'عام' }}
                    </div>
                    <div class="domain-kpi__sub">تجديد تلقائي: {{ NamecomCtrl::formatBool($domain['autorenewEnabled'] ?? false) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="domain-panel">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                        <h2 class="domain-panel__title">معلومات النطاق</h2>
                    </div>
                    <div class="domain-panel__body">
                        @foreach([
                            ['label' => 'تاريخ الإنشاء', 'value' => NamecomCtrl::formatDate($domain['createDate'] ?? null)],
                            ['label' => 'تاريخ الانتهاء', 'value' => NamecomCtrl::formatDate($expiresRaw)],
                            ['label' => 'سعر التجديد', 'value' => NamecomCtrl::formatMoney($domain['renewalPrice'] ?? null)],
                            ['label' => 'تجديد تلقائي', 'value' => NamecomCtrl::formatBool($domain['autorenewEnabled'] ?? false), 'bool' => true],
                            ['label' => 'قفل النقل', 'value' => NamecomCtrl::formatBool($domain['locked'] ?? false), 'bool' => true],
                            ['label' => 'خصوصية Whois', 'value' => NamecomCtrl::formatBool($domain['privacyEnabled'] ?? false), 'bool' => true],
                        ] as $row)
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">{{ $row['label'] }}</span>
                            <span class="domain-info-row__value @if(!empty($row['bool'])){{ $row['value'] === 'نعم' ? 'domain-bool-yes' : 'domain-bool-no' }}@endif">
                                {{ $row['value'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="domain-panel">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-server"></i></span>
                        <h2 class="domain-panel__title">Nameservers</h2>
                    </div>
                    <div class="domain-panel__body">
                        @if(is_array($ns) && count($ns) > 0)
                        <div class="domain-ns-list">
                            @foreach($ns as $server)
                            @php $nsText = is_string($server) ? $server : json_encode($server); @endphp
                            <div class="domain-ns-chip" data-copy="{{ $nsText }}" title="انقر للنسخ">
                                <span class="domain-ns-chip__text">{{ $nsText }}</span>
                                <span class="domain-ns-chip__copy"><i class="fe fe-copy"></i></span>
                            </div>
                            @endforeach
                        </div>
                        <p class="text-muted small mb-0 mt-2"><i class="fe fe-target"></i> انقر على أي nameserver للنسخ</p>
                        @else
                        <p class="text-muted mb-0">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php $hasContacts = false; @endphp
        @foreach($contactRoles as $key => $role)
            @if(is_array($contacts[$key] ?? null))
                @php $hasContacts = true; @endphp
            @endif
        @endforeach

        @if($hasContacts)
        <div class="domain-contact-grid">
            @foreach($contactRoles as $key => $role)
                @php $contact = is_array($contacts[$key] ?? null) ? $contacts[$key] : null; @endphp
                @if($contact)
                @php
                    $fullName = trim(($contact['firstName'] ?? '').' '.($contact['lastName'] ?? ''));
                    $initials = mb_strtoupper(mb_substr($contact['firstName'] ?? 'U', 0, 1).mb_substr($contact['lastName'] ?? '', 0, 1));
                @endphp
                <div class="domain-contact-card domain-contact-card--{{ $role['class'] }}">
                    <div class="domain-contact-card__head">
                        <span class="domain-contact-card__avatar">{{ $initials ?: '?' }}</span>
                        <div>
                            <p class="domain-contact-card__role">جهة اتصال — {{ $role['label'] }}</p>
                            <span class="text-muted small">{{ $role['sub'] }}</span>
                        </div>
                    </div>
                    <div class="domain-contact-card__body">
                        <div class="domain-contact-row">
                            <span class="domain-contact-row__label">الاسم</span>
                            <span class="domain-contact-row__value">{{ $fullName ?: '—' }}</span>
                        </div>
                        <div class="domain-contact-row">
                            <span class="domain-contact-row__label">الشركة</span>
                            <span class="domain-contact-row__value">{{ $contact['companyName'] ?? '—' }}</span>
                        </div>
                        <div class="domain-contact-row">
                            <span class="domain-contact-row__label">البريد</span>
                            <span class="domain-contact-row__value" dir="ltr">{{ $contact['email'] ?? '—' }}</span>
                        </div>
                        <div class="domain-contact-row">
                            <span class="domain-contact-row__label">الهاتف</span>
                            <span class="domain-contact-row__value" dir="ltr">{{ $contact['phone'] ?? '—' }}</span>
                        </div>
                        <div class="domain-contact-row domain-contact-row--stack">
                            <span class="domain-contact-row__label">العنوان</span>
                            <span class="domain-contact-row__value">
                                {{ $contact['address1'] ?? '—' }}
                                @if(!empty($contact['address2']))<br>{{ $contact['address2'] }}@endif
                                @if(!empty($contact['city']) || !empty($contact['state']) || !empty($contact['zip']))
                                <br>{{ ($contact['city'] ?? '') }}@if(!empty($contact['state'])), {{ $contact['state'] }}@endif {{ $contact['zip'] ?? '' }}
                                @endif
                                @if(!empty($contact['country']))
                                <br><span class="domain-contact-row__country">{{ $contact['country'] }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-layers text-primary"></i> سجلات DNS
                </h2>
                <span class="domain-dns-count">{{ count($dnsRecords ?? []) }} سجل</span>
            </div>
            @if(!empty($dnsError))
            <div class="p-3"><div class="alert alert-warning mb-0 py-2 small">{{ $dnsError }}</div></div>
            @endif
            <div class="table-responsive">
                <table class="domain-dns-table">
                    <thead>
                        <tr><th>النوع</th><th>المضيف</th><th>القيمة</th><th>TTL</th></tr>
                    </thead>
                    <tbody>
                    @forelse($dnsRecords as $r)
                        @php
                            $type = strtoupper((string) ($r['type'] ?? ''));
                            $typeClass = match($type) {
                                'A' => 'a', 'AAAA' => 'aaaa', 'CNAME' => 'cname',
                                'MX' => 'mx', 'TXT' => 'txt', 'NS' => 'ns',
                                default => 'default',
                            };
                            $value = $r['answer'] ?? $r['data'] ?? '—';
                        @endphp
                        <tr>
                            <td><span class="domain-dns-type domain-dns-type--{{ $typeClass }}">{{ $type ?: '—' }}</span></td>
                            <td dir="ltr">{{ $r['host'] ?? $r['fqdn'] ?? '—' }}</td>
                            <td>
                                <span class="domain-dns-value" data-copy="{{ $value }}" title="انقر للنسخ">{{ $value }}</span>
                            </td>
                            <td>{{ $r['ttl'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد سجلات DNS أو لا صلاحية لعرضها.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="domain-raw-toggle">
            <button class="domain-raw-toggle__btn" type="button" data-bs-toggle="collapse" data-bs-target="#rawJson">
                <span><i class="fe fe-code me-1"></i> بيانات API خام (تشخيص)</span>
                <i class="fe fe-chevron-down"></i>
            </button>
            <div class="collapse" id="rawJson">
                <div class="p-3">
                    <pre class="mb-0 small bg-dark text-light p-3 rounded" dir="ltr" style="max-height:400px;overflow:auto;">{{ json_encode($domain, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="domain-toast-copy" id="domainCopyToast">تم النسخ</div>
@endsection

@push('scripts')
<script>
(function() {
    const toast = document.getElementById('domainCopyToast');
    function showToast() {
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 1800);
    }
    function copyText(text) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(showToast).catch(function() {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            showToast();
        });
    }
    document.querySelectorAll('[data-copy]').forEach(el => {
        el.addEventListener('click', () => copyText(el.getAttribute('data-copy')));
    });
})();
</script>
@endpush
