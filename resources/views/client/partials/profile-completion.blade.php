{{--
    Profile completion progress. One partial, two shapes.

    @param  \App\Support\ProfileCompletion $completion  required
    @param  string $variant  'full' (profile page) | 'compact' (dashboard banner). Default 'full'.

    The compact variant renders nothing at all once the profile is complete — a permanent
    "you are done" banner on the dashboard is just noise. The full variant keeps a short
    confirmation instead, so the profile page does not gain an empty hole.
--}}
@php
    $variant = $variant ?? 'full';
    $percent = $completion->percent();
    $tone = $completion->tone();
    $missing = $completion->missing();
    $optional = $completion->optionalMissing();
    $editUrl = route('client.profile.edit');
@endphp

@if($variant === 'compact' && $completion->isComplete())
    {{-- nothing to nag about --}}
@else
    @once
        @include('client.partials.profile-completion-styles')
    @endonce

    <div class="profile-completion profile-completion--{{ $variant }} profile-completion--{{ $tone }}">
        <div class="profile-completion__head">
            <span class="profile-completion__badge" aria-hidden="true">
                <i class="fe {{ $completion->isComplete() ? 'fe-check' : 'fe-trending-up' }}"></i>
            </span>
            <div class="profile-completion__headline">
                <strong>{{ $completion->headline() }}</strong>
                <p>
                    @if($completion->isComplete())
                        كل البيانات المطلوبة موجودة — فواتيرك وتنبيهاتك تصل بشكل صحيح.
                    @else
                        أكملت {{ $completion->completedCount() }} من {{ $completion->totalCount() }} من البيانات المطلوبة.
                        استكمالها يضمن وصول الفواتير والتنبيهات إليك، ويتيح استعادة كلمة المرور عبر واتساب.
                    @endif
                </p>
            </div>
            <span class="profile-completion__percent" dir="ltr">{{ $percent }}%</span>
        </div>

        <div class="profile-completion__track" role="progressbar"
            aria-label="نسبة اكتمال الملف الشخصي"
            aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="profile-completion__bar" style="width: {{ $percent }}%"></div>
        </div>

        @if($variant === 'compact')
            @if($missing !== [])
                <div class="profile-completion__chips">
                    @foreach($missing as $item)
                        <a href="{{ $editUrl }}" class="profile-completion__chip">
                            <i class="{{ $item['icon'] }}"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
            <a href="{{ $editUrl }}" class="btn btn-primary btn-sm rounded-pill px-4 profile-completion__cta">
                <i class="fe fe-edit-2 me-1"></i> أكمل ملفك الآن
            </a>
        @else
            @if($missing !== [])
                <ul class="profile-completion__list">
                    @foreach($missing as $item)
                        <li class="profile-completion__item">
                            <span class="profile-completion__item-icon"><i class="{{ $item['icon'] }}"></i></span>
                            <div class="profile-completion__item-body">
                                <strong>{{ $item['label'] }}</strong>
                                <span>{{ $item['why'] }}</span>
                            </div>
                            <a href="{{ $editUrl }}" class="profile-completion__item-action">
                                إضافة<i class="fe fe-arrow-left ms-1"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($completion->done() !== [])
                <div class="profile-completion__done">
                    @foreach($completion->done() as $item)
                        <span class="profile-completion__done-chip">
                            <i class="fe fe-check"></i>{{ $item['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            @if($optional !== [])
                <div class="profile-completion__optional">
                    <span class="profile-completion__optional-title">خطوات إضافية — لا تؤثر على النسبة</span>
                    <div class="profile-completion__chips">
                        @foreach($optional as $item)
                            <a href="{{ $editUrl }}" class="profile-completion__chip" title="{{ $item['why'] }}">
                                <i class="{{ $item['icon'] }}"></i>{{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
@endif
