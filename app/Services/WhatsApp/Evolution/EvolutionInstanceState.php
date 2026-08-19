<?php

namespace App\Services\WhatsApp\Evolution;

/**
 * Reads connection state and profile data out of Evolution API payloads.
 *
 * Pure, so every shape Evolution has shipped can be pinned by a unit test. It exists
 * because the previous inline readers defaulted an unrecognised payload to "close" and
 * then persisted it: a phone that was genuinely linked by QR showed up as disconnected,
 * and the UI told the admin to rescan a QR that was never the problem. Here "I could not
 * read it" is null, and the callers must decide what to do with null.
 */
final class EvolutionInstanceState
{
    public const OPEN = 'open';

    public const CLOSE = 'close';

    public const CONNECTING = 'connecting';

    /** Our own marker: the name is absent from this Evolution server. Never sent by the API. */
    public const NOT_FOUND = 'not_found';

    /**
     * Connection state from GET /instance/connectionState/{name}.
     *
     * v2 answers {"instance":{"instanceName":...,"state":"open"}}; older and proxied
     * builds have answered with a bare {"state":...}, {"status":...} or the fetchInstances
     * spelling {"connectionStatus":...}. Null when none of them is present, so an
     * unreadable answer can never be mistaken for a disconnected phone.
     */
    public static function readConnectionState(mixed $response): ?string
    {
        if (! is_array($response)) {
            return null;
        }

        $candidates = [
            data_get($response, 'instance.state'),
            data_get($response, 'instance.status'),
            data_get($response, 'instance.connectionStatus'),
            $response['state'] ?? null,
            $response['status'] ?? null,
            $response['connectionStatus'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtolower(trim($candidate));
            }
        }

        return null;
    }

    /**
     * Normalise a fetchInstances payload to a list of rows.
     *
     * v2 returns a bare list of flat rows; some builds wrap it in "value"/"data"/
     * "instances", and v1 wrapped each row in an "instance" key.
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        $list = $response;

        if (! array_is_list($response)) {
            $unwrapped = null;
            foreach (['value', 'data', 'instances'] as $key) {
                if (isset($response[$key]) && is_array($response[$key])) {
                    $unwrapped = $response[$key];
                    break;
                }
            }

            // A single row handed back unwrapped is still one row.
            $list = $unwrapped ?? [$response];
        }

        if (! array_is_list($list)) {
            $list = [$list];
        }

        $rows = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }

            // v1 nested the payload one level deeper. Outer keys win nothing here:
            // the inner "instance" object is the authoritative copy.
            if (isset($row['instance']) && is_array($row['instance'])) {
                $row = array_merge($row, $row['instance']);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /** The instance name of a row, whichever spelling this build uses. */
    public static function rowName(array $row): string
    {
        foreach (['name', 'instanceName', 'instance_name'] as $key) {
            $value = $row[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    /**
     * Find one instance in a fetchInstances payload.
     *
     * Matching is trimmed and case-insensitive on purpose: the name is typed by hand into
     * the admin form and contains spaces ("whatsapp ClaudSoft"), so a stray trailing space
     * must not read as "this phone is disconnected". Returns null when the name is
     * genuinely absent — deliberately no "fall back to the first row", which would copy a
     * different phone's JID and number onto this one.
     *
     * @return array<string, mixed>|null
     */
    public static function findRow(mixed $response, string $instanceName): ?array
    {
        $needle = self::foldName($instanceName);
        if ($needle === '') {
            return null;
        }

        foreach (self::rows($response) as $row) {
            if (self::foldName(self::rowName($row)) === $needle) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Every instance name the server reported — shown to the admin when the configured
     * name matches none of them, which is the only way to spot a typo without SSH.
     *
     * @return list<string>
     */
    public static function names(mixed $response): array
    {
        $names = [];
        foreach (self::rows($response) as $row) {
            $name = self::rowName($row);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The connected phone number for a row.
     *
     * v2 leaves "number" null unless the instance was paired by code, so the linked
     * number has to come out of ownerJid ("905519665883@s.whatsapp.net"). Reading only
     * "number" is what left the admin table showing "—" for a connected phone.
     */
    public static function phoneNumber(array $row): ?string
    {
        $direct = $row['number'] ?? null;
        if (is_string($direct) || is_int($direct)) {
            $digits = self::digits((string) $direct);
            if ($digits !== '') {
                return $digits;
            }
        }

        return self::phoneDigitsFromJid(self::ownerJid($row));
    }

    public static function ownerJid(array $row): ?string
    {
        foreach (['ownerJid', 'owner_jid', 'wuid'] as $key) {
            $value = $row[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /** Digits before the "@" of a JID. Group JIDs are not phone numbers. */
    public static function phoneDigitsFromJid(?string $jid): ?string
    {
        if ($jid === null || trim($jid) === '') {
            return null;
        }

        if (str_contains($jid, '@g.us')) {
            return null;
        }

        $local = strtok($jid, '@');
        $digits = self::digits($local === false ? $jid : $local);

        return $digits !== '' ? $digits : null;
    }

    /** Fold a name for comparison only — never for sending back to the API. */
    private static function foldName(string $name): string
    {
        // Collapse runs of any whitespace, including the NBSP that rides along with a
        // copy/paste out of the Evolution Manager UI.
        $normalised = preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $name)) ?? $name;

        return mb_strtolower(trim($normalised));
    }

    private static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
