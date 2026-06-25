@extends('admin.layouts.master')
@section('page-title') باقات CyberPanel @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4 class="mb-0">باقات CyberPanel</h4>
            <a href="{{ route('admin.cyberpanel.settings.index') }}" class="btn btn-outline-secondary btn-sm">الإعدادات</a>
        </div>
        @include('admin.coolify.partials.alerts')
        @if($error)<div class="alert alert-danger">{{ $error }}</div>@endif
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title mb-0">الباقات على السيرفر</div></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>الاسم</th><th>تفاصيل</th></tr></thead>
                            <tbody>
                            @forelse($packages as $pkg)
                                @php
                                    $name = is_array($pkg) ? ($pkg['packageName'] ?? $pkg['name'] ?? $pkg['package'] ?? '—') : (string) $pkg;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $name }}</td>
                                    <td class="small text-muted">
                                        @if(is_array($pkg))
                                            {{ ($pkg['diskSpace'] ?? '—') }} MB قرص · {{ ($pkg['bandwidth'] ?? '—') }} MB ترافيك
                                        @else — @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">لا باقات أو فشل الجلب</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title mb-0">إنشاء باقة</div></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.cyberpanel.packages.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">اسم الباقة</label>
                                <input type="text" name="packageName" class="form-control" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label small">قرص MB</label><input type="number" name="diskSpace" class="form-control form-control-sm" value="1000" required></div>
                                <div class="col-6"><label class="form-label small">ترافيك MB</label><input type="number" name="bandwidth" class="form-control form-control-sm" value="10000" required></div>
                                <div class="col-6"><label class="form-label small">بريد</label><input type="number" name="emailAccounts" class="form-control form-control-sm" value="100" required></div>
                                <div class="col-6"><label class="form-label small">قواعد بيانات</label><input type="number" name="dataBases" class="form-control form-control-sm" value="100" required></div>
                                <div class="col-6"><label class="form-label small">FTP</label><input type="number" name="ftpAccounts" class="form-control form-control-sm" value="100" required></div>
                                <div class="col-6"><label class="form-label small">نطاقات فرعية</label><input type="number" name="allowedDomains" class="form-control form-control-sm" value="100" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3" @disabled(!($configured ?? false))>إنشاء</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
