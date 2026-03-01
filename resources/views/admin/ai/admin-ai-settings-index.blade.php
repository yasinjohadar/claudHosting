@extends('admin.layouts.master')

@section('page-title')
    إعدادات الذكاء الاصطناعي
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إعدادات الذكاء الاصطناعي</h5>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('admin.ai.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div id="settings-container">
                                @foreach($settings as $setting)
                                    <div class="mb-3">
                                        <label for="setting_{{ $setting->id }}" class="form-label">
                                            {{ $setting->key }}
                                            @if($setting->description)
                                                <small class="text-muted">({{ $setting->description }})</small>
                                            @endif
                                        </label>
                                        
                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="settings[{{ $setting->id }}][value]" 
                                                       value="1" 
                                                       id="setting_{{ $setting->id }}"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->id }}">
                                                    مفعّل
                                                </label>
                                            </div>
                                        @elseif($setting->type === 'json')
                                            <textarea class="form-control" 
                                                     name="settings[{{ $setting->id }}][value]" 
                                                     id="setting_{{ $setting->id }}" 
                                                     rows="3">{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $setting->value }}</textarea>
                                        @else
                                            <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" 
                                                  class="form-control" 
                                                  name="settings[{{ $setting->id }}][value]" 
                                                  id="setting_{{ $setting->id }}" 
                                                  value="{{ $setting->value }}">
                                        @endif

                                        <input type="hidden" name="settings[{{ $setting->id }}][key]" value="{{ $setting->key }}">
                                        <input type="hidden" name="settings[{{ $setting->id }}][type]" value="{{ $setting->type }}">
                                        <input type="hidden" name="settings[{{ $setting->id }}][description]" value="{{ $setting->description }}">
                                        <input type="hidden" name="settings[{{ $setting->id }}][is_public]" value="{{ $setting->is_public ? 1 : 0 }}">
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

