@php $email = $account->display_email; @endphp
<td class="text-center align-middle whm-col-email">
    @if($email)
        <div class="whm-email-copy-wrap mx-auto">
            <span class="whm-email-text" dir="ltr" title="{{ $email }}">{{ $email }}</span>
            <button type="button" class="btn btn-sm whm-copy-email" data-copy="{{ $email }}" title="نسخ البريد" aria-label="نسخ البريد">
                <i class="fe fe-copy"></i>
            </button>
        </div>
    @else
        <span class="text-muted">—</span>
    @endif
</td>
