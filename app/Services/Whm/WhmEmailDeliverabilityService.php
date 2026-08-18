<?php

namespace App\Services\Whm;

use App\Models\WhmAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Read-only "Email Deliverability" data (DKIM / SPF / PTR / Mail HELO) per account,
 * mirroring cPanel's Email Deliverability interface.
 *
 * The exact key names of EmailAuth::validate_current_setup vary between cPanel
 * versions, so every read goes through an alias table and every value through
 * flattenValue(). When the module is unavailable we still derive real current
 * values by reading the DNS zone. Nothing here ever throws.
 *
 * Result = array{
 *   success: bool, configured: bool, available: bool,
 *   message: string,
 *   fetched_at: ?string, fetched_at_human: ?string,
 *   server: array{hostname: ?string, ip: ?string, ptr: ?string, ptr_state: string},
 *   domains: list<Domain>,
 *   warnings: list<string>,
 * }
 *
 * Domain = array{
 *   domain: string, type: string, is_main: bool,
 *   helo: ?string, ip: ?string,
 *   overall: string, message: ?string,
 *   checks: list<Check>,
 * }
 *
 * Check = array{
 *   key: string, label: string, context: ?string,
 *   state: string, state_label: string, badge: string, raw_state: ?string,
 *   record_type: ?string,
 *   expected_name: ?string, expected_value: ?string, current_value: ?string,
 *   matches: ?bool, message: ?string,
 *   source: string,           // provenance of current_value
 *   expected_source: string,  // provenance of expected_value — 'derived' must be surfaced
 * }
 */
class WhmEmailDeliverabilityService
{
    /** Wall-clock budget for the whole per-domain fan-out, in seconds. */
    protected const FETCH_BUDGET_SECONDS = 20.0;

    /** Subdomains that are never mail-sending identities. */
    protected const SERVICE_PREFIXES = ['mail.', 'www.', 'webmail.', 'cpanel.', 'webdisk.', 'autodiscover.', 'autoconfig.'];

    /** @var array<string, list<string>> */
    protected const SECTION_ALIASES = [
        'dkim' => ['dkim', 'DKIM', 'domain_keys', 'dkim_records', 'dkim_validation'],
        'spf' => ['spf', 'SPF', 'spf_records', 'spf_validation'],
        'ptr' => ['ptr', 'PTR', 'reverse_dns', 'rdns', 'ptr_records', 'ptr_validation'],
        'helo' => ['helo', 'ehlo', 'mail_helo', 'mail_hostname', 'hostname'],
    ];

    protected const EXPECTED_VALUE_KEYS = [
        'expected', 'expected_record', 'expected_value', 'suggested_record', 'suggested',
        'recommended', 'record', 'value', 'p', 'public_key', 'dkim_public_key',
    ];

    protected const CURRENT_VALUE_KEYS = [
        'current', 'current_record', 'current_value', 'found', 'actual', 'existing', 'txtdata',
    ];

    protected const EXPECTED_NAME_KEYS = [
        'expected_name', 'record_name', 'dname', 'ptr_domain', 'name', 'domain', 'zone',
    ];

    protected const STATE_KEYS = ['state', 'status', 'result', 'validation_state', 'is_valid', 'valid'];

    protected const MESSAGE_KEYS = ['message', 'reason', 'error', 'errors', 'warning', 'details'];

    protected const STATES_OK = [
        'VALID', 'OK', 'PASS', 'PASSED', 'CONFIGURED', 'ENABLED', 'TRUE', '1', 'YES', 'MATCH',
    ];

    protected const STATES_PROBLEM = [
        'MISSING', 'INVALID', 'NOT_FOUND', 'NOTFOUND', 'MISMATCH', 'FAIL', 'FAILED',
        'PERMERROR', 'TEMPERROR', 'NO_DNS_ENTRY', 'NONEXISTENT', 'NO_KEY_CONFIGURED',
        'DUPLICATE_KEYS', 'FALSE', '0', 'NO',
    ];

