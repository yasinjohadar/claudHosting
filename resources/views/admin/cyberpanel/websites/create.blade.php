@extends('admin.layouts.master')
@section('page-title') إضافة موقع CyberPanel @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة موقع</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cyberpanel.websites.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">النطاق *</label>
                            <input type="text" name="domain" class="form-control" dir="ltr" required value="{{ old('domain') }}" placeholder="client.example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الباقة *</label>
                            @if(!empty($packages))
                            <select name="package" class="form-select" required>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg }}" @selected(old('package', $defaultPackage) === $pkg)>{{ $pkg }}</option>
                                @endforeach
                            </select>
                            @else
                            <input type="text" name="package" class="form-control" required value="{{ old('package', $defaultPackage) }}">
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المالك</label>
                            <input type="text" name="owner" class="form-control" dir="ltr" value="{{ old('owner', $defaultOwner) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">البريد</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PHP</label>
                            <input type="text" name="php_version" class="form-control" dir="ltr" value="{{ old('php_version', $defaultPhp) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة مرور المالك (اختياري)</label>
                            <input type="password" name="owner_password" class="form-control" placeholder="يُولَّد تلقائياً إن تُرك فارغاً">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ربط عميل</label>
                            <select name="user_id" class="form-select">
                                <option value="">—</option>
                                @foreach($clientUsers as $u)
                                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="install_wordpress" value="1" class="form-check-input" id="install_wp" @checked(old('install_wordpress'))>
                                <label class="form-check-label" for="install_wp">تثبيت WordPress بعد الإنشاء</label>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="ssl" value="1" class="form-check-input" id="ssl" @checked(old('ssl'))>
                                <label class="form-check-label" for="ssl">تفعيل SSL عند الإنشاء</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">إنشاء</button>
                        <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
