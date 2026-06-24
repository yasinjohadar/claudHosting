<?php



namespace App\Services\Infrastructure;



use App\Models\VpsMetricSnapshot;
use App\Models\VpsServer;



class VpsSyncService

{

    public function __construct(

        protected VpsProviderRegistry $registry,

        protected VpsMetricsService $metrics,

    ) {}



    /**

     * @return array{success: bool, message: string, synced: int, errors: array<int, string>}

     */

    public function syncAll(?string $providerFilter = null): array

    {

        $synced = 0;

        $errors = [];

        $syncedServers = [];



        $providers = $providerFilter !== null

            ? [$providerFilter => $this->registry->get($providerFilter)]

            : $this->registry->configuredProviders();



        if ($providers === []) {

            return ['success' => false, 'message' => 'لا يوجد مزود مضبوط — راجع الإعدادات', 'synced' => 0, 'errors' => []];

        }



        foreach ($providers as $key => $provider) {

            $result = $provider->listInstances();

            if (! ($result['success'] ?? false)) {

                $errors[] = $key.': '.($result['message'] ?? 'فشل');



                continue;

            }



            foreach ($result['instances'] ?? [] as $instance) {

                if (empty($instance['external_id'])) {

                    continue;

                }



                $server = VpsServer::query()->updateOrCreate(

                    [

                        'provider' => $key,

                        'external_id' => (string) $instance['external_id'],

                    ],

                    [

                        'name' => (string) ($instance['name'] ?? 'VPS'),

                        'ip' => $instance['ip'] ?? null,

                        'region' => $instance['region'] ?? null,

                        'status' => (string) ($instance['status'] ?? 'unknown'),

                        'metadata' => $instance['metadata'] ?? null,

                        'last_synced_at' => now(),

                    ]

                );

                $syncedServers[] = $server;

                $synced++;

            }

        }



        foreach ($syncedServers as $server) {

            $this->captureMetricsSnapshot($server);

        }



        $message = 'تمت مزامنة '.$synced.' سيرفر';

        if ($errors !== []) {

            $message .= ' — تحذيرات: '.implode('; ', $errors);

        }



        return [

            'success' => $errors === [] || $synced > 0,

            'message' => $message,

            'synced' => $synced,

            'errors' => $errors,

        ];

    }



    /**

     * @return array{success: bool, message: string}

     */

    public function refreshOne(VpsServer $server): array

    {

        $provider = $this->registry->get($server->provider);

        $result = $provider->getInstance($server->external_id);



        if (! ($result['success'] ?? false)) {

            return ['success' => false, 'message' => $result['message'] ?? 'فشل التحديث'];

        }



        $instance = $result['instance'] ?? [];

        $server->update([

            'name' => (string) ($instance['name'] ?? $server->name),

            'ip' => $instance['ip'] ?? $server->ip,

            'region' => $instance['region'] ?? $server->region,

            'status' => (string) ($instance['status'] ?? $server->status),

            'metadata' => array_merge($server->metadata ?? [], $instance['metadata'] ?? []),

            'last_synced_at' => now(),

        ]);



        $server->refresh();

        $this->captureMetricsSnapshot($server);



        return ['success' => true, 'message' => 'تم تحديث الحالة'];

    }



    protected function captureMetricsSnapshot(VpsServer $server): void
    {
        if ($server->provider === 'netcup') {
            VpsMetricSnapshot::query()
                ->where('vps_server_id', $server->id)
                ->where('payload->source', 'netcup_scp')
                ->delete();
        }

        if (blank($server->ip) || ! $server->isRunning()) {
            return;
        }

        try {
            $this->metrics->recordSnapshotForServer($server);
        } catch (\Throwable) {
            // Metrics are optional during sync.
        }
    }
}


