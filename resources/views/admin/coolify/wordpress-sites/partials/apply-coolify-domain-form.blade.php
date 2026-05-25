@php $wpSiteRoutes = $wpSiteRoutes ?? \App\Support\WordpressSiteRouteMap::forPanel('admin', $uuid); @endphp
@if(!empty($wpSiteRoutes['applyCoolifyDomain']))
<form method="POST" action="{{ $wpSiteRoutes['applyCoolifyDomain'] }}" class="d-inline"
    onsubmit="return confirm('سيتم تعيين {{ $site->public_url }} على Coolify وإعادة تشغيل الخدمة. متابعة؟');">
    @csrf
    <button type="submit" class="btn btn-warning btn-sm">
        <i class="fe fe-link"></i> تطبيق النطاق على Coolify
    </button>
</form>
@endif
