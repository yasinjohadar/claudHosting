@php
    $cf = $site->metadata['cloudflare'] ?? [];
    $hasCf = !empty($cf);
@endphp
<form method="POST" action="{{ route('admin.coolify.wordpress-sites.sync-cloudflare', $uuid) }}" class="d-inline">
    @csrf
    <button type="submit" class="btn {{ $hasCf ? 'btn-outline-info' : 'btn-info' }} btn-sm">
        <i class="fe fe-refresh-cw"></i>
        {{ $hasCf ? 'إعادة مزامنة Cloudflare' : 'مزامنة من Cloudflare' }}
    </button>
</form>
