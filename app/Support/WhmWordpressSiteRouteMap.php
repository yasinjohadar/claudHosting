<?php

namespace App\Support;

class WhmWordpressSiteRouteMap
{
    /**
     * @return array<string, string>
     */
    public static function forPanel(string $panel, int $accountId, int $siteId): array
    {
        $prefix = $panel === 'client'
            ? 'client.hosting.wordpress'
            : 'admin.whm.accounts.wordpress';

        $params = [$accountId, $siteId];

        return [
            'index' => route("{$prefix}.index", $accountId),
            'show' => route("{$prefix}.show", $params),
            'status' => route("{$prefix}.status", $params),
            'wpInfo' => route("{$prefix}.wp-info", $params),
            'wpAction' => route("{$prefix}.wp-action", $params),
            'wpJob' => route("{$prefix}.wp-job", $params),
            'wpOperations' => route("{$prefix}.wp-operations", $params),
            'wpOperationDownload' => route("{$prefix}.wp-operations.download", [$accountId, $siteId, '__ID__']),
            'open' => route("{$prefix}.open", $params),
            'wpAdmin' => route("{$prefix}.wp-admin", $params),
            'manager' => route("{$prefix}.manager", $params),
            // Unused by Coolify scripts but required keys for parity
            'edit' => route("{$prefix}.show", $params),
            'filesList' => '',
            'filesRead' => '',
            'filesWrite' => '',
            'filesUpload' => '',
            'filesMkdir' => '',
            'filesRename' => '',
            'filesDestroy' => '',
            'filesDownload' => '',
            'dockerLogs' => '',
            'dockerInspect' => '',
            'dockerStats' => '',
            'dockerHealth' => '',
            'dockerDbBackup' => '',
            'dockerDbRestore' => '',
            'terminalSession' => '',
            'terminalCommands' => '',
            'syncCloudflare' => '',
            'filebrowser' => '',
            'filebrowserProxy' => '',
            'filebrowserRotate' => '',
            'applyCoolifyDomain' => '',
            'retry' => '',
            'restartCoolify' => '',
            'componentRestart' => '',
            'componentRedeploy' => '',
            'destroy' => '',
        ];
    }
}
