<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsService
{
    protected string $url;

    protected string $identifier;

    protected string $secret;

    protected ?string $accessKey;

    public function __construct()
    {
        $this->url = config('services.whmcs.url', '');
        $this->identifier = config('services.whmcs.identifier', '');
        $this->secret = config('services.whmcs.secret', '');
        $this->accessKey = config('services.whmcs.access_key') ?: null;
    }

    /**
     * استدعاء WHMCS API.
     *
     * @param string $action اسم الـ action (مثل GetCurrencies, GetClients)
     * @param array $params معاملات إضافية للطلب
     * @return array الاستجابة كـ array
     */
    public function call(string $action, array $params = []): array
    {
        $body = [
            'identifier' => $this->identifier,
            'secret' => $this->secret,
            'action' => $action,
            'responsetype' => 'json',
        ];

        if ($this->accessKey !== null && $this->accessKey !== '') {
            $body['accesskey'] = $this->accessKey;
        }

        $body = array_merge($body, $params);

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->url, $body);

            $data = $response->json();
            if (! is_array($data)) {
                $data = [];
            }

            if ($response->successful()) {
                return $data;
            }

            Log::warning('WHMCS API non-success', [
                'action' => $action,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'result' => 'error',
                'message' => $data['message'] ?? 'Request failed with status: ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('WHMCS API exception', [
                'action' => $action,
                'message' => $e->getMessage(),
            ]);

            return [
                'result' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
