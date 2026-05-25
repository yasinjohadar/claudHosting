@extends('admin.layouts.master')
@section('page-title') جداول لقطات المشاريع @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'schedules'])
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-1">جداول اللقطات الدورية</h4>
                <p class="text-muted small mb-0">تشغيل تلقائي عبر الأمر <code>coolify:run-scheduled-snapshots</code> (كل ساعة)</p>
            </div>
            <a href="{{ route('admin.coolify.backups.schedules.create') }}" class="btn btn-primary btn-sm">
                <i class="fe fe-plus"></i> جدولة جديدة
            </a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>المشروع</th>
                            <th>التكرار</th>
                            <th>الحالة</th>
                            <th>آخر تشغيل</th>
                            <th>التشغيل القادم</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($schedules as $schedule)
                        <tr>
                            <td class="fw-semibold">{{ $schedule->name }}</td>
                            <td>{{ $schedule->project_name ?? $schedule->project_uuid }}</td>
                            <td>{{ ['hourly' => 'كل ساعة', 'daily' => 'يومي', 'weekly' => 'أسبوعي', 'monthly' => 'شهري'][$schedule->frequency] ?? $schedule->frequency }}</td>
                            <td>
                                <span class="badge {{ $schedule->enabled ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $schedule->enabled ? 'مفعّل' : 'معطّل' }}
                                </span>
                            </td>
                            <td class="small">{{ $schedule->last_run_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="small">{{ $schedule->next_run_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-nowrap">
                                <form method="POST" action="{{ route('admin.coolify.backups.schedules.run', $schedule->uuid) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="تشغيل الآن"><i class="fe fe-play"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.coolify.backups.schedules.toggle', $schedule->uuid) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="fe fe-power"></i></button>
                                </form>
                                <a href="{{ route('admin.coolify.backups.schedules.edit', $schedule->uuid) }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-edit"></i></a>
                                @include('admin.coolify.partials.delete-form', [
                                    'action' => route('admin.coolify.backups.schedules.destroy', $schedule->uuid),
                                    'class' => 'd-inline',
                                    'buttonClass' => 'btn btn-sm btn-outline-danger',
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">لا توجد جداول — أنشئ جدولة أو فعّل «جدولة دورية» من معالج اللقطة</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($schedules->hasPages())
                <div class="card-footer">{{ $schedules->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

