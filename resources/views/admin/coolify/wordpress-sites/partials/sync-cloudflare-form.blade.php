@php
    $cf = $site->metadata['cloudflare'] ?? [];
    $hasCf = !empty($cf);
@endphp
@php $wpSiteRoutes = $wpSiteRoutes ?? \App\Support\WordpressSiteRouteMap::forPanel('admin', $uuid); @endphp
@if(!empty($wpSiteRoutes['syncCloudflare']))
<form method="POST" action="{{ $wpSiteRoutes['syncCloudflare'] }}" class="d-inline">
    @csrf
    <button type="submit" class="btn {{ $hasCf ? 'btn-outline-info' : 'btn-info' }} btn-sm">
        <i class="fe fe-refresh-cw"></i>
        {{ $hasCf ? 'إعادة مزامنة Cloudflare' : 'مزامنة من Cloudflare' }}
    </button>
</form>
@endif
