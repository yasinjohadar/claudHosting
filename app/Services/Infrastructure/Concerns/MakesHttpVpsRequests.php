<?php

namespace App\Services\Infrastructure\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait MakesHttpVpsRequests
{
    /**
     * @param  array<string, string>  $headers
     * @return array{success: bool, status: int, body: mixed, message: string}
     */
    protected function httpJson(string $method, string $url, array $headers = [], array $data = [], int $timeout = 60): array
    {
        try {
            $request = Http::timeout($timeout)->acceptJson();
            foreach ($headers as $key => $value) {
                $request = $request->withHeaders([$key => $value]);
            }

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => empty($data) ? $request->post($url) : $request->asJson()->post($url, $data),
                'PUT' => $request->asJson()->put($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => $request->send($method, $url, ['json' => $data]),
            };

            $body = $response->json();
            $message = is_array($body)
                ? (string) ($body['message'] ?? $body['error']['message'] ?? '')
                : '';

            if (! $response->successful()) {
                if ($message === '' && is_array($body) && isset($body['error'])) {
                    $message = is_string($body['error']) ? $body['error'] : json_encode($body['error']);
                }

                return [
                    'success' => false,
                    'status' => $response->status(),
                    'body' => $body,
                    'message' => $message !== '' ? $message : 'HTTP '.$response->status(),
                ];
            }

            return [
                'success' => true,
                'status' => $response->status(),
                'body' => $body,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 0,
                'body' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function contaboRequestHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'x-request-id' => (string) Str::uuid(),
        ];
    }

    protected function normalizeStatus(string $raw): string
    {
        $s = strtolower(trim($raw));

        return match (true) {
            in_array($s, ['running', 'on', 'active', 'poweron', 'started'], true) => 'running',
            in_array($s, ['stopped', 'off', 'inactive', 'poweroff', 'stopped'], true) => 'stopped',
            str_contains($s, 'start') => 'starting',
            str_contains($s, 'stop') => 'stopping',
            str_contains($s, 'reboot') => 'rebooting',
            default => $s !== '' ? $s : 'unknown',
        };
    }
}
