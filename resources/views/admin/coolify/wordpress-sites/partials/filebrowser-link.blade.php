@php
    $fbRoutes = $wpSiteRoutes ?? [];
    $fbEmbedHref = $fbRoutes['filebrowser'] ?? null;
    $fbMode = $filebrowserOpenMode ?? app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressFilebrowserOpenMode();
    $fbHref = $fbEmbedHref ?: ($filebrowserOpenUrl ?? null);
    $fbNewTab = $fbMode === 'new_tab';
    $fbDisabled = empty($canOpenFilebrowser) || ! $fbHref;
@endphp
<a href="{{ $fbDisabled ? '#' : $fbHref }}"
    class="btn btn-outline-info btn-sm {{ $fbDisabled ? 'disabled' : '' }}"
    @if(! $fbDisabled && $fbNewTab) target="_blank" rel="noopener" @endif>
    <i class="fe {{ $fbNewTab ? 'fe-external-link' : 'fe-folder' }}"></i> فتح FileBrowser
</a>
