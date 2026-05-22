@if($url = $account->site_url)
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="whm-domain-link link-primary" dir="ltr" title="فتح {{ $account->domain }}">
        {{ $account->domain }}
        <i class="fe fe-external-link whm-domain-link-icon" aria-hidden="true"></i>
    </a>
@else
    <span class="text-muted">—</span>
@endif
