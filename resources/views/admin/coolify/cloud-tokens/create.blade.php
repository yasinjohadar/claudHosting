@extends('admin.layouts.master')
@section('page-title') إضافة Cloud Token @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة توكن سحابة</h4>
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.cloud-tokens.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">المزود *</label>
                    <select name="provider" class="form-control" required>
                        <option value="hetzner">Hetzner</option>
                        <option value="digitalocean">DigitalOcean</option>
                        <option value="aws">AWS</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">التوكن *</label><input type="password" name="token" class="form-control" required dir="ltr"></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
