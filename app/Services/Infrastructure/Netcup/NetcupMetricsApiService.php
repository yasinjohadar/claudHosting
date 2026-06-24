<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupMetricsApiService
{
    use NetcupScpHelpers;

    public function __construct(protected NetcupScpClient $client) {}

    public function cpu(string $externalId, array $query = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/metrics/cpu', $this->metricsQuery($query)));
    }

    public function disk(string $externalId, array $query = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/metrics/disk', $this->metricsQuery($query)));
    }

    public function network(string $externalId, array $query = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/metrics/network', $this->metricsQuery($query)));
    }

    public function networkPackets(string $externalId, array $query = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/metrics/network/packet', $this->metricsQuery($query)));
    }

    public function isEmptyPayload(mixed $data): bool
    {
        if ($data === null) {
            return true;
        }

        if (is_array($data)) {
            return $data === [];
        }

        if (is_object($data)) {
            return (array) $data === [];
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{hours: int}
     */
    public function metricsQuery(array $query = []): array
    {
        $default = (int) config('infrastructure.netcup.metrics_default_hours', 6);
        $hours = isset($query['hours']) ? (int) $query['hours'] : $default;

        return ['hours' => max(1, min(1440, $hours))];
    }
}
