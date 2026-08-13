@extends('client.layouts.master')

@section('page-title')
تذكرة {{ $ticket->tid }}
@stop

@section('content')
@php
    $steps = $ticket->progress_steps;
    $percent = $ticket->progress_percent;
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success py-2 mt-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2 mt-3">{{ session('error') }}</div>
        @endif

        <div class="client-ticket-show">
            <div class="client-ticket-show__hero">
                <div class="client-ticket-show__hero-inner">
                    <div class="client-ticket-show__identity">
                        <nav class="client-portal-breadcrumb mb-2">
                            <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                            <span class="text-muted mx-1">/</span>
                            <a href="{{ route('client.tickets.index') }}">التذاكر</a>
                            <span class="text-muted mx-1">/</span>
                            <span class="text-muted" dir="ltr">{{ $ticket->tid }}</span>
                        </nav>
                        <h1 class="client-ticket-show__title">{{ $ticket->subject }}</h1>
                        <div class="client-profile-show__chips mt-2">
                            <span class="client-profile-show__chip" dir="ltr">
                                <i class="fe fe-hash"></i> {{ $ticket->tid }}
                            </span>
                            <span class="client-profile-show__chip">
                                <i class="fe fe-layers"></i> {{ $ticket->department ?: '—' }}
                            </span>
                            <span class="client-profile-show__chip client-profile-show__chip--accent">
                                <i class="fe fe-flag"></i> {{ $ticket->status_name }}
                            </span>
                            <span class="client-profile-show__chip">
                                <i class="fe fe-calendar"></i> {{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}
                            </span>
                        </div>
                    </div>

                    <div class="client-ticket-show__hero-side">
                        <div class="client-profile-show__completion" style="--progress: {{ $percent }};">
                            <div class="client-profile-show__completion-ring" aria-hidden="true">
                                <span>{{ $percent }}%</span>
                            </div>
                            <div>
                                <strong>تقدم التذكرة</strong>
                                <p class="client-profile-show__completion-hint mb-0">{{ $ticket->status_name }}</p>
                            </div>
                        </div>
                        <div class="client-profile-show__actions">
                            <a href="{{ route('client.tickets.index') }}" class="btn btn-light btn-sm rounded-pill px-4">
                                <i class="fe fe-arrow-right me-1"></i> القائمة
                            </a>
                            @if($ticket->status !== 'Closed')
                                <a href="#reply-box" class="btn btn-primary btn-sm rounded-pill px-4">
                                    <i class="fe fe-message-square me-1"></i> إضافة رد
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="client-ticket-steps" aria-label="مراحل التقدم">
                    @foreach($steps as $index => $step)
                        <div class="client-ticket-steps__item {{ $step['done'] ? 'is-done' : '' }} {{ $step['current'] ? 'is-current' : '' }}">
                            <span class="client-ticket-steps__dot">
                                @if($step['done'])
                                    <i class="fe fe-check"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span class="client-ticket-steps__label">{{ $step['label'] }}</span>
                        </div>
                        @if(! $loop->last)
                            <span class="client-ticket-steps__line {{ $step['done'] ? 'is-done' : '' }}"></span>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="row g-3 client-ticket-show__grid">
                <div class="col-lg-4">
                    <div class="client-profile-show__card h-100">
                        <div class="client-profile-show__card-head">
                            <span class="client-profile-show__card-icon client-profile-show__card-icon--blue">
                                <i class="fe fe-info"></i>
                            </span>
                            <div>
                                <h3>تفاصيل التذكرة</h3>
                                <p>القسم والأولوية والحالة</p>
                            </div>
                        </div>
                        <div class="client-profile-show__list">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="client-profile-show__list-label">القسم</span>
                                <span>{{ $ticket->department ?: '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="client-profile-show__list-label">الأولوية</span>
                                <span class="badge bg-{{ $ticket->priority_color }}-transparent">{{ $ticket->priority_name }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="client-profile-show__list-label">الحالة</span>
                                <span class="badge bg-{{ $ticket->status_color }}-transparent">{{ $ticket->status_name }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="client-profile-show__list-label">آخر رد</span>
                                <span>{{ $ticket->lastreply?->format('Y-m-d H:i') ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="client-profile-show__list-label">عدد الردود</span>
                                <span>{{ $ticket->replies->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="client-profile-show__card">
                        <div class="client-profile-show__card-head">
                            <span class="client-profile-show__card-icon client-profile-show__card-icon--emerald">
                                <i class="fe fe-message-square"></i>
                            </span>
                            <div>
                                <h3>المحادثة</h3>
                                <p>الرسالة الأصلية والردود المتبادلة</p>
                            </div>
                        </div>

                        <div class="client-ticket-thread">
                            <article class="client-ticket-thread__item client-ticket-thread__item--client">
                                <div class="client-ticket-thread__meta">
                                    <strong>{{ $ticket->name ?: $user->name }}</strong>
                                    <span class="badge bg-info-transparent">أنت</span>
                                    <span class="text-muted small ms-auto">{{ $ticket->date?->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="client-ticket-thread__body ticket-rich-content">{!! \App\Support\Html::safe($ticket->message) !!}</div>
                            </article>

                            @foreach($ticket->replies as $reply)
                                @php $isAdmin = $reply->type === 'admin' || filled($reply->admin); @endphp
                                <article class="client-ticket-thread__item {{ $isAdmin ? 'client-ticket-thread__item--admin' : 'client-ticket-thread__item--client' }}">
                                    <div class="client-ticket-thread__meta">
                                        <strong>{{ $reply->name }}</strong>
                                        @if($isAdmin)
                                            <span class="badge bg-primary-transparent">فريق الدعم</span>
                                        @else
                                            <span class="badge bg-info-transparent">أنت</span>
                                        @endif
                                        <span class="text-muted small ms-auto">{{ $reply->date?->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="client-ticket-thread__body ticket-rich-content">{!! \App\Support\Html::safe($reply->message) !!}</div>
                                </article>
                            @endforeach
                        </div>

                        @if($ticket->status !== 'Closed')
                            <div id="reply-box" class="client-ticket-reply mt-3">
                                <h4 class="h6 fw-bold mb-2">إضافة رد</h4>
                                <form action="{{ route('client.tickets.reply', $ticket) }}" method="POST" id="client-ticket-reply-form">
                                    @csrf
                                    <textarea name="message" id="reply_message" class="form-control @error('message') is-invalid @enderror" rows="8">{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="d-flex justify-content-end mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                                            <i class="fe fe-send me-1"></i> إرسال الرد
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="client-portal-alert mt-3 mb-0">
                                <span class="client-portal-alert__icon"><i class="fe fe-lock"></i></span>
                                <div class="small mb-0">هذه التذكرة مغلقة. افتح تذكرة جديدة إذا احتجت مساعدة إضافية.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($ticket->status !== 'Closed')
@push('scripts')
@include('admin.partials.tinymce-editor', [
    'tinymceSelector' => '#reply_message',
    'tinymceHeight' => 280,
    'tinymceFormId' => 'client-ticket-reply-form',
    'tinymceRequiredMessage' => 'يرجى كتابة الرد',
])
@endpush
@endif
