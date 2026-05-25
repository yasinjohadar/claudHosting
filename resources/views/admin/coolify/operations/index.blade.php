@extends('admin.layouts.master')
@section('page-title') عمليات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <h4 class="mb-0">مركز العمليات</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.coolify.readiness.index') }}" class="btn btn-outline-primary btn-sm">معالج الجاهزية</a>
                <a href="{{ route('admin.coolify.operations.index', ['refresh_ssh' => 1]) }}" class="btn btn-outline-secondary btn-sm">تحديث SSH</a>
                <form method="POST" action="{{ route('admin.coolify.operations.check-alerts') }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">فحص التنبيهات</button>
                </form>
                <a href="{{ route('admin.coolify.overview') }}" class="btn btn-outline-secondary btn-sm">لوحة Coolify</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        @php $r = $readiness ?? ['ready' => false, 'checks' => []]; @endphp
        <div class="card custom-card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span class="card-title mb-0">جاهزية الاستضافة</span>
                <span class="badge {{ ($r['ready'] ?? false) ? 'bg-success' : 'bg-danger' }}">
                    {{ ($r['ready'] ?? false) ? 'جاهز' : 'يحتاج إصلاح' }}
                </span>
            </div>
            <div class="card-body py-2">
                <ul class="list-unstyled mb-0 small">
                    @foreach($r['checks'] ?? [] as $check)
                    <li class="mb-1">
                        <i class="fe fe-{{ ($check['ok'] ?? false) ? 'check-circle text-success' : 'x-circle text-danger' }}"></i>
                        <strong>{{ $check['label'] ?? '' }}:</strong> {{ $check['message'] ?? '' }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if(!($ops['connected'] ?? false))
        <div class="alert alert-warning">Coolify API غير متصل — <a href="{{ route('admin.coolify.settings.index') }}">الإعدادات</a></div>
        @else
        @include('admin.coolify.partials.metrics-operations')
        <div class="row mb-3">
            @foreach(['servers' => 'سيرفرات', 'projects' => 'مشاريع', 'applications' => 'تطبيقات', 'services' => 'خدمات', 'databases' => 'قواعد بيانات'] as $k => $label)
            <div class="col-6 col-md-4 col-lg-2 mb-2">
                <div class="card custom-card mb-0 text-center py-2">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-4 fw-semibold">{{ $ops['stats'][$k] ?? 0 }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><span class="card-title">موارد غير سليمة</span></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            @forelse($ops['unhealthy_resources'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['type_label'] }}</td>
                                <td><a href="{{ $row['url'] }}">{{ $row['name'] }}</a></td>
                                <td><code class="small">{{ $row['status'] }}</code></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">لا توجد مشاكل ظاهرة</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><span class="card-title">نشرات قيد التشغيل / فاشلة</span></div>
                    <div class="card-body p-0">
                        @if(!empty($ops['running_deployments']))
                        <p class="small text-muted px-3 pt-2 mb-1">قيد التشغيل</p>
                        <table class="table table-sm mb-2">
                            @foreach($ops['running_deployments'] as $d)
                            <tr>
                                <td>{{ $d['application_name'] }}</td>
                                <td><code>{{ $d['status'] }}</code></td>
                                <td><a href="{{ route('admin.coolify.deployments.index') }}" class="btn btn-xs btn-outline-secondary btn-sm">النشرات</a></td>
                            </tr>
                            @endforeach
                        </table>
                        @endif
                        @if(!empty($ops['failed_deployments']))
                        <p class="small text-muted px-3 mb-1">فاشلة</p>
                        <table class="table table-sm mb-0">
                            @foreach($ops['failed_deployments'] as $d)
                            <tr>
                                <td>{{ $d['application_name'] }}</td>
                                <td><code>{{ $d['status'] }}</code></td>
                            </tr>
                            @endforeach
                        </table>
                        @else
                        @if(empty($ops['running_deployments']))
                        <p class="text-muted text-center py-3 mb-0">لا نشرات نشطة أو فاشلة حديثاً</p>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><span class="card-title">مواقع WordPress</span></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            @forelse($ops['wordpress_issues'] ?? [] as $wp)
                            <tr>
                                <td><a href="{{ $wp['url'] }}">{{ $wp['name'] }}</a></td>
                                <td>{{ $wp['status_label'] }}</td>
                                <td class="small text-muted">{{ Str::limit($wp['error'] ?? '', 40) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">لا مواقع معلّقة أو فاشلة</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card custom-card h-100">
                    <div class="card-header"><span class="card-title">لقطات / نسخ فاشلة</span></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            @forelse($ops['failed_snapshots'] ?? [] as $snap)
                            <tr>
                                <td><a href="{{ $snap['url'] }}">{{ $snap['name'] }}</a></td>
                                <td>{{ $snap['status_label'] }}</td>
                                <td class="small">{{ $snap['completed_at'] ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">لا لقطات فاشلة</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="card custom-card">
                    <div class="card-header"><span class="card-title">حالة SSH للسيرفرات</span></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>السيرفر</th><th>المضيف</th><th>الحالة</th><th></th></tr></thead>
                            <tbody>
                            @forelse($ops['ssh_statuses'] ?? [] as $ssh)
                            <tr>
                                <td>{{ $ssh['name'] }}</td>
                                <td><code>{{ $ssh['host'] }}</code></td>
                                <td>
                                    @if($ssh['ok'])
                                    <span class="text-success">متصل</span>
                                    @else
                                    <span class="text-danger">{{ $ssh['message'] }}</span>
                                    @endif
                                </td>
                                <td>@if($ssh['url'])<a href="{{ $ssh['url'] }}" class="btn btn-sm btn-outline-primary">عرض</a>@endif</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">لا سيرفرات أو لم يُفحص SSH</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

