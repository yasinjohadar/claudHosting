@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'إرسال وسائط';
    $evoTitle = 'إرسال وسائط';
    $evoSubtitle = 'صورة، فيديو، صوت، أو مستند';
    $evoBreadcrumb = 'إرسال';
@endphp

@section('evo-content')
@include('admin.pages.evolution-api.partials.send-nav', ['instanceName' => $instanceName])

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-image-line me-2 text-success"></i>وسائط</div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.evolution-api.send.media.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">المستلم</label>
                            <input type="text" name="to" class="form-control" required value="{{ old('to') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">النوع</label>
                            <select name="mediatype" class="form-select">
                                @foreach(['image'=>'صورة','video'=>'فيديو','audio'=>'صوت','document'=>'مستند'] as $v=>$l)
                                    <option value="{{ $v }}" @selected(old('mediatype')===$v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">اسم الملف</label>
                            <input type="text" name="fileName" class="form-control" value="{{ old('fileName') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">رابط أو Base64</label>
                            <input type="text" name="media" class="form-control" required value="{{ old('media') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">تعليق</label>
                            <textarea name="caption" class="form-control" rows="2">{{ old('caption') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-success mt-3"><i class="ri-send-plane-line me-1"></i> إرسال</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
