@extends('admin.layouts.master')
@section('page-title') تعديل {{ $server->name }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل سيرفر VPS</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.infrastructure.servers.update', $server->uuid) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">الاسم المعروض</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $server->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ربط Coolify (اختياري)</label>
                        <select name="coolify_server_uuid" class="form-select">
                            <option value="">— بدون ربط —</option>
                            @foreach($coolifyServers as $cs)
                                @php $cu = $cs['uuid'] ?? $cs['id'] ?? ''; @endphp
                                <option value="{{ $cu }}" @selected(old('coolify_server_uuid', $server->coolify_server_uuid) === $cu)>
                                    {{ $cs['name'] ?? $cu }} ({{ $cs['ip'] ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">للانتقال السريع إلى metrics وتطبيقات Coolify على نفس العقدة.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                    <a href="{{ route('admin.infrastructure.servers.show', $server->uuid) }}" class="btn btn-light">رجوع</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
