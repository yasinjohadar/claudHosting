@extends('admin.layouts.master')

@section('page-title')
إدارة الهيرو
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إدارة الهيرو — الصفحة الرئيسية</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">الهيرو</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> معاينة الموقع
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.homepage.hero.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card custom-card mb-4">
                <div class="card-body py-3">
                    <input type="hidden" name="enabled" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="hero_enabled" @checked(old('enabled', $hero['enabled'] ?? true))>
                        <label class="form-check-label" for="hero_enabled">تفعيل قسم الهيرو في الصفحة الرئيسية</label>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab">المحتوى</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-light" type="button" role="tab">الوضع النهاري</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dark" type="button" role="tab">الوضع الليلي</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-actions" type="button" role="tab">الأزرار والإحصائيات</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">بادئة العنوان</label>
                                <input type="text" name="content[title_prefix]" class="form-control" value="{{ old('content.title_prefix', $hero['content']['title_prefix'] ?? '') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نصوص الكتابة المتحركة</label>
                                <textarea name="content[typing_texts]" class="form-control" rows="4" placeholder="سطر لكل عبارة أو افصل بـ |">{{ old('content.typing_texts', is_array($hero['content']['typing_texts'] ?? null) ? implode("\n", $hero['content']['typing_texts']) : '') }}</textarea>
                                <div class="form-text">مثال: استضافة كلاودسوفت (سطر جديد لكل عبارة)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea name="content[subtitle]" class="form-control" rows="4" required>{{ old('content.subtitle', $hero['content']['subtitle'] ?? '') }}</textarea>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">نص بديل للصورة (alt)</label>
                                <input type="text" name="content[image_alt]" class="form-control" value="{{ old('content.image_alt', $hero['content']['image_alt'] ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>

                @include('admin.homepage.hero.partials.theme-tab', ['themeKey' => 'light', 'themeLabel' => 'الوضع النهاري', 'hero' => $hero])
                @include('admin.homepage.hero.partials.theme-tab', ['themeKey' => 'dark', 'themeLabel' => 'الوضع الليلي', 'hero' => $hero])

                <div class="tab-pane fade" id="tab-actions" role="tabpanel">
                    <div class="card custom-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="card-title mb-0">الأزرار</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-hero-button">
                                <i class="fas fa-plus"></i> إضافة زر
                            </button>
                        </div>
                        <div class="card-body" id="hero-buttons-list">
                            @php $buttons = old('content.buttons', $hero['content']['buttons'] ?? []); @endphp
                            @forelse ($buttons as $i => $btn)
                                @include('admin.homepage.hero.partials.button-row', ['index' => $i, 'btn' => $btn])
                            @empty
                                @include('admin.homepage.hero.partials.button-row', ['index' => 0, 'btn' => []])
                            @endforelse
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="card-title mb-0">الإحصائيات</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-hero-stat">
                                <i class="fas fa-plus"></i> إضافة إحصائية
                            </button>
                        </div>
                        <div class="card-body" id="hero-stats-list">
                            @php $stats = old('content.stats', $hero['content']['stats'] ?? []); @endphp
                            @forelse ($stats as $i => $stat)
                                @include('admin.homepage.hero.partials.stat-row', ['index' => $i, 'stat' => $stat])
                            @empty
                                @include('admin.homepage.hero.partials.stat-row', ['index' => 0, 'stat' => []])
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> حفظ إعدادات الهيرو
                </button>
            </div>
        </form>
    </div>
</div>

<template id="hero-button-template">
    @include('admin.homepage.hero.partials.button-row', ['index' => '__INDEX__', 'btn' => []])
</template>
<template id="hero-stat-template">
    @include('admin.homepage.hero.partials.stat-row', ['index' => '__INDEX__', 'stat' => []])
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleBgFields(theme) {
        var mode = document.querySelector('select.hero-bg-mode[data-theme="' + theme + '"]');
        if (!mode) return;
        var val = mode.value;
        ['color', 'gradient', 'image'].forEach(function (type) {
            var el = document.querySelector('.hero-bg-' + type + '-' + theme);
            if (el) el.style.display = (val === type) ? 'block' : 'none';
        });
    }

    document.querySelectorAll('.hero-bg-mode').forEach(function (sel) {
        toggleBgFields(sel.dataset.theme);
        sel.addEventListener('change', function () { toggleBgFields(sel.dataset.theme); });
    });

    var btnIndex = document.querySelectorAll('#hero-buttons-list .hero-repeater-row').length;
    document.getElementById('add-hero-button').addEventListener('click', function () {
        var tpl = document.getElementById('hero-button-template').innerHTML.replace(/__INDEX__/g, btnIndex++);
        document.getElementById('hero-buttons-list').insertAdjacentHTML('beforeend', tpl);
    });

    var statIndex = document.querySelectorAll('#hero-stats-list .hero-repeater-row').length;
    document.getElementById('add-hero-stat').addEventListener('click', function () {
        var tpl = document.getElementById('hero-stat-template').innerHTML.replace(/__INDEX__/g, statIndex++);
        document.getElementById('hero-stats-list').insertAdjacentHTML('beforeend', tpl);
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.hero-remove-row')) {
            e.preventDefault();
            var row = e.target.closest('.hero-repeater-row');
            if (row && row.parentElement.querySelectorAll('.hero-repeater-row').length > 1) {
                row.remove();
            }
        }
    });
});
</script>
@endpush
