<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;

class CoolifyProjectCleanupService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * ملخص موارد المشروع لعرض القائمة والتحقق قبل الحذف.
     *
     * @return array{
     *     success: bool,
     *     resources: array<int, array<string, mixed>>,
     *     coolify_count: int,
     *     wordpress_sites_count: int,
     *     total: int,
     *     by_type: array<string, int>,
     *     summary_label: string,
     *     can_delete: bool,
     *     block_message: string,
     *     fetch_error: string|null
     * }
     */
    public function inspectProject(string $projectUuid): array
    {
        $projectUuid = trim($projectUuid);
        $empty = [
            'success' => true,
            'resources' => [],
            'coolify_count' => 0,
            'wordpress_sites_count' => 0,
            'total' => 0,
            'by_type' => [],
            'summary_label' => 'لا توجد موارد',
            'can_delete' => true,
            'block_message' => '',
            'fetch_error' => null,
        ];

        if ($projectUuid === '') {
            return array_merge($empty, ['success' => false, 'fetch_error' => 'معرّف غير صالح']);
        }

        $fetch = $this->fetchProjectResources($projectUuid);
        $resources = $fetch['resources'];
        $wpCount = (int) CoolifyWordpressSite::query()->where('project_uuid', $projectUuid)->count();
        $byType = $this->countResourcesByType($resources);
        $coolifyCount = count($resources);
        $total = $coolifyCount + $wpCount;
        $canDelete = $total === 0 && ($fetch['success'] ?? true);

        return [
            'success' => $fetch['success'] ?? false,
            'resources' => $resources,
            'coolify_count' => $coolifyCount,
            'wordpress_sites_count' => $wpCount,
            'total' => $total,
            'by_type' => $byType,
            'summary_label' => $this->buildSummaryLabel($byType, $wpCount, $coolifyCount),
            'can_delete' => $canDelete,
            'block_message' => $canDelete ? '' : $this->buildBlockMessage($projectUuid, $resources, $wpCount, $byType),
            'fetch_error' => $fetch['error'] ?? null,
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     warnings: array<int, string>,
     *     wordpress_sites_deleted: int,
     *     snapshots_deleted: int,
     *     resources_deleted: int
     * }
     */
    public function purgeForProject(string $projectUuid): array
    {
        $projectUuid = trim($projectUuid);
        if ($projectUuid === '') {
            return $this->failureResult('معرّف المشروع غير صالح');
        }

        $inspection = $this->inspectProject($projectUuid);

        if (! ($inspection['can_delete'] ?? false)) {
            $message = $inspection['block_message'] !== ''
                ? $inspection['block_message']
                : 'لا يمكن حذف المشروع لوجود موارد بداخله.';

            if ($inspection['fetch_error'] ?? null) {
                $message .= ' '.$inspection['fetch_error'];
            }

            return $this->failureResult($message);
        }

        $snapshotsDeleted = $this->purgeLocalSnapshots($projectUuid);
        $this->clearSharedProjectSetting($projectUuid);

        $response = $this->coolify->deleteProject($projectUuid);

        if (! ($response['success'] ?? false)) {
            return $this->failureResult(
                trim((string) ($response['message'] ?? 'فشل حذف المشروع من Coolify'))
            );
        }

        $parts = ['تم حذف المشروع من Coolify'];
        if ($snapshotsDeleted > 0) {
            $parts[] = $snapshotsDeleted.' لقطة محلية';
        }

        return [
            'success' => true,
            'message' => implode(' — ', $parts),
            'warnings' => [],
            'wordpress_sites_deleted' => 0,
            'snapshots_deleted' => $snapshotsDeleted,
            'resources_deleted' => 0,
        ];
    }

    /**
     * @return array{success: bool, resources: array<int, array<string, mixed>>, error: string|null}
     */
    public function fetchProjectResources(string $projectUuid): array
    {
        $response = $this->coolify->projectResources($projectUuid);

        if (! ($response['success'] ?? false)) {
            return [
                'success' => false,
                'resources' => [],
                'error' => $response['message'] ?? 'تعذّر جلب موارد المشروع',
            ];
        }

        return [
            'success' => true,
            'resources' => $this->coolify->normalizeList($response['data'] ?? []),
            'error' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $resources
     * @return array<string, int>
     */
    protected function countResourcesByType(array $resources): array
    {
        $counts = [];

        foreach ($resources as $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $bucket = $this->resourceTypeBucket($resource);
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function resourceTypeBucket(array $resource): string
    {
        $type = strtolower((string) ($resource['type'] ?? $resource['resource_type'] ?? 'resource'));

        if ($type === 'application') {
            return 'application';
        }

        if ($type === 'service') {
            return 'service';
        }

        if ($type === 'database' || in_array($type, ['postgresql', 'mysql', 'mariadb', 'mongodb', 'redis'], true)) {
            return 'database';
        }

        return 'other';
    }

    /**
     * @param  array<string, int>  $byType
     */
    protected function buildSummaryLabel(array $byType, int $wpCount, int $coolifyCount): string
    {
        if ($coolifyCount === 0 && $wpCount === 0) {
            return 'لا توجد موارد';
        }

        $labels = [
            'application' => 'تطبيق',
            'service' => 'خدمة',
            'database' => 'قاعدة بيانات',
            'other' => 'مورد',
        ];

        $parts = [];
        foreach ($byType as $key => $count) {
            if ($count > 0) {
                $parts[] = $count.' '.($labels[$key] ?? $key);
            }
        }

        if ($wpCount > 0) {
            $parts[] = $wpCount.' موقع WordPress';
        }

        return implode('، ', $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $resources
     * @param  array<string, int>  $byType
     */
    protected function buildBlockMessage(string $projectUuid, array $resources, int $wpCount, array $byType): string
    {
        $lines = [
            'لا يمكن حذف المشروع: يوجد '.$this->buildSummaryLabel($byType, $wpCount, count($resources)).' داخل المشروع.',
            'احذف جميع الموارد أولاً من Coolify ثم أعد المحاولة.',
        ];

        $names = [];
        foreach (array_slice($resources, 0, 5) as $resource) {
            $name = trim((string) ($resource['name'] ?? ''));
            $type = $this->resourceTypeBucket($resource);
            if ($name !== '') {
                $names[] = $name.' ('.$type.')';
            }
        }

        if ($names !== []) {
            $lines[] = 'أمثلة: '.implode('، ', $names).(count($resources) > 5 ? '…' : '');
        }

        if ($wpCount > 0) {
            $lines[] = 'يوجد '.$wpCount.' موقع WordPress مرتبط — احذفه من «مواقع WordPress» أولاً.';
        }

        $lines[] = 'عرض الموارد: '.route('admin.coolify.projects.resources', $projectUuid);

        return implode("\n", $lines);
    }

    protected function purgeLocalSnapshots(string $projectUuid): int
    {
        $snapshotCount = CoolifyProjectSnapshot::query()
            ->where('project_uuid', $projectUuid)
            ->count();

        CoolifyProjectSnapshot::query()
            ->where('project_uuid', $projectUuid)
            ->delete();

        CoolifyProjectSnapshotItem::query()
            ->where('project_uuid', $projectUuid)
            ->delete();

        return $snapshotCount;
    }

    protected function clearSharedProjectSetting(string $projectUuid): void
    {
        if ($this->settings->getWordpressSharedProjectUuid() !== $projectUuid) {
            return;
        }

        $this->settings->updateSettings(['wordpress_shared_project_uuid' => '']);
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     warnings: array<int, string>,
     *     wordpress_sites_deleted: int,
     *     snapshots_deleted: int,
     *     resources_deleted: int
     * }
     */
    protected function failureResult(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'warnings' => [],
            'wordpress_sites_deleted' => 0,
            'snapshots_deleted' => 0,
            'resources_deleted' => 0,
        ];
    }
}
