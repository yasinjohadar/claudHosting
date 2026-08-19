<?php

namespace App\Services\WhatsApp\Evolution;

use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Providers\EvolutionApiProvider;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;

class EvolutionService
{
    public function __construct(
        private WhatsAppSettingsService $settingsService
    ) {}

    public function getSettings(): array
    {
        $this->settingsService->initializeDefaults();

        return $this->settingsService->getSettings();
    }

    public function client(?array $override = null): EvolutionApiClient
    {
        $settings = $override ?? $this->getSettings();

        return EvolutionApiClient::fromConfig([
            'base_url' => $settings['evolution_base_url'] ?? '',
            'api_key' => $settings['evolution_api_key'] ?? '',
        ]);
    }

    public function clientFor(?EvolutionInstance $instance = null, ?string $instanceName = null): EvolutionApiClient
    {
        if ($instanceName !== null && $instance === null) {
            $instance = EvolutionInstance::where('instance_name', $instanceName)->first();
        }

        if ($instance instanceof EvolutionInstance && $instance->hasCustomCredentials()) {
            return EvolutionApiClient::fromConfig($instance->resolveApiConfig());
        }

        return $this->client();
    }

    public function clientForActiveInstance(): EvolutionApiClient
    {
        return $this->clientFor(null, $this->activeInstanceName());
    }

    public function refreshInstanceFromApi(EvolutionInstance $instance): EvolutionInstance
    {
        $client = $this->clientFor($instance);

        // The instance list comes first: it carries the profile, it is the fallback when
        // the state endpoint answers in a shape we cannot read, and it is the only way to
        // tell "this phone is disconnected" apart from "this name is not on this server".
        $profile = $this->fetchInstanceProfileFromApi($client, $instance->instance_name);
        $updates = $profile['updates'];

        $connection = null;
        try {
            $connection = EvolutionInstanceState::readConnectionState(
                $client->getConnectionState($instance->instance_name)
            );
        } catch (\Throwable $e) {
            // A 404 here means the name is unknown to this server, which the list already
            // told us. Anything else is a real transport failure and must surface.
            if (! EvolutionApiException::isNotFound($e)) {
                throw $e;
            }
        }

        // Order of trust: the dedicated state endpoint, then the list's connectionStatus,
        // then "the server does not know this name at all". Never an invented "close".
        $connection ??= $profile['connection_status'];
        if ($connection === null && $profile['found'] === false) {
            $connection = EvolutionInstanceState::NOT_FOUND;
        }

        if ($connection !== null) {
            $updates['connection_status'] = $connection;

            if ($connection === EvolutionInstanceState::OPEN) {
                $updates['disconnected_at'] = null;
                if ($instance->connection_status !== EvolutionInstanceState::OPEN) {
                    $updates['connected_at'] = now();
                    $updates['rotation_enabled'] = true;
                }
            } else {
                // connected_at is history and is left alone; only the last-seen-down moves.
                $updates['disconnected_at'] = now();
            }
        }

        if ($updates !== []) {
            $instance->update($updates);
        }

        return $instance->fresh();
    }

