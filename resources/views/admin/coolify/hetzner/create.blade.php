@extends('admin.layouts.master')
@section('page-title') سيرفر Hetzner @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إنشاء سيرفر Hetzner</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.hetzner.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                <div class="mb-3"><label class="form-label">Cloud Token *</label>
                    <select name="cloud_token_uuid" class="form-control" required>
                        <option value="">—</option>
                        @foreach($cloudTokens as $t)
                        <option value="{{ $t['uuid'] ?? '' }}" @selected(old('cloud_token_uuid') == ($t['uuid'] ?? ''))>{{ $t['name'] ?? $t['uuid'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">الموقع *</label>
                    <select name="location" class="form-control" required>
                        @forelse($locations as $l)
                        @php $v = \App\Services\CoolifyApiService::hetznerOptionValue($l); @endphp
                        <option value="{{ $v }}" @selected(old('location') == $v)>{{ \App\Services\CoolifyApiService::hetznerOptionLabel($l) }}</option>
                        @empty
                        <option value="" disabled>لا توجد مواقع — أضف Cloud Token أولاً</option>
                        @endforelse
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">نوع السيرفر *</label>
                    <select name="server_type" class="form-control" required>
                        @foreach($serverTypes as $s)
                        @php $v = \App\Services\CoolifyApiService::hetznerOptionValue($s); @endphp
                        <option value="{{ $v }}" @selected(old('server_type') == $v)>{{ \App\Services\CoolifyApiService::hetznerOptionLabel($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">الصورة *</label>
                    <select name="image" class="form-control" required>
                        @foreach($images as $i)
                        @php $v = \App\Services\CoolifyApiService::hetznerOptionValue($i); @endphp
                        <option value="{{ $v }}" @selected(old('image') == $v)>{{ \App\Services\CoolifyApiService::hetznerOptionLabel($i) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(!empty($sshKeys))
                <div class="mb-3"><label class="form-label">مفتاح SSH</label>
                    <select name="ssh_key_uuid" class="form-control">
                        <option value="">— اختياري —</option>
                        @foreach($sshKeys as $k)
                        <option value="{{ $k['uuid'] ?? $k['id'] ?? '' }}">{{ $k['name'] ?? $k['uuid'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <button type="submit" class="btn btn-primary">إنشاء</button>
                <a href="{{ route('admin.coolify.servers.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection

