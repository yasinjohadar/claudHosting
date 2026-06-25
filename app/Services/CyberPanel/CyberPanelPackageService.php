<?php

namespace App\Services\CyberPanel;

class CyberPanelPackageService
{
    public function __construct(protected CyberPanelApiService $api) {}

    /**
     * @return array<int, string>
     */
    public function listPackageNames(): array
    {
        $result = $this->api->listPackages();
        if (! ($result['success'] ?? false)) {
            return [];
        }

        $names = [];
        foreach ($result['packages'] ?? [] as $pkg) {
            if (! is_array($pkg)) {
                continue;
            }
            $name = trim((string) ($pkg['packageName'] ?? $pkg['name'] ?? $pkg['package'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string}
     */
    public function createPackage(array $params): array
    {
        $result = $this->api->createPackage($params);

        return [
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? (($result['success'] ?? false) ? 'تم إنشاء الباقة' : 'فشل إنشاء الباقة'),
        ];
    }
}
