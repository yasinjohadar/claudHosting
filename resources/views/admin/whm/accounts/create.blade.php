@extends('admin.layouts.master')
@section('page-title') إنشاء حساب WHM @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إنشاء حساب cPanel عبر WHM</h4>
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.whm.accounts.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">اسم المستخدم cPanel *</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" dir="ltr"
                                value="{{ old('username') }}" pattern="[a-zA-Z][a-zA-Z0-9]{0,15}" maxlength="16" required>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">النطاق الرئيسي *</label>
                            <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror" dir="ltr"
                                value="{{ old('domain') }}" required>
                            @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">كلمة المرور *</label>
                            <input type="text" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">باقة WHM *</label>
                            @if(!empty($packages))
                                <select name="package" class="form-select" required>
                                    @foreach($packages as $pkg)
                                        @php $name = is_array($pkg) ? ($pkg['name'] ?? '') : (string) $pkg; @endphp
                                        @if($name !== '')
                                            <option value="{{ $name }}" @selected(old('package', $defaultPackage) === $name)>{{ $name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="package" class="form-control" value="{{ old('package', $defaultPackage) }}" required>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">بريد التواصل</label>
                            <input type="email" name="contactemail" class="form-control" value="{{ old('contactemail') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">العميل المسؤول (مستخدم النظام)</label>
                            <select name="user_id" class="form-select">
                                <option value="">—</option>
                                @foreach($clientUsers as $u)
                                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} — {{ $u->email }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">إنشاء في WHM</button>
                        <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
