@php
    $fbMode = $filebrowserOpenMode ?? app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressFilebrowserOpenMode();
    $fbRoutes = $wpSiteRoutes ?? [];
    $fbEmbedHref = $fbRoutes['filebrowser'] ?? null;
    $fbExternal = $filebrowserOpenUrl ?? null;
    $fbHref = ($fbMode === 'new_tab' && $fbExternal) ? $fbExternal : ($fbEmbedHref ?: $fbExternal);
    $fbNewTab = $fbMode === 'new_tab' && $fbExternal;
    $fbDisabled = empty($canOpenFilebrowser) || ! $fbHref;
@endphp
<a href="{{ $fbDisabled ? '#' : $fbHref }}"
    class="btn btn-outline-info btn-sm {{ $fbDisabled ? 'disabled' : '' }}"
    @if(! $fbDisabled && $fbNewTab) target="_blank" rel="noopener" @endif>
    <i class="fe {{ $fbNewTab ? 'fe-external-link' : 'fe-folder' }}"></i> فتح FileBrowser
</a>
