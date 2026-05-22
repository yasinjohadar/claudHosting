<?php

namespace App\Http\Controllers\Admin\Coolify\Concerns;

use App\Models\CoolifyActivityLog;

trait LogsCoolifyActivity
{
    protected function logCoolify(
        string $action,
        string $resourceType,
        ?string $uuid = null,
        ?string $name = null,
        ?string $message = null
    ): void {
        try {
            CoolifyActivityLog::record($action, $resourceType, $uuid, $name, $message);
        } catch (\Throwable) {
            // ignore if migration not run yet
        }
    }
}
