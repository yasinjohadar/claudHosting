<?php

namespace App\Http\Controllers\Admin\Namecom;

use App\Http\Controllers\Controller;
use App\Services\NamecomApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NamecomDomainController extends Controller
{
    public function __construct(protected NamecomApiService $namecom)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! $this->namecom->isConfigured()) {
            return redirect()->route('admin.namecom.settings.index')
                ->with('error', 'يرجى ضبط إعدادات name.com أولاً');
        }

        $forceRefresh = $request->boolean('refresh');
        if ($forceRefresh) {
            $this->namecom->clearCaches();
        }

        $meta = $this->namecom->listAllDomainsWithMeta($forceRefresh);
        $domains = $meta['domains'];
        $error = $meta['error'];

        if (empty($domains) && ! $forceRefresh && $error === null) {
            $this->namecom->clearCaches();
            $meta = $this->namecom->listAllDomainsWithMeta(true);
            $domains = $meta['domains'];
            $error = $meta['error'];
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $domains = array_values(array_filter($domains, function (array $d) use ($q) {
                $name = self::domainName($d);

                return stripos($name, $q) !== false;
            }));
        }

        $sort = $request->query('sort', 'expires');
        $sortDir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $domains = $this->sortDomains($domains, $sort, $sortDir);

        return view('admin.namecom.domains.index', compact(
            'domains',
            'error',
            'sort',
            'sortDir',
            'q'
        ));
    }

    public function show(string $domain)
    {
        if (! $this->namecom->isConfigured()) {
            return redirect()->route('admin.namecom.settings.index')
                ->with('error', 'يرجى ضبط إعدادات name.com أولاً');
        }

        $domainName = rawurldecode($domain);
        $result = $this->namecom->getDomain($domainName);

        if ($result['error'] !== null || ! is_array($result['domain'])) {
            return redirect()->route('admin.namecom.domains.index')
                ->with('error', $result['error'] ?? 'تعذر جلب تفاصيل النطاق');
        }

        $dns = $this->namecom->listDnsRecords($domainName);

        return view('admin.namecom.domains.show', [
            'domain' => $result['domain'],
            'domainName' => self::domainName($result['domain']),
            'dnsRecords' => $dns['records'],
            'dnsError' => $dns['error'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $domain
     */
    public static function domainName(array $domain): string
    {
        return (string) ($domain['domainName'] ?? $domain['name'] ?? '—');
    }

    /**
     * @param  array<int, array<string, mixed>>  $domains
     * @return array<int, array<string, mixed>>
     */
    protected function sortDomains(array $domains, string $sort, string $dir): array
    {
        if ($sort !== 'expires' || count($domains) < 2) {
            return $domains;
        }

        usort($domains, function (array $a, array $b) use ($dir): int {
            $tsA = self::expireTimestamp($a);
            $tsB = self::expireTimestamp($b);

            if ($tsA === $tsB) {
                return strcmp(self::domainName($a), self::domainName($b));
            }

            if ($tsA === null) {
                return 1;
            }
            if ($tsB === null) {
                return -1;
            }

            $cmp = $tsA <=> $tsB;

            return $dir === 'desc' ? -$cmp : $cmp;
        });

        return $domains;
    }

    /**
     * @param  array<string, mixed>  $domain
     */
    protected static function expireTimestamp(array $domain): ?int
    {
        $raw = $domain['expireDate'] ?? $domain['expires_at'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->timestamp;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{label: string, badge_class: string, is_active: bool}
     */
    public static function formatStatus(array $domain): array
    {
        $expireRaw = $domain['expireDate'] ?? $domain['expires_at'] ?? null;
        if ($expireRaw) {
            try {
                $expires = Carbon::parse($expireRaw);
                if ($expires->isPast()) {
                    return ['label' => 'منتهي', 'badge_class' => 'bg-danger-transparent text-danger', 'is_active' => false];
                }

                return ['label' => 'فعال', 'badge_class' => 'bg-success-transparent text-success', 'is_active' => true];
            } catch (\Throwable) {
                // fall through
            }
        }

        return ['label' => 'فعال', 'badge_class' => 'bg-success-transparent text-success', 'is_active' => true];
    }

    public static function isExpiringSoon(?string $expiresAt, int $withinDays = 30): bool
    {
        if (! $expiresAt) {
            return false;
        }

        try {
            $expires = Carbon::parse($expiresAt);

            return $expires->isFuture() && $expires->lte(now()->addDays($withinDays));
        } catch (\Throwable) {
            return false;
        }
    }

    public static function formatDate(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }

    public static function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return is_numeric($value) ? number_format((float) $value, 2).' USD' : (string) $value;
    }

    public static function formatBool(mixed $value): string
    {
        return ! empty($value) ? 'نعم' : 'لا';
    }
}