    /** Needles that mean "this cPanel has no EmailAuth module". */
    protected const MODULE_MISSING_NEEDLES = [
        'emailauth', 'cannot find module', 'does not exist', 'undefined subroutine',
        'unknown module', 'not supported',
    ];

    public function __construct(
        protected WhmApiService $api,
        protected WhmSettingsService $settings
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forAccount(WhmAccount $account, bool $fresh = false): array
    {
        if ($account->status === 'terminated') {
            return $this->emptyResult('الحساب محذوف', configured: true);
        }

        if (! $this->api->isConfigured()) {
            return $this->emptyResult(
                'إعدادات WHM غير مكتملة — اضبطها من لوحة التحكم → WHM / cPanel → إعدادات WHM',
                configured: false
            );
        }

        $cacheKey = $this->cacheKey($account);
        $ttl = max(30, (int) config('whm.email_deliverability_cache_seconds', 300));

        if ($fresh) {
            Cache::forget($cacheKey);
        } else {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $data = $this->fetch($account);

        // Only cache successes — a transient connection failure must not be pinned for the whole TTL.
        if ($data['success'] ?? false) {
            Cache::put($cacheKey, $data, $ttl);
        }

        return $data;
    }

    public function forgetCache(WhmAccount $account): void
    {
        Cache::forget($this->cacheKey($account));
    }

    protected function cacheKey(WhmAccount $account): string
    {
        $host = $this->settings->getConnectionConfig()['host'] ?? 'default';

        return 'whm_email_deliv_'.md5($host.'|'.$account->username);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetch(WhmAccount $account): array
    {
        $warnings = [];
        $available = false;
        $zoneYieldedData = false;

        [$domains, $resolveWarnings] = $this->resolveDomains($account);
        $warnings = array_merge($warnings, $resolveWarnings);

        $server = $this->serverContext($account);
        $budgetEndsAt = microtime(true) + self::FETCH_BUDGET_SECONDS;

        $blocks = [];
        foreach ($domains as $entry) {
            if (microtime(true) >= $budgetEndsAt) {
                $blocks[] = $this->timedOutBlock($entry, $server);

                continue;
            }

            $raw = [];
            $domainMessage = null;

            $result = $this->api->emailAuthValidateSetup($account->username, $entry['domain']);

            if (config('whm.email_deliverability_debug')) {
                Log::debug('WHM EmailAuth validate_current_setup', [
                    'user' => $account->username,
                    'domain' => $entry['domain'],
                    'success' => $result['success'] ?? false,
                    'payload' => $result['payload'] ?? ($result['message'] ?? null),
                ]);
            }

            if ($result['success'] ?? false) {
                $available = true;
                $raw = $this->pickDomainRow($result['payload'] ?? null, $entry['domain']);
            } else {
                $message = (string) ($result['message'] ?? 'فشل جلب بيانات البريد');
                if ($this->looksLikeModuleMissing($message)) {
                    $warnings[] = 'وحدة EmailAuth غير متاحة على هذا السيرفر — تم الاعتماد على قراءة منطقة DNS';
                } else {
                    $domainMessage = $message;
                }
            }

            $block = $this->buildDomainBlock($entry, $raw, $server, $domainMessage);
            if ($this->fillFromZone($account, $block)) {
                $zoneYieldedData = true;
            }
            $this->finalizeBlock($block);

            $blocks[] = $block;
        }

        $warnings = array_values(array_unique($warnings));

        // "Success" means we actually learned something from the server. A dead WHM host
        // still yields four all-unknown checks per domain; caching that for the whole TTL
        // would pin a useless panel across a transient outage.
        $success = $blocks !== [] && ($available || $zoneYieldedData);

        return [
            'success' => $success,
            'configured' => true,
            'available' => $available,
            'message' => $success ? 'تم جلب بيانات البريد' : 'تعذّر جلب بيانات البريد من WHM',
            'fetched_at' => now()->toIso8601String(),
            'fetched_at_human' => now()->format('Y-m-d H:i'),
            'server' => $server,
            'domains' => $blocks,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{hostname: ?string, ip: ?string, ptr: ?string, ptr_state: string}
     */
    /**
     * Public accessor for the server context (mail hostname / IP / PTR). This is the
     * only source of truth for the account's mail hostname anywhere in the app, so the
     * mail-DNS sync flow needs it too.
     *
     * @return array{hostname: ?string, ip: ?string, ptr: ?string, ptr_state: string}
     */
    public function serverContextForAccount(WhmAccount $account): array
    {
        return $this->serverContext($account);
    }

    protected function serverContext(WhmAccount $account): array
    {
        $ip = null;
        $summary = $this->api->accountSummary($account->username);
        if ($summary['success'] ?? false) {
            $value = trim((string) ($summary['summary']['ip'] ?? ''));
            $ip = $value !== '' ? $value : null;
        }

        $hostname = null;
        $host = $this->api->serverHostname();
        if ($host['success'] ?? false) {
            $hostname = $host['hostname'];
        } else {
            $configured = (string) ($this->settings->getConnectionConfig()['host'] ?? '');
            $parsed = $configured !== '' ? parse_url($configured, PHP_URL_HOST) : null;
            $hostname = is_string($parsed) && $parsed !== '' ? $parsed : null;
        }

        $ptr = $this->localPtr($ip);
        $ptrState = 'unknown';
        if ($ptr !== null && $hostname !== null) {
            $ptrState = strcasecmp($ptr, $hostname) === 0 ? 'ok' : 'problem';
        }

        return [
            'hostname' => $hostname,
            'ip' => $ip,
            'ptr' => $ptr,
            'ptr_state' => $ptrState,
        ];
    }

    /**
     * Reverse DNS from the application server. Runs once per fetch, behind the cache,
     * and can be switched off — gethostbyaddr() has no timeout parameter.
     */
    protected function localPtr(?string $ip): ?string
    {
        if ($ip === null || ! config('whm.email_deliverability_local_ptr', true)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        try {
            $host = gethostbyaddr($ip);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($host) || $host === '' || $host === $ip) {
            return null;
        }

        return rtrim($host, '.');
    }

    /**
     * @return array{0: list<array{domain: string, type: string}>, 1: list<string>}
     */
    protected function resolveDomains(WhmAccount $account): array
    {
        $warnings = [];
        $found = $this->domainsFromDomainInfo($account);

        if ($found === []) {
            $found = $this->domainsFromEmailAuth($account);
        }

        if ($found === []) {
            $main = strtolower(trim((string) $account->domain));
            $found = $main !== '' ? [['domain' => $main, 'type' => 'main']] : [];
        }

        $mainDomain = strtolower(trim((string) $account->domain));
        $clean = [];
        foreach ($found as $entry) {
            $domain = strtolower(trim((string) ($entry['domain'] ?? '')));
            if ($domain === '' || $this->isServiceSubdomain($domain, $mainDomain)) {
                continue;
            }
            if (isset($clean[$domain])) {
                continue;
            }
            $clean[$domain] = [
                'domain' => $domain,
                'type' => (string) ($entry['type'] ?? 'unknown'),
            ];
        }

        $list = array_values($clean);

        // Main domain first, then alphabetical.
        usort($list, function (array $a, array $b) use ($mainDomain): int {
            $aMain = $a['domain'] === $mainDomain ? 0 : 1;
            $bMain = $b['domain'] === $mainDomain ? 0 : 1;

            return $aMain <=> $bMain ?: strcmp($a['domain'], $b['domain']);
        });

        $max = max(1, (int) config('whm.email_deliverability_max_domains', 25));
        if (count($list) > $max) {
            $warnings[] = 'الحساب يملك '.count($list).' نطاقاً — تم عرض أول '.$max.' نطاق فقط';
            $list = array_slice($list, 0, $max);
        }

        return [$list, $warnings];
    }

    /**
     * @return list<array{domain: string, type: string}>
     */
    protected function domainsFromDomainInfo(WhmAccount $account): array
    {
        $response = $this->api->domainsData($account->username);
        if (! ($response['success'] ?? false)) {
            return [];
        }

        $payload = $response['payload'] ?? null;
        if (! is_array($payload)) {
            return [];
        }

        $out = [];

        $main = $payload['main_domain'] ?? null;
        $mainDomain = $this->domainFromEntry($main);
        if ($mainDomain !== null) {
            $out[] = ['domain' => $mainDomain, 'type' => 'main'];
        }

        $groups = [
            'addon_domains' => 'addon',
            'sub_domains' => 'sub',
            'parked_domains' => 'parked',
        ];
        foreach ($groups as $group => $type) {
            $items = $payload[$group] ?? [];
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                $domain = $this->domainFromEntry($item);
                if ($domain !== null) {
                    $out[] = ['domain' => $domain, 'type' => $type];
                }
            }
        }

        if ($out !== []) {
            return $out;
        }

        // Flat list of rows shape.
        if (array_is_list($payload)) {
            foreach ($payload as $item) {
                $domain = $this->domainFromEntry($item);
                if ($domain !== null) {
                    $out[] = ['domain' => $domain, 'type' => 'unknown'];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array{domain: string, type: string}>
     */
    protected function domainsFromEmailAuth(WhmAccount $account): array
    {
        $response = $this->api->emailAuthValidateSetup($account->username);
        if (! ($response['success'] ?? false)) {
            return [];
        }

        $payload = $response['payload'] ?? null;
        if (! is_array($payload)) {
            return [];
        }

        $out = [];
        foreach ($payload as $key => $row) {
            $domain = $this->domainFromEntry($row);
            if ($domain === null && is_string($key)) {
                $domain = strtolower(trim($key));
                $domain = $domain !== '' ? $domain : null;
            }
            if ($domain !== null) {
                $out[] = ['domain' => $domain, 'type' => 'unknown'];
            }
        }

        return $out;
    }

    /**
     * An entry may be a plain domain string or an assoc row.
     */
    protected function domainFromEntry(mixed $entry): ?string
    {
        if (is_string($entry)) {
            $entry = strtolower(trim($entry));

            return $entry !== '' ? $entry : null;
        }

        if (! is_array($entry)) {
            return null;
        }

        foreach (['domain', 'servername', 'name', 'zone'] as $key) {
            $value = $entry[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return strtolower(trim($value));
            }
        }

        return null;
    }

    protected function isServiceSubdomain(string $domain, string $mainDomain): bool
    {
        if ($domain === $mainDomain) {
            return false;
        }

        foreach (self::SERVICE_PREFIXES as $prefix) {
            if (str_starts_with($domain, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tolerate: a list of rows, a map keyed by domain, or a single bare row.
     *
     * @return array<string, mixed>
     */
    protected function pickDomainRow(mixed $payload, string $domain): array
    {
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        // Map keyed by domain.
        if (! array_is_list($payload)) {
            foreach ($payload as $key => $row) {
                if (is_string($key) && strcasecmp(trim($key), $domain) === 0 && is_array($row)) {
                    return $row;
                }
            }

            // A single bare row carrying our section keys.
            if ($this->hasAnySection($payload)) {
                return $payload;
            }

            return [];
        }

        $rows = array_values(array_filter($payload, 'is_array'));
        foreach ($rows as $row) {
            $rowDomain = $this->domainFromEntry($row);
            if ($rowDomain !== null && strcasecmp($rowDomain, $domain) === 0) {
                return $row;
            }
        }

        // Exactly one row and it carries no domain key — assume it is ours.
        if (count($rows) === 1 && $this->domainFromEntry($rows[0]) === null) {
            return $rows[0];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function hasAnySection(array $row): bool
    {
        foreach (self::SECTION_ALIASES as $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $row)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array{domain: string, type: string}  $entry
     * @param  array<string, mixed>  $raw
     * @param  array{hostname: ?string, ip: ?string, ptr: ?string, ptr_state: string}  $server
     * @return array<string, mixed>
     */
    protected function buildDomainBlock(array $entry, array $raw, array $server, ?string $message): array
    {
        $domain = $entry['domain'];
        $helo = $this->firstString($raw, self::SECTION_ALIASES['helo']) ?? $server['hostname'];

        $ctx = [
            'domain' => $domain,
            'ip' => $server['ip'],
            'hostname' => $server['hostname'],
            'ptr' => $server['ptr'],
            'ptr_state' => $server['ptr_state'],
            'helo' => $helo,
        ];

        $checks = [];
        $checks[] = $this->buildCheck('dkim', $this->section($raw, 'dkim'), $ctx);
        $checks[] = $this->buildCheck('spf', $this->section($raw, 'spf'), $ctx);

        foreach ($this->buildPtrChecks($this->section($raw, 'ptr'), $ctx) as $ptrCheck) {
            $checks[] = $ptrCheck;
        }

        $checks[] = $this->buildHeloCheck($ctx);

        return [
            'domain' => $domain,
            'type' => $entry['type'],
            'is_main' => $entry['type'] === 'main',
            'helo' => $helo,
            'ip' => $server['ip'],
            'overall' => 'unknown',
            'message' => $message,
            'checks' => $checks,
        ];
    }

    /**
     * @param  array{domain: string, type: string}  $entry
     * @param  array{hostname: ?string, ip: ?string, ptr: ?string, ptr_state: string}  $server
     * @return array<string, mixed>
     */
    protected function timedOutBlock(array $entry, array $server): array
    {
        return [
            'domain' => $entry['domain'],
            'type' => $entry['type'],
            'is_main' => $entry['type'] === 'main',
            'helo' => $server['hostname'],
            'ip' => $server['ip'],
            'overall' => 'unknown',
            'message' => 'انتهت المدة المخصصة للجلب — اضغط تحديث',
            'checks' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function section(array $raw, string $key): mixed
    {
        foreach (self::SECTION_ALIASES[$key] as $alias) {
            if (! array_key_exists($alias, $raw)) {
                continue;
            }
            $value = $raw[$alias];
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    protected function buildCheck(string $key, mixed $sectionValue, array $ctx): array
    {
        $labels = [
            'dkim' => 'DKIM',
            'spf' => 'SPF',
            'ptr' => 'PTR (السجل العكسي)',
            'helo' => 'Mail HELO',
        ];
        $recordTypes = ['dkim' => 'TXT', 'spf' => 'TXT', 'ptr' => 'PTR', 'helo' => null];

        $check = [
            'key' => $key,
            'label' => $labels[$key] ?? strtoupper($key),
            'context' => null,
            'state' => 'unknown',
            'state_label' => $this->stateLabel('unknown'),
            'badge' => $this->stateBadge('unknown'),
            'raw_state' => null,
            'record_type' => $recordTypes[$key] ?? null,
            'expected_name' => $this->defaultExpectedName($key, $ctx),
            'expected_value' => null,
            'current_value' => null,
            'matches' => null,
            'message' => null,
            'source' => 'api',
            'expected_source' => 'api',
        ];

        if ($sectionValue === null) {
            $check['message'] = 'لم يُرجع السيرفر بيانات هذا الفحص';

            return $check;
        }

        if (is_string($sectionValue)) {
            $check['current_value'] = $this->flattenValue($sectionValue);

            return $this->resolveState($check);
        }

        if (is_array($sectionValue) && array_is_list($sectionValue)) {
            if (count($sectionValue) > 1) {
                $check['message'] = 'تم العثور على أكثر من سجل — يُعرض الأول';
            }
            $sectionValue = $sectionValue[0] ?? null;
            if (! is_array($sectionValue)) {
                $check['current_value'] = $this->flattenValue($sectionValue);

                return $this->resolveState($check);
            }
        }

        if (! is_array($sectionValue)) {
            return $this->resolveState($check);
        }

        $check['expected_value'] = $this->firstString($sectionValue, self::EXPECTED_VALUE_KEYS);
        $check['current_value'] = $this->firstString($sectionValue, self::CURRENT_VALUE_KEYS);

        $expectedName = $this->firstString($sectionValue, self::EXPECTED_NAME_KEYS);
        if ($expectedName !== null) {
            $check['expected_name'] = $expectedName;
        }

        $rawState = $this->firstString($sectionValue, self::STATE_KEYS);
        if ($rawState !== null) {
            $check['raw_state'] = $rawState;
            $check['state'] = $this->normalizeState($rawState);
        }

        $check['message'] = $this->firstString($sectionValue, self::MESSAGE_KEYS) ?? $check['message'];

        // DKIM key material sometimes arrives bare (just the base64 blob).
        if ($key === 'dkim' && $check['expected_value'] !== null && ! preg_match('/^\s*"?v=/i', $check['expected_value'])) {
            $check['expected_value'] = 'v=DKIM1; k=rsa; p='.ltrim($check['expected_value'], "p= \t");
        }

        return $this->resolveState($check);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return list<array<string, mixed>>
     */
    protected function buildPtrChecks(mixed $sectionValue, array $ctx): array
    {
        // Several IPs => one check per IP, each labelled with its address.
        if (is_array($sectionValue) && array_is_list($sectionValue) && count($sectionValue) > 1) {
            $out = [];
            foreach ($sectionValue as $item) {
                $check = $this->buildCheck('ptr', $item, $ctx);
                $ip = is_array($item)
                    ? $this->firstString($item, ['ip_address', 'ip', 'address'])
                    : null;
                $check['context'] = $ip ?? $ctx['ip'];
                $out[] = $check;
            }

            return $out;
        }

        $check = $this->buildCheck('ptr', $sectionValue, $ctx);
        $check['context'] = $ctx['ip'];

        // Fall back to the locally resolved PTR when WHM gave us nothing.
        if ($check['current_value'] === null && $ctx['ptr'] !== null) {
            $check['current_value'] = $ctx['ptr'];
            $check['source'] = 'local';
            $check['message'] = 'مقروء من سيرفر التطبيق لا من WHM';
            if ($check['expected_value'] === null && $ctx['hostname'] !== null) {
                $check['expected_value'] = $ctx['hostname'];
            }
            $check = $this->resolveState($check);
        }

        return [$check];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    protected function buildHeloCheck(array $ctx): array
    {
        $check = [
            'key' => 'helo',
            'label' => 'Mail HELO',
            'context' => null,
            'state' => 'unknown',
            'state_label' => $this->stateLabel('unknown'),
            'badge' => $this->stateBadge('unknown'),
            'raw_state' => null,
            'record_type' => null,
            'expected_name' => null,
            'expected_value' => $ctx['helo'],
            'current_value' => null,
            'matches' => null,
            'message' => null,
            'source' => 'api',
            'expected_source' => 'api',
        ];

        if ($ctx['helo'] === null) {
            $check['message'] = 'اسم مضيف السيرفر غير متوفر';

            return $check;
        }

        if ($ctx['ptr'] !== null) {
            $check['current_value'] = $ctx['ptr'];
            $check['state'] = strcasecmp($ctx['ptr'], (string) $ctx['helo']) === 0 ? 'ok' : 'problem';
            $check['message'] = $check['state'] === 'ok'
                ? 'السجل العكسي مطابق لاسم المضيف'
                : 'السجل العكسي لا يطابق اسم المضيف — راجع مزوّد السيرفر';
        } else {
            $check['message'] = 'تعذّر التحقق من السجل العكسي';
        }

        $check['state_label'] = $this->stateLabel($check['state']);
        $check['badge'] = $this->stateBadge($check['state']);

        return $check;
    }

    /**
     * Compare expected vs current and let the comparison override an unrecognised state.
     * This is what keeps the tab useful when the state vocabulary is unknown.
     *
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    protected function resolveState(array $check): array
    {
        $expected = $check['expected_value'];
        $current = $check['current_value'];

        if ($expected !== null && $current !== null) {
            $check['matches'] = $this->canonical($expected) === $this->canonical($current);
            if ($check['state'] === 'unknown') {
                $check['state'] = $check['matches'] ? 'ok' : 'problem';
            }
        } elseif ($current === null && $check['state'] === 'unknown' && $expected !== null) {
            $check['state'] = 'problem';
            $check['message'] ??= 'السجل غير موجود حالياً';
        }

        $check['state_label'] = $this->stateLabel($check['state']);
        $check['badge'] = $this->stateBadge($check['state']);

        return $check;
    }

    /**
     * Read the real current DKIM/SPF values out of the DNS zone when the API gave none.
     *
     * @param  array<string, mixed>  $block
     * @return bool whether the zone actually yielded a value
     */
    protected function fillFromZone(WhmAccount $account, array &$block): bool
    {
        $needsZone = false;
        foreach ($block['checks'] as $check) {
            if (in_array($check['key'], ['dkim', 'spf'], true) && $check['current_value'] === null) {
                $needsZone = true;
                break;
            }
        }

        if (! $needsZone) {
            $this->fillExpectedFallbacks($account, $block);

            return false;
        }

        $domain = $block['domain'];
        $zone = $this->api->dumpZone($domain);
        $records = ($zone['success'] ?? false) && is_array($zone['records'] ?? null) ? $zone['records'] : [];

        $spfCurrent = null;
        $dkimCurrent = null;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            if (strcasecmp((string) ($record['type'] ?? ''), 'TXT') !== 0) {
                continue;
            }

            $name = strtolower(rtrim(trim((string) ($record['name'] ?? $record['dname'] ?? '')), '.'));
            $value = $this->flattenValue($record['txtdata'] ?? null);
            if ($value === null) {
                continue;
            }

            $isRoot = $name === '' || $name === '@' || $name === strtolower($domain);
            if ($isRoot && $spfCurrent === null && stripos($value, 'v=spf1') === 0) {
                $spfCurrent = $value;

                continue;
            }

            if ($dkimCurrent === null && str_starts_with($name, 'default._domainkey')) {
                $dkimCurrent = $value;
            }
        }

        $filled = false;

        foreach ($block['checks'] as $i => $check) {
            if ($check['current_value'] !== null) {
                continue;
            }

            if ($check['key'] === 'spf' && $spfCurrent !== null) {
                $check['current_value'] = $spfCurrent;
                $check['source'] = 'zone';
            } elseif ($check['key'] === 'dkim' && $dkimCurrent !== null) {
                $check['current_value'] = $dkimCurrent;
                $check['source'] = 'zone';
            } else {
                continue;
            }

            $check['state'] = 'unknown';
            $block['checks'][$i] = $this->resolveState($check);
            $filled = true;
        }

        $this->fillExpectedFallbacks($account, $block);

        return $filled;
    }

    /**
     * Derive the suggested records ourselves when the API offered none. These are
     * flagged with a non-'api' source so they are never presented as authoritative.
     *
     * @param  array<string, mixed>  $block
     */
    protected function fillExpectedFallbacks(WhmAccount $account, array &$block): void
    {
        foreach ($block['checks'] as $i => $check) {
            if ($check['expected_value'] !== null) {
                continue;
            }

            if ($check['key'] === 'spf' && $block['ip'] !== null) {
                $check['expected_value'] = 'v=spf1 +mx +a +ip4:'.$block['ip'].' ~all';
                $check['expected_source'] = 'derived';
                $check['message'] = 'القيمة الموصى بها مقترحة محلياً — قارنها مع cPanel قبل الاعتماد عليها';
                $block['checks'][$i] = $this->resolveState($check);

                continue;
            }

            if ($check['key'] === 'dkim') {
                $key = $this->dkimPublicKey($account, $block['domain']);
                if ($key !== null) {
                    $check['expected_value'] = $key;
                    $check['expected_source'] = 'api';
                    $block['checks'][$i] = $this->resolveState($check);
                }
            }
        }
    }

    protected function dkimPublicKey(WhmAccount $account, string $domain): ?string
    {
        $response = $this->api->emailAuthDkimKeys($account->username, $domain);
        if (! ($response['success'] ?? false)) {
            return null;
        }

        $payload = $response['payload'] ?? null;
        $row = $this->pickDomainRow($payload, $domain);
        if ($row === [] && is_array($payload)) {
            $row = $payload;
        }
        if (! is_array($row)) {
            return null;
        }

        $key = $this->firstString($row, ['dkim_public_key', 'public_key', 'p', 'value', 'record']);
        if ($key === null) {
            return null;
        }

        if (preg_match('/^\s*"?v=/i', $key)) {
            return $key;
        }

        return 'v=DKIM1; k=rsa; p='.ltrim($key, "p= \t");
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function finalizeBlock(array &$block): void
    {
        $overall = 'unknown';
        foreach ($block['checks'] as $check) {
            if ($check['state'] === 'problem') {
                $overall = 'problem';
                break;
            }
            if ($check['state'] === 'ok') {
                $overall = 'ok';
            }
        }

        $block['overall'] = $overall;
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    protected function defaultExpectedName(string $key, array $ctx): ?string
    {
        return match ($key) {
            'dkim' => 'default._domainkey.'.$ctx['domain'],
            'spf' => $ctx['domain'],
            'ptr' => $ctx['ip'],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    protected function firstString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $value = $this->flattenValue($row[$key]);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Collapse a value that may be a string, a list of TXT chunks, or a nested row.
     *
     * A TXT record longer than 255 bytes is chunked; the chunks must be joined with
     * NO separator or the base64 DKIM key breaks. We only join when the first chunk
     * alone starts with "v=" — otherwise these are distinct records, not chunks.
     */
    protected function flattenValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_array($value) || $value === []) {
            return null;
        }

        if (array_is_list($value)) {
            $parts = [];
            foreach ($value as $item) {
                if (is_string($item) || is_int($item) || is_float($item)) {
                    $parts[] = trim((string) $item);
                }
            }
            $parts = array_values(array_filter($parts, fn (string $p): bool => $p !== ''));

            if ($parts === []) {
                return $this->flattenValue($value[0] ?? null);
            }

            if (count($parts) === 1) {
                return $parts[0];
            }

            $firstIsHeader = (bool) preg_match('/^\s*"?v=/i', $parts[0]);
            $restAreHeaders = false;
            foreach (array_slice($parts, 1) as $part) {
                if (preg_match('/^\s*"?v=/i', $part)) {
                    $restAreHeaders = true;
                    break;
                }
            }

            if ($firstIsHeader && ! $restAreHeaders) {
                return implode('', $parts);
            }

            return $parts[0];
        }

        foreach (['txtdata', 'value', 'record', 'data', 'p', 'public_key'] as $key) {
            if (array_key_exists($key, $value)) {
                $inner = $value[$key];
                if (is_string($inner) || is_numeric($inner) || (is_array($inner) && array_is_list($inner))) {
                    return $this->flattenValue($inner);
                }
            }
        }

        return null;
    }

    protected function normalizeState(mixed $raw): string
    {
        $value = strtoupper(trim((string) $this->flattenValue($raw)));

        if ($value === '') {
            return 'unknown';
        }

        if (in_array($value, self::STATES_OK, true)) {
            return 'ok';
        }

        if (in_array($value, self::STATES_PROBLEM, true)) {
            return 'problem';
        }

        return 'unknown';
    }

    protected function canonical(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = str_replace(['"', '\\'], '', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return strtolower(trim($value));
    }

    protected function stateLabel(string $state): string
    {
        return match ($state) {
            'ok' => 'سليم',
            'problem' => 'يحتاج إصلاح',
            default => 'غير معروف',
        };
    }

    protected function stateBadge(string $state): string
    {
        return match ($state) {
            'ok' => 'bg-success-transparent',
            'problem' => 'bg-danger-transparent',
            default => 'bg-secondary-transparent',
        };
    }

    protected function looksLikeModuleMissing(string $message): bool
    {
        $needle = strtolower($message);
        foreach (self::MODULE_MISSING_NEEDLES as $candidate) {
            if (str_contains($needle, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(string $message, bool $configured): array
    {
        return [
            'success' => false,
            'configured' => $configured,
            'available' => false,
            'message' => $message,
            'fetched_at' => null,
            'fetched_at_human' => null,
            'server' => ['hostname' => null, 'ip' => null, 'ptr' => null, 'ptr_state' => 'unknown'],
            'domains' => [],
            'warnings' => [],
        ];
    }
}
