@extends('admin.layouts.master')

@section('page-title')
قوالب الواتساب
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        @include('admin.components.alerts')

        <div class="my-4 page-header-breadcrumb d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp-messages.index') }}">رسائل واتساب</a></li>
                        <li class="breadcrumb-item active">القوالب</li>
                    </ol>
                </nav>
                <h1 class="page-title fw-semibold fs-20 mb-0 mt-2">قوالب الواتساب</h1>
                <p class="text-muted mb-0">نصوص جاهزة بمتغيرات، تُستخدم في الإرسال اليدوي والرسائل التلقائية.</p>
            </div>
            <a href="{{ route('admin.whatsapp-templates.create') }}" class="btn btn-primary">
                <i class="fe fe-plus me-1"></i> قالب جديد
            </a>
        </div>

        <div class="row g-3 mb-3">
            @foreach([
                ['label' => 'الإجمالي', 'value' => $stats['total'], 'icon' => 'fe fe-layers', 'tone' => 'primary'],
                ['label' => 'مفعّلة', 'value' => $stats['active'], 'icon' => 'fe fe-check-circle', 'tone' => 'success'],
                ['label' => 'معطّلة', 'value' => $stats['inactive'], 'icon' => 'fe fe-pause-circle', 'tone' => 'secondary'],
                ['label' => 'قوالب نظام', 'value' => $stats['system'], 'icon' => 'fe fe-shield', 'tone' => 'info'],
            ] as $card)
                <div class="col-6 col-lg-3">
                    <div class="card custom-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="avatar avatar-md bg-{{ $card['tone'] }}-transparent text-{{ $card['tone'] }} rounded-3">
                                <i class="{{ $card['icon'] }}"></i>
                            </span>
                            <div>
                                <span class="d-block text-muted small">{{ $card['label'] }}</span>
                                <span class="fs-18 fw-semibold">{{ $card['value'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card custom-card group-show-members-card">
            <div class="card-header border-0 pb-0">
                <form method="GET" class="row g-2 w-100 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">بحث</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            value="{{ request('search') }}" placeholder="اسم، معرّف، أو نص">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">التصنيف</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">كل التصنيفات</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">الحالة</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>مفعّلة</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>معطّلة</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-fill">بحث</button>
                        <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-sm btn-light">مسح</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الاسم</th>
                                <th>المعرّف</th>
                                <th>التصنيف</th>
                                <th>المتغيرات</th>
                                <th>الحالة</th>
                                <th class="text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $template->name }}</span>
                                    @if($template->description)
                                        <span class="text-muted small">{{ \Illuminate\Support\Str::limit($template->description, 70) }}</span>
                                    @endif
                                    @if($template->isProtected())
                                        <span class="badge bg-info-transparent text-info mt-1">قالب نظام</span>
                                    @endif
                                </td>
                                <td><code dir="ltr" class="small">{{ $template->slug ?: '—' }}</code></td>
                                <td><span class="text-muted small">{{ $template->categoryLabel() }}</span></td>
                                <td>
                                    @php $vars = $template->variables ?? []; @endphp
                                    @if($vars === [])
                                        <span class="text-muted small">بلا متغيرات</span>
                                    @else
                                        <span class="badge bg-primary-transparent text-primary" dir="ltr"
                                            title="{{ implode(', ', $vars) }}">{{ count($vars) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}-transparent text-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'مفعّل' : 'معطّل' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.whatsapp-templates.edit', $template) }}"
                                        class="btn btn-sm btn-outline-primary" title="تعديل"><i class="fe fe-edit-2"></i></a>
                                    @if($template->isProtected())
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                            title="قالب نظام — عطّله بدلاً من حذفه"><i class="fe fe-lock"></i></button>
                                    @else
                                        <form action="{{ route('admin.whatsapp-templates.destroy', $template) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('حذف القالب «{{ $template->name }}»؟');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fe fe-message-square fs-24 d-block mb-2"></i>
                                    لا توجد قوالب بعد. أنشئ الأول من الزر أعلى الصفحة.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($templates->hasPages())
                    <div class="mt-3">{{ $templates->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
