<?php

namespace App\Http\Controllers;

use App\Services\WhmcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * مثال استخدام WhmcsService لاختبار الاتصال بـ WHMCS API.
 */
class WhmcsExampleController extends Controller
{
    public function __construct(
        protected WhmcsService $whmcs
    ) {}

    /**
     * اختبار الاتصال عبر action = GetCurrencies
     */
    public function testConnection(Request $request): JsonResponse
    {
        $response = $this->whmcs->call('GetCurrencies', []);

        if (isset($response['result']) && $response['result'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => 'تم الاتصال بنجاح بـ WHMCS',
                'data' => $response,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'فشل الاتصال',
            'data' => $response,
        ], 422);
    }

    /**
     * تشخيص: يعرض الاستجابة الخام من WHMCS (الحالة + النص) لمعرفة سبب الرفض.
     */
    public function debug(Request $request): JsonResponse
    {
        $url = config('services.whmcs.url');
        $body = [
            'identifier' => config('services.whmcs.identifier'),
            'secret' => config('services.whmcs.secret'),
            'action' => 'GetCurrencies',
            'responsetype' => 'json',
        ];
        if (config('services.whmcs.access_key')) {
            $body['accesskey'] = config('services.whmcs.access_key');
        }

        $response = Http::asForm()->timeout(15)->post($url, $body);

        return response()->json([
            'request_url' => $url,
            'request_has_accesskey' => isset($body['accesskey']),
            'http_status' => $response->status(),
            'response_body_raw' => $response->body(),
            'response_body_length' => strlen($response->body()),
            'response_json' => $response->json(),
        ]);
    }
}
