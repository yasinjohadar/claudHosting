<?php

namespace App\Support;

class WordpressSiteRouteMap
{
    /**
     * @return array<string, string>
     */
    public static function forPanel(string $panel, string $uuid): array
    {
        $prefix = $panel === 'client' ? 'client.wordpress-sites' : 'admin.coolify.wordpress-sites';

        return [
            'index' => route("{$prefix}.index"),
            'show' => route("{$prefix}.show", $uuid),
            'edit' => $panel === 'admin' ? route('admin.coolify.wordpress-sites.edit', $uuid) : route("{$prefix}.show", $uuid),
            'status' => route("{$prefix}.status", $uuid),
            'wpInfo' => route("{$prefix}.wp-info", $uuid),
            'wpAction' => route("{$prefix}.wp-action", $uuid),
            'wpJob' => route("{$prefix}.wp-job", $uuid),
            'filesList' => route("{$prefix}.files.list", $uuid),
            'filesRead' => route("{$prefix}.files.read", $uuid),
            'filesWrite' => route("{$prefix}.files.write", $uuid),
            'filesUpload' => route("{$prefix}.files.upload", $uuid),
            'filesMkdir' => route("{$prefix}.files.mkdir", $uuid),
            'filesRename' => route("{$prefix}.files.rename", $uuid),
            'filesDestroy' => route("{$prefix}.files.destroy", $uuid),
            'filesDownload' => route("{$prefix}.files.download", $uuid),
            'dockerLogs' => route("{$prefix}.docker.logs", $uuid),
            'dockerInspect' => route("{$prefix}.docker.inspect", $uuid),
            'terminalSession' => route("{$prefix}.terminal.session", $uuid),
            'terminalCommands' => route("{$prefix}.terminal.commands"),
            'syncCloudflare' => route("{$prefix}.sync-cloudflare", $uuid),
            'applyCoolifyDomain' => $panel === 'admin' ? route('admin.coolify.wordpress-sites.apply-coolify-domain', $uuid) : '',
            'retry' => $panel === 'admin' ? route('admin.coolify.wordpress-sites.retry', $uuid) : '',
            'restartCoolify' => $panel === 'admin' ? route('admin.coolify.wordpress-sites.restart-coolify', $uuid) : '',
            'destroy' => $panel === 'admin' ? route('admin.coolify.wordpress-sites.destroy', $uuid) : '',
        ];
    }
}
