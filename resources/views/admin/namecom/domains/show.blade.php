@extends('admin.layouts.master')
@section('page-title') {{ $domainName }} @stop
@section('content')
@php
    $ctrl = \App\Http\Controllers\Admin\Namecom\NamecomDomainController::class;
    $expiresRaw = $domain['expireDate'] ?? null;
    $status = $ctrl::formatStatus($domain);
    $contacts = $domain['contacts'] ?? [];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0" dir="ltr">{{ $domainName }}</h4>
                <span class="namecom-status-pill badge {{ $status['badge_class'] }} mt-1">{{ $status['label'] }}</span>
            </div>
            <a href="{{ route('admin.namecom.domains.index') }}" class="btn btn-light btn-sm">رجوع للقائمة</a>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">معلومات النطاق</div></div>
                    <div class="card-body">
                        <p><strong>تاريخ الإنشاء:</strong> {{ $ctrl::formatDate($domain['createDate'] ?? null) }}</p>
                        <p><strong>تاريخ الانتهاء:</strong> {{ $ctrl::formatDate($expiresRaw) }}</p>
                        <p><strong>سعر التجديد:</strong> {{ $ctrl::formatMoney($domain['renewalPrice'] ?? null) }}</p>
                        <p><strong>تجديد تلقائي:</strong> {{ $ctrl::formatBool($domain['autorenewEnabled'] ?? false) }}</p>
                        <p><strong>قفل النقل:</strong> {{ $ctrl::formatBool($domain['locked'] ?? false) }}</p>
                        <p><strong>خصوصية Whois:</strong> {{ $ctrl::formatBool($domain['privacyEnabled'] ?? false) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title">Nameservers</div></div>
                    <div class="card-body">
                        @php $ns = $domain['nameservers'] ?? []; @endphp
                        @if(is_array($ns) && count($ns) > 0)
                            <ul class="mb-0 ps-3">
                                @foreach($ns as $server)
                                <li dir="ltr"><code>{{ is_string($server) ? $server : json_encode($server) }}</code></li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @foreach(['registrant' => 'المالك (Registrant)', 'admin' => 'إداري', 'tech' => 'تقني', 'billing' => 'فوترة'] as $key => $label)
            @php $contact = is_array($contacts[$key] ?? null) ? $contacts[$key] : null; @endphp
            @if($contact)
            <div class="card custom-card mb-3">
                <div class="card-header"><div class="card-title">جهة اتصال — {{ $label }}</div></div>
                <div class="card-body row">
                    <div class="col-md-6">
                        <p><strong>الاسم:</strong> {{ trim(($contact['firstName'] ?? '').' '.($contact['lastName'] ?? '')) ?: '—' }}</p>
                        <p><strong>الشركة:</strong> {{ $contact['companyName'] ?? '—' }}</p>
                        <p><strong>البريد:</strong> <span dir="ltr">{{ $contact['email'] ?? '—' }}</span></p>
                        <p><strong>الهاتف:</strong> <span dir="ltr">{{ $contact['phone'] ?? '—' }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>العنوان:</strong> {{ $contact['address1'] ?? '—' }}</p>
                        @if(!empty($contact['address2']))<p>{{ $contact['address2'] }}</p>@endif
                        <p>{{ ($contact['city'] ?? '') }}, {{ ($contact['state'] ?? '') }} {{ ($contact['zip'] ?? '') }}</p>
                        <p><strong>الدولة:</strong> {{ $contact['country'] ?? '—' }}</p>
                    </div>
                </div>
            </div>
            @endif
        @endforeach

        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">سجلات DNS</div></div>
            <div class="table-responsive">
                @if(!empty($dnsError))
                <div class="card-body"><div class="alert alert-warning mb-0">{{ $dnsError }}</div></div>
                @endif
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>النوع</th><th>المضيف</th><th>القيمة</th><th>TTL</th></tr>
                    </thead>
                    <tbody>
                    @forelse($dnsRecords as $r)
                        <tr>
                            <td><span class="badge bg-secondary-transparent">{{ $r['type'] ?? '—' }}</span></td>
                            <td dir="ltr">{{ $r['host'] ?? $r['fqdn'] ?? '—' }}</td>
                            <td dir="ltr" class="small text-break">{{ $r['answer'] ?? $r['data'] ?? '—' }}</td>
                            <td>{{ $r['ttl'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد سجلات DNS أو لا صلاحية لعرضها.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <a class="card-title text-decoration-none" data-bs-toggle="collapse" href="#rawJson" role="button">بيانات API خام (تشخيص)</a>
            </div>
            <div class="collapse" id="rawJson">
                <div class="card-body">
                    <pre class="mb-0 small" dir="ltr" style="max-height:400px;overflow:auto;">{{ json_encode($domain, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
