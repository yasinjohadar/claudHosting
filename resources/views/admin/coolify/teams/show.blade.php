@extends('admin.layouts.master')
@section('page-title') فريق Coolify #{{ $teamId }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">{{ $team['name'] ?? 'فريق' }} <span class="text-muted">#{{ $teamId }}</span></h4>
                @if(!empty($team['description']))<p class="text-muted small mb-0">{{ $team['description'] }}</p>@endif
            </div>
            <a href="{{ route('admin.coolify.teams.index') }}" class="btn btn-sm btn-light">رجوع للفرق</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card custom-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">العميل المرتبط</span>
                        @if($link?->client)
                            <a href="{{ route('admin.customers.show', $link->client->id) }}" class="btn btn-sm btn-outline-primary">ملف العميل</a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($link?->client)
                            <p class="mb-1"><strong>{{ $link->client->name }}</strong></p>
                            <p class="small text-muted mb-2" dir="ltr">{{ $link->client->email }}</p>
                            <p class="mb-2">
                                توكن الفريق:
                                @if($link->hasApiToken())
                                    <span class="badge bg-success-transparent">مضبوط</span>
                                @else
                                    <span class="badge bg-warning-transparent">غير مضبوط</span>
                                @endif
                            </p>
                            <form method="post" action="{{ route('admin.coolify.teams.unlink', $link->client) }}" onsubmit="return confirm('إلغاء الربط المحلي فقط — الفريق يبقى في Coolify؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء الربط</button>
                            </form>
                        @else
                            <p class="text-muted mb-0">لا يوجد عميل مربوط بهذا الفريق.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card custom-card h-100">
                    <div class="card-header"><span class="fw-semibold">أعضاء Coolify (مرجعي)</span></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @forelse($members as $m)
                                    <tr>
                                        <td>{{ $m['name'] ?? '—' }}</td>
                                        <td dir="ltr" class="small text-muted">{{ $m['email'] ?? '' }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-muted">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(!$link?->client)
        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title mb-0">ربط عميل بهذا الفريق</div></div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.coolify.teams.link-client') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="coolify_team_id" value="{{ $teamId }}">
                    <input type="hidden" name="team_name" value="{{ $team['name'] ?? '' }}">
                    <input type="hidden" name="_return" value="{{ url()->current() }}">
                    <div class="col-md-5">
                        <label class="form-label">العميل</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach($clientUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">توكن API للفريق</label>
                        <input type="password" name="api_token" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">ربط</button>
                    </div>
                </form>
            </div>
        </div>
        @elseif(!$link->hasApiToken())
        <div class="card custom-card mb-4 border-warning">
            <div class="card-header"><div class="card-title mb-0">إضافة/تحديث توكن الفريق</div></div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.coolify.teams.link-client') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $link->user_id }}">
                    <input type="hidden" name="coolify_team_id" value="{{ $teamId }}">
                    <input type="hidden" name="team_name" value="{{ $link->team_name ?? $team['name'] ?? '' }}">
                    <input type="hidden" name="_return" value="{{ url()->current() }}">
                    <div class="col-md-10">
                        <input type="password" name="api_token" class="form-control" placeholder="توكن API مقيّد بهذا الفريق" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100">حفظ التوكن</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">مشاريع الفريق</span>
                @if($link?->client)
                    <a href="{{ route('admin.coolify.projects.index', ['user_id' => $link->client->id]) }}" class="btn btn-sm btn-outline-secondary">ربط مشاريع</a>
                @endif
            </div>
            @if($projectsError)<div class="alert alert-warning m-3 mb-0">{{ $projectsError }}</div>@endif
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الاسم</th>
                            <th>UUID</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $proj)
                            @php $uuid = $proj['uuid'] ?? ''; @endphp
                            <tr>
                                <td>{{ $proj['name'] ?? '—' }}</td>
                                <td><code class="small" dir="ltr">{{ $uuid }}</code></td>
                                <td class="text-center">
                                    @if($uuid !== '')
                                        <a href="{{ route('admin.coolify.projects.show', $uuid) }}" class="btn btn-sm btn-primary-light">عرض</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">لا مشاريع أو التوكن غير مضبوط</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