    /**
     * Names this Evolution server reports, for the "your name matches none of these"
     * hint. Returns null when the list could not be read at all.
     *
     * @return list<string>|null
     */
    public function remoteInstanceNames(?EvolutionInstance $instance = null): ?array
    {
        try {
            return EvolutionInstanceState::names($this->clientFor($instance)->fetchInstances());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Refresh every instance registered in the platform (uses per-instance API credentials).
     *
     * @return EvolutionInstance[]
     */
    public function syncAllRegisteredInstances(): array
    {
        $synced = [];

        EvolutionInstance::query()
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (EvolutionInstance $instance) use (&$synced) {
                try {
                    $synced[] = $this->refreshInstanceFromApi($instance);
                } catch (\Throwable) {
                    // keep last known row when this instance's API is unreachable
                }
            });

        return $synced;
    }

    /**
     * Look this instance up in the server's instance list.
     *
     * `found` is three-valued on purpose: true (row present), false (the server answered
     * but this name is not in the list), null (the list itself could not be read, so we
     * know nothing and must not conclude anything).
     *
     * @return array{updates: array<string, mixed>, connection_status: ?string, found: ?bool}
     */
    private function fetchInstanceProfileFromApi(EvolutionApiClient $client, string $instanceName): array
    {
        $result = ['updates' => [], 'connection_status' => null, 'found' => null];

        try {
            $response = $client->fetchInstances($instanceName);
        } catch (\Throwable $e) {
            // 404 from the list endpoint is still an answer: the name is not there.
            if (EvolutionApiException::isNotFound($e)) {
                $result['found'] = false;
            }

            return $result;
        }

        $row = EvolutionInstanceState::findRow($response, $instanceName);
        if ($row === null) {
            $result['found'] = false;

            return $result;
        }

        $result['found'] = true;
        $result['connection_status'] = EvolutionInstanceState::readConnectionState($row);

        $updates = [];

        // Only non-empty values are written: a build that omits profileName must not wipe
        // the name we already learned from a previous sync.
        if (! empty($row['id'])) {
            $updates['evolution_uuid'] = $row['id'];
        }

        $ownerJid = EvolutionInstanceState::ownerJid($row);
        if ($ownerJid !== null) {
            $updates['owner_jid'] = $ownerJid;
        }

        if (! empty($row['profileName'])) {
            $updates['profile_name'] = $row['profileName'];
        }

        if (! empty($row['profilePicUrl'])) {
            $updates['profile_pic_url'] = $row['profilePicUrl'];
        }

        $phone = EvolutionInstanceState::phoneNumber($row);
        if ($phone !== null) {
            $updates['phone_number'] = $phone;
        }

        $result['updates'] = $updates;

        return $result;
    }

    /**
     * Refresh connection status for instances before rotation.
     */
    public function refreshRotationCandidates(): int
    {
        return count($this->syncAllRegisteredInstances());
    }

    /**
     * @return string[]
     */
    public function parseInstanceNamesList(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn ($line) => trim($line),
            $lines
        ))));
    }

    public function registerManualInstance(array $data): EvolutionInstance
    {
        $name = trim((string) ($data['instance_name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('اسم Instance مطلوب.');
        }

        $instance = EvolutionInstance::firstOrNew(['instance_name' => $name]);
        $isNew = ! $instance->exists;
        $instance->fill([
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
            'is_manual' => true,
            'connection_status' => $instance->connection_status ?: 'pending',
            'rotation_enabled' => $isNew ? true : $instance->rotation_enabled,
        ]);

        $baseUrl = trim((string) ($data['evolution_base_url'] ?? ''));
        if ($baseUrl !== '') {
            $instance->evolution_base_url = rtrim($baseUrl, '/');
        }

        $apiKey = trim((string) ($data['evolution_api_key'] ?? ''));
        if ($apiKey !== '') {
            $instance->evolution_api_key = $apiKey;
        }

        $instance->save();

        if (! empty($data['verify']) && ($instance->hasCustomCredentials() || $this->hasGlobalCredentials())) {
            try {
                $this->refreshInstanceFromApi($instance);
            } catch (\Throwable) {
                // keep manual row even if verify fails
            }
        }

        if (! empty($data['set_as_default'])) {
            $this->assignDefaultInstance($instance->instance_name);
        }

        return $instance->fresh();
    }

    public function assignDefaultInstance(string $instanceName): void
    {
        $this->settingsService->updateSettings([
            'evolution_instance_name' => $instanceName,
        ]);

        EvolutionInstance::query()->update(['is_default' => false]);
        EvolutionInstance::where('instance_name', $instanceName)->update(['is_default' => true]);
    }

    public function hasGlobalCredentials(): bool
    {
        $settings = $this->getSettings();

        return ($settings['evolution_base_url'] ?? '') !== '' && ($settings['evolution_api_key'] ?? '') !== '';
    }

    public function provider(?array $override = null): EvolutionApiProvider
    {
        $settings = $override ?? $this->getSettings();

        return $this->providerForInstance($settings['evolution_instance_name'] ?? '', $settings);
    }

    public function providerForInstance(string $instanceName, ?array $settings = null): EvolutionApiProvider
    {
        return WhatsAppProviderFactory::create('evolution', $this->providerConfigForInstance($instanceName, $settings));
    }

    /**
     * @return array{base_url: string, api_key: string, instance_name: string}
     */
    public function providerConfigForInstance(string $instanceName, ?array $settings = null): array
    {
        $settings = $settings ?? $this->getSettings();
        $config = [
            'base_url' => $settings['evolution_base_url'] ?? '',
            'api_key' => $settings['evolution_api_key'] ?? '',
            'instance_name' => $instanceName,
        ];

        $instance = EvolutionInstance::where('instance_name', $instanceName)->first();
        if ($instance instanceof EvolutionInstance && $instance->hasCustomCredentials()) {
            $resolved = $instance->resolveApiConfig();
            $config['base_url'] = $resolved['base_url'];
            $config['api_key'] = $resolved['api_key'];
        }

        return $config;
    }

    public function activeInstanceName(): string
    {
        $settings = $this->getSettings();
        if (! empty($settings['evolution_instance_name'])) {
            return $settings['evolution_instance_name'];
        }

        return EvolutionInstance::defaultInstance()?->instance_name ?? '';
    }

    public function syncInstances(bool $markConfiguredAsDefault = true): array
    {
        $settings = $this->getSettings();
        $configuredName = $settings['evolution_instance_name'] ?? '';
        $list = EvolutionInstanceState::rows($this->client()->fetchInstances());

        if ($list === []) {
            return [];
        }

        $synced = [];
        $remoteNames = [];

        foreach ($list as $instance) {
            $name = EvolutionInstanceState::rowName($instance);
            if ($name === '') {
                continue;
            }

            $remoteNames[] = $name;

            try {
                $synced[] = EvolutionInstance::syncFromApiArray(
                    $instance,
                    $markConfiguredAsDefault && $name === $configuredName
                );
            } catch (\Throwable) {
                // Table may not exist until migration runs
            }
        }

        if ($remoteNames !== []) {
            EvolutionInstance::query()
                ->where('is_manual', false)
                ->whereNotIn('instance_name', $remoteNames)
                ->delete();
        }

        return $synced;
    }

    public function webhookBaseUrl(): string
    {
        $settings = $this->getSettings();
        $custom = trim((string) ($settings['evolution_webhook_base_url'] ?? ''));
        if ($custom !== '') {
            return rtrim($custom, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    public function isLocalWebhookBaseUrl(?string $baseUrl = null): bool
    {
        $base = strtolower($baseUrl ?? $this->webhookBaseUrl());

        return str_contains($base, '127.0.0.1')
            || str_contains($base, 'localhost')
            || str_contains($base, '::1');
    }

    public function webhookUrl(?string $instanceName = null): string
    {
        $instance = $instanceName ?: $this->activeInstanceName();

        return $this->webhookBaseUrl().'/api/webhooks/evolution/'.urlencode($instance);
    }

    public function defaultWebhookEvents(): array
    {
        return [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'CONNECTION_UPDATE',
            'SEND_MESSAGE',
            'QRCODE_UPDATED',
        ];
    }
}
