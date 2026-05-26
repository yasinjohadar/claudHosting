<?php

namespace App\Support;

use Illuminate\Support\Str;

class WordpressDomainHelper
{
    public static function normalizeHostname(string $hostname): string
    {
        $hostname = strtolower(trim($hostname));
        $hostname = preg_replace('#^https?://#', '', $hostname) ?? $hostname;
        $hostname = rtrim($hostname, '/');
        $hostname = preg_replace('#:\d+$#', '', $hostname);

        if (str_contains($hostname, '/')) {
            $hostname = explode('/', $hostname, 2)[0];
        }

        return $hostname;
    }

    public static function apexFromHostname(string $hostname): string
    {
        $hostname = self::normalizeHostname($hostname);
        $parts = explode('.', $hostname);

        if (count($parts) <= 2) {
            return $hostname;
        }

        $tld = array_pop($parts);
        $sld = array_pop($parts);

        return $sld.'.'.$tld;
    }

    public static function slugFromHostname(string $hostname): string
    {
        $apex = self::apexFromHostname($hostname);
        $slug = Str::slug(str_replace('.', '-', $apex), '-');

        if ($slug === '') {
            $slug = 'site-'.Str::lower(Str::random(6));
        }

        return substr($slug, 0, 63);
    }

    public static function buildPublicUrl(string $hostname): string
    {
        return 'https://'.self::normalizeHostname($hostname);
    }

    public static function isSubdomainOfBase(string $hostname, string $baseDomain): bool
    {
        $hostname = self::normalizeHostname($hostname);
        $baseDomain = self::normalizeHostname($baseDomain);

        if ($hostname === $baseDomain) {
            return true;
        }

        return str_ends_with($hostname, '.'.$baseDomain);
    }

    /**
     * @return array{record_name: string, fqdn: string}
     */
    public static function dnsRecordForPrimaryHostname(string $primaryHostname, string $apex): array
    {
        $primaryHostname = self::normalizeHostname($primaryHostname);
        $apex = self::normalizeHostname($apex);

        if ($primaryHostname === $apex) {
            return [
                'record_name' => '@',
                'fqdn' => $apex,
            ];
        }

        if (str_ends_with($primaryHostname, '.'.$apex)) {
            $recordName = substr($primaryHostname, 0, -strlen('.'.$apex));

            return [
                'record_name' => $recordName !== '' ? $recordName : '@',
                'fqdn' => $primaryHostname,
            ];
        }

        return [
            'record_name' => $primaryHostname,
            'fqdn' => $primaryHostname,
        ];
    }

    public static function filebrowserHostname(string $apex): string
    {
        return 'files.'.self::normalizeHostname($apex);
    }

    public static function filebrowserPublicUrl(string $apex): string
    {
        return self::buildPublicUrl(self::filebrowserHostname($apex));
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function manualDnsInstructions(
        string $primaryHostname,
        string $apex,
        string $origin,
        bool $includeFilebrowser
    ): array {
        $primary = self::dnsRecordForPrimaryHostname($primaryHostname, $apex);
        $isIp = filter_var($origin, FILTER_VALIDATE_IP) !== false;
        $type = $isIp ? 'A' : 'CNAME';

        $rows = [
            [
                'label' => 'الموقع الرئيسي',
                'name' => $primary['record_name'],
                'fqdn' => $primary['fqdn'],
                'type' => $type,
                'value' => $origin,
            ],
        ];

        if ($includeFilebrowser) {
            $fbHost = self::filebrowserHostname($apex);
            $rows[] = [
                'label' => 'مدير الملفات',
                'name' => 'files',
                'fqdn' => $fbHost,
                'type' => 'CNAME',
                'value' => $primary['fqdn'],
            ];
        }

        return $rows;
    }
}
