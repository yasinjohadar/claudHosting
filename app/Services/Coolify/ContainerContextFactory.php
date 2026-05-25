<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;

class ContainerContextFactory
{
    public function __construct(
        protected WordpressContainerResolver $resolver,
        protected WordpressCliService $cli,
        protected WordpressManagementService $management
    ) {}

    /**
     * @return array{success: bool, context?: ContainerExecutionContext, message?: string}
     */
    public function forSite(CoolifyWordpressSite $site): array
    {
        $state = $this->management->getManagementState($site);
        if (! ($state['execute_ready'] ?? false)) {
            return ['success' => false, 'message' => $state['message'] ?? 'SSH غير جاهز'];
        }

        $resolved = $this->resolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'تعذّر تحديد الحاوية'];
        }

        $host = (string) ($resolved['host'] ?? '');
        $containerId = (string) ($resolved['container_id'] ?? '');
        if ($host === '' || $containerId === '') {
            return ['success' => false, 'message' => 'بيانات الحاوية غير مكتملة'];
        }

        $wordpressRoot = $this->cli->resolveWordpressPathForSite($site, $host, $containerId);

        return [
            'success' => true,
            'context' => new ContainerExecutionContext(
                siteId: $site->id,
                siteUuid: $site->uuid,
                host: $host,
                containerId: $containerId,
                containerName: (string) ($resolved['container_name'] ?? ''),
                wordpressRoot: $wordpressRoot,
                serviceUuid: (string) ($site->service_uuid ?? ''),
            ),
        ];
    }
}
