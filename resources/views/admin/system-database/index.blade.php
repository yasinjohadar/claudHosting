@extends('admin.layouts.master')
@section('page-title') قاعدة بيانات النظام @stop
@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.system-database.partials.styles')
@endpush
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="sysdb-hero mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">مستكشف قاعدة بيانات النظام</h4>
                    <p class="text-muted mb-2 mb-md-0 small">بنية الجداول، الأحجام، الأعمدة، الفهارس، والمفاتيح الأجنبية — بدون عرض بيانات الصفوف</p>
                    @if($overview)
                    <div class="d-flex flex-wrap gap-2">
                        <span class="sysdb-pill">
                            <i class="fe fe-database"></i>
                            <span dir="ltr">{{ $overview['database'] }}</span>
                        </span>
                        @if($overview['host'] ?? null)
                        <span class="sysdb-pill" dir="ltr">{{ $overview['host'] }}:{{ $overview['port'] }}</span>
                        @endif
                        @if($overview['charset'] ?? null)
                        <span class="sysdb-pill">{{ $overview['charset'] }}</span>
                        @endif
                        @if($overview['cached_at'] ?? null)
                        <span class="sysdb-pill text-muted">
                            <i class="fe fe-clock"></i>
                            {{ \Carbon\Carbon::parse($overview['cached_at'])->diffForHumans() }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0">الاتصال</label>
                        <select name="connection" id="sysdbConnectionSelect" class="form-select form-select-sm" style="min-width:140px;" dir="ltr">
                            @foreach($connections as $conn)
                            <option value="{{ $conn['name'] }}" {{ $connection === $conn['name'] ? 'selected' : '' }}>
                                {{ $conn['name'] }} ({{ $conn['driver'] }}){{ $conn['is_default'] ? ' *' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('admin.system-database.refresh') }}">
                        @csrf
                        <input type="hidden" name="connection" value="{{ $connection }}">
                        <button type="submit" class="btn btn-sm btn-light" title="مسح الكاش وإعادة القراءة">
                            <i class="fe fe-refresh-cw"></i> تحديث
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active">قاعدة بيانات النظام</li>
            </ol>
        </nav>

        @include('admin.coolify.partials.alerts')

        @if(request()->boolean('refreshed'))
        <div class="alert alert-success py-2 small">تم تحديث البيانات من قاعدة البيانات.</div>
        @endif

        @if($error)
        <div class="alert alert-danger">
            <strong><i class="fe fe-alert-triangle me-1"></i> تعذّر الاتصال</strong>
            <p class="mb-0 small mt-2">{{ $error }}</p>
            <p class="mb-0 small text-muted mt-2">تحقق من إعدادات الاتصال في <code>.env</code> وأن السيرفر يعمل.</p>
        </div>
        @else
            @include('admin.system-database.partials.summary-cards')
            @include('admin.system-database.partials.tables-list')
        @endif
    </div>
</div>

@include('admin.system-database.partials.table-detail-modal')
@endsection

@push('scripts')
@include('admin.system-database.partials.scripts')
@endpush
