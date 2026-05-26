@extends('admin.layouts.master')
@section('page-title') تثبيت {{ $item['name_ar'] }} @stop
@section('content')
@include('admin.coolify.catalog.partials.flow-styles')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.partials.alerts')

        @include('admin.coolify.catalog.partials.hero', [
            'item' => $item,
            'backUrl' => route('admin.coolify.catalog.show', $slug),
            'backLabel' => 'العودة للتفاصيل',
        ])

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="catalog-panel">
                    <div class="catalog-panel__body">
                        @include('admin.coolify.catalog.partials.stepper', ['currentStep' => $step])

                        @if($step === 1)
                        <div class="catalog-panel__head border-0 bg-transparent px-0 pt-0">
                            <div class="catalog-panel__head-icon"><i class="fe fe-shield"></i></div>
                            <div>
                                <div class="fw-semibold">تأكد من المتطلبات</div>
                                <div class="text-muted small">يجب توفر البنود التالية قبل المتابعة</div>
                            </div>
                        </div>
                        <ul class="catalog-checklist mb-0">
                            @foreach($item['requirements'] ?? ['سيرفر Coolify متصل'] as $req)
                            <li>
                                <span class="catalog-checklist__icon"><i class="fe fe-check"></i></span>
                                <span>{{ $req }}</span>
                            </li>
                            @endforeach
                        </ul>
                        <div class="catalog-wizard-actions">
                            <a href="{{ route('admin.coolify.catalog.install', ['slug' => $slug, 'step' => 2]) }}" class="btn btn-primary catalog-btn-next">
                                التالي <i class="fe fe-arrow-left ms-1"></i>
                            </a>
                        </div>

                        @elseif($step === 2)
                        <div class="catalog-panel__head border-0 bg-transparent px-0 pt-0">
                            <div class="catalog-panel__head-icon"><i class="fe fe-server"></i></div>
                            <div>
                                <div class="fw-semibold">اختر الوجهة على Coolify</div>
                                <div class="text-muted small">المشروع، السيرفر، وبيئة التشغيل</div>
                            </div>
                        </div>
                        <form method="GET" action="{{ route('admin.coolify.catalog.install', $slug) }}">
                            <input type="hidden" name="step" value="3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="catalog-form-field">
                                        <label><i class="fe fe-folder"></i> المشروع *</label>
                                        <select name="project_uuid" class="form-control" required>
                                            @forelse($projects as $p)
                                            <option value="{{ $p['uuid'] ?? '' }}">{{ $p['name'] ?? $p['uuid'] }}</option>
                                            @empty
                                            <option value="">لا توجد مشاريع</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="catalog-form-field">
                                        <label><i class="fe fe-hard-drive"></i> السيرفر *</label>
                                        <select name="server_uuid" class="form-control" required>
                                            @forelse($servers as $s)
                                            <option value="{{ $s['uuid'] ?? '' }}">{{ $s['name'] ?? $s['uuid'] }}</option>
                                            @empty
                                            <option value="">لا توجد سيرفرات</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="catalog-form-field">
                                        <label><i class="fe fe-layers"></i> البيئة *</label>
                                        <input type="text" name="environment_name" class="form-control" value="production" required>
                                    </div>
                                </div>
                            </div>
                            <div class="catalog-wizard-actions">
                                <a href="{{ route('admin.coolify.catalog.install', ['slug' => $slug, 'step' => 1]) }}" class="btn btn-light">
                                    <i class="fe fe-arrow-right me-1"></i> السابق
                                </a>
                                <button type="submit" class="btn btn-primary catalog-btn-next">
                                    التالي <i class="fe fe-arrow-left ms-1"></i>
                                </button>
                            </div>
                        </form>

                        @else
                        <div class="catalog-panel__head border-0 bg-transparent px-0 pt-0">
                            <div class="catalog-panel__head-icon"><i class="fe fe-check-circle"></i></div>
                            <div>
                                <div class="fw-semibold">التأكيد والإنشاء</div>
                                <div class="text-muted small">راجع الإعدادات ثم أنشئ المورد على Coolify</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.coolify.catalog.install.store', $slug) }}">
                            @csrf
                            <input type="hidden" name="project_uuid" value="{{ request('project_uuid') }}">
                            <input type="hidden" name="server_uuid" value="{{ request('server_uuid') }}">
                            <input type="hidden" name="environment_name" value="{{ request('environment_name', 'production') }}">

                            <div class="catalog-summary-box">
                                <div class="small fw-semibold text-primary mb-2"><i class="fe fe-info"></i> ملخص الوجهة</div>
                                <div class="catalog-summary-row">
                                    <span>المشروع</span>
                                    <code title="{{ request('project_uuid') }}">{{ request('project_uuid') }}</code>
                                </div>
                                <div class="catalog-summary-row">
                                    <span>السيرفر</span>
                                    <code title="{{ request('server_uuid') }}">{{ request('server_uuid') }}</code>
                                </div>
                                <div class="catalog-summary-row">
                                    <span>البيئة</span>
                                    <code>{{ request('environment_name', 'production') }}</code>
                                </div>
                            </div>

                            <div class="catalog-form-field">
                                <label><i class="fe fe-tag"></i> اسم المورد *</label>
                                <input type="text" name="name" class="form-control" required
                                    value="{{ old('name', $item['coolify_key'] ?? 'resource') }}"
                                    placeholder="مثال: {{ $item['coolify_key'] }}-prod">
                            </div>
                            <div class="catalog-form-field">
                                <label><i class="fe fe-align-right"></i> وصف (اختياري)</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="وصف مختصر للمورد">{{ old('description') }}</textarea>
                            </div>
                            <div class="catalog-form-field mb-0">
                                <label><i class="fe fe-code"></i> JSON إضافي (اختياري)</label>
                                <textarea name="extra_payload" class="form-control font-monospace" rows="5" dir="ltr"
                                    placeholder='{"git_repository":"https://github.com/org/repo","git_branch":"main"}'>{{ old('extra_payload') }}</textarea>
                                <div class="form-text">إعدادات إضافية يدعمها Coolify لهذا النوع (خاصة للتطبيقات).</div>
                            </div>

                            <div class="catalog-wizard-actions">
                                <a href="{{ route('admin.coolify.catalog.install', array_filter(['slug' => $slug, 'step' => 2, 'project_uuid' => request('project_uuid'), 'server_uuid' => request('server_uuid'), 'environment_name' => request('environment_name')])) }}" class="btn btn-light">
                                    <i class="fe fe-arrow-right me-1"></i> السابق
                                </a>
                                <button type="submit" class="btn btn-success catalog-btn-create">
                                    <i class="fe fe-check me-1"></i> إنشاء على Coolify
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @include('admin.coolify.catalog.partials.sidebar-resource', ['item' => $item])
            </div>
        </div>
    </div>
</div>
@endsection
