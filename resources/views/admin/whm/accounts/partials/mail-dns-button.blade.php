{{--
    Trigger for the mail-DNS sync.

    Opt-in by construction: a caller must pass canWriteDns => true AND the endpoints it
    should talk to. The URLs are never assumed, so a client page cannot accidentally end
    up pointing at the admin routes (which are behind admin.panel anyway).

    @param  \App\Models\WhmAccount  $account
    @param  string|null  $previewUrl  defaults to the admin preview route
    @param  string|null  $applyUrl    defaults to the admin apply route
--}}
@php
    $previewUrl = $previewUrl ?? route('admin.whm.accounts.mail-dns.preview', $account);
    $applyUrl = $applyUrl ?? route('admin.whm.accounts.mail-dns.apply', $account);
@endphp
<button type="button" class="btn btn-sm btn-primary"
    data-whm-dns-open
    data-preview-url="{{ $previewUrl }}"
    data-apply-url="{{ $applyUrl }}">
    <i class="fe fe-upload-cloud me-1"></i>تركيب في Cloudflare
</button>
@include('admin.whm.accounts.partials.mail-dns-modal')
