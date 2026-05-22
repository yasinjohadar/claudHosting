<?php

namespace App\Http\Controllers\Admin\Cloudflare;

use App\Http\Controllers\Controller;
use App\Services\CloudflareApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CloudflareZoneController extends Controller
{
    public function __construct(protected CloudflareApiService $cloudflare)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! $this->cloudflare->isConfigured()) {
            return redirect()->route('admin.cloudflare.settings.index')
                ->with('error', 'يرجى ضبط إعدادات Cloudflare أولاً');
        }

        if ($request->boolean('refresh')) {
            $this->cloudflare->clearCaches();
        }

        $zones = $this->cloudflare->listAllZones(
            $request->query('name'),
            $request->query('status')
        );

        $error = null;
        if (empty($zones) && ! $this->cloudflare->ping()) {
            $error = 'فشل جلب Zones — تحقق من إعدادات Cloudflare والصلاحيات';
        }

        $stats = [
            'total' => count($zones),
            'active' => count(array_filter($zones, fn ($z) => ($z['status'] ?? '') === 'active')),
            'pending' => count(array_filter($zones, fn ($z) => ($z['status'] ?? '') === 'pending')),
        ];

        return view('admin.cloudflare.zones.index', compact('zones', 'stats', 'error'));
    }

    public function show(string $zoneId)
    {
        if (! $this->cloudflare->isConfigured()) {
            return redirect()->route('admin.cloudflare.settings.index')
                ->with('error', 'يرجى ضبط إعدادات Cloudflare أولاً');
        }

        $zoneResponse = $this->cloudflare->getZone($zoneId);
        $zone = ($zoneResponse['success'] ?? false) ? ($zoneResponse['data']['result'] ?? null) : null;

        if (! is_array($zone)) {
            return redirect()->route('admin.cloudflare.zones.index')
                ->with('error', $zoneResponse['message'] ?? 'النطاق غير موجود');
        }

        $dnsRecords = $this->cloudflare->listDnsRecords($zoneId);
        $dnsError = null;
        if (isset($dnsRecords['_error'])) {
            $dnsError = $dnsRecords['_error'];
            $dnsRecords = [];
        }

        $sslResponse = $this->cloudflare->getZoneSsl($zoneId);
        $ssl = ($sslResponse['success'] ?? false) ? ($sslResponse['data']['result'] ?? null) : null;

        return view('admin.cloudflare.zones.show', compact('zone', 'zoneId', 'dnsRecords', 'dnsError', 'ssl'));
    }

    public static function formatDate(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return $value;
        }
    }
}
