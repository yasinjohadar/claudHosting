@extends('admin.layouts.master')
@section('page-title') فرق Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">فرق Coolify</h4>
                <p class="text-muted small mb-0">فريق لكل عميل — العزل عبر توكن API مقيّد بالفريق</p>
            </div>
            <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-sm btn-outline-secondary">إعدادات Coolify</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($error)<div class="alert alert-warning">{{ $error }}</div>@endif

        <div class="row mb-4 g-3">
            <div class="col-lg-5">
                <div class="card custom-card h-100 border-info">
                    <div class="card-header"><div class="card-title mb-0">دليل الإعداد في Coolify</div></div>
                    <div class="card-body small">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">في Coolify: <strong>Teams</strong> → أنشئ فريقاً باسم العميل.</li>
                            <li class="mb-2"><strong>Keys &amp; Tokens → API tokens</strong> → توكن بصلاحية <code>*</code> مقيّد <strong>بهذا الفريق</strong> فقط.</li>
                            <li class="mb-2">أنشئ المشروع وأنت على ذلك الفريق (أو بتوكن الفريق عبر API).</li>
                            <li>اربط العميل أدناه: معرّف الفريق + التوكن (اختياري للتحقق).</li>
                        </ol>
                        <p class="text-muted mb-0 mt-3">العملاء يستخدمون لوحة <code>/client</code> فقط — لا حاجة لحساب Coolify لهم.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card custom-card h-100">
                    <div class="card-header"><div class="card-title mb-0">الفريق الحالي (توكن اللوحة)</div></div>
                    <div class="card-body">
                        @if(!empty($current))
                            <div><strong>{{ $current['name'] ?? '—' }}</strong> <span class="text-muted">#{{ $current['id'] ?? '' }}</span></div>
                            @if(!empty($current['description']))<p class="small text-muted mb-0">{{ $current['description'] }}</p>@endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title mb-0">ربط عميل بفريق</div></div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.coolify.teams.link-client') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">العميل (مستخدم النظام)</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— اختر —</option>
                            @foreach($clientUsers as $u)
                                <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">معرّف الفريق</label>
                        <input type="number" name="coolify_team_id" class="form-control" min="1" value="{{ old('coolify_team_id') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">اسم الفريق (اختياري)</label>
                        <input type="text" name="team_name" class="form-control" value="{{ old('team_name') }}" maxlength="255">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">توكن API للفريق</label>
                        <input type="password" name="api_token" class="form-control" autocomplete="new-password" placeholder="يُتحقق منه عند الحفظ">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">ربط</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title mb-0">كل الفرق (حساب Coolify للتوكن الرئيسي)</div></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>شخصي</th>
                            <th>العميل المرتبط</th>
                            <th>توكن الفريق</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teams as $team)
                            @php
                                $tid = (int) ($team['id'] ?? 0);
                                $link = $team['_link'] ?? null;
                                $client = $team['_client'] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $tid }}</td>
                                <td>{{ $team['name'] ?? '—' }}</td>
                                <td>
                                    @if($team['personal_team'] ?? false)
                                        <span class="badge bg-secondary-transparent">نعم</span>
                                    @else
                                        <span class="text-muted">لا</span>
                                    @endif
                                </td>
                                <td>
                                    @if($client)
                                        <a href="{{ route('admin.customers.show', $client->id) }}">{{ $client->name }}</a>
                                        <span class="small text-muted d-block" dir="ltr">{{ $client->email }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($team['_has_token'] ?? false)
                                        <span class="badge bg-success-transparent">مضبوط</span>
                                    @elseif($link)
                                        <span class="badge bg-warning-transparent">بدون توكن</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('admin.coolify.teams.show', $tid) }}" class="btn btn-sm btn-primary-light">تفاصيل</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا فرق أو فشل الاتصال</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

