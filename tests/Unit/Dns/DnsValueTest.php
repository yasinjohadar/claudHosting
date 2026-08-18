<?php

namespace Tests\Unit\Dns;

use App\Support\Dns\DnsValue;
use Tests\TestCase;

/**
 * Every mail-DNS comparison routes through DnsValue, so a bug here would either
 * rewrite a correct live record or skip a broken one. No DB, no HTTP.
 */
class DnsValueTest extends TestCase
{
    public function test_host_strips_scheme_port_path_case_and_trailing_dot(): void
    {
        $this->assertSame('docs.claudsoft.com', DnsValue::host('DOCS.ClaudSoft.com.'));
        $this->assertSame('docs.claudsoft.com', DnsValue::host('https://docs.claudsoft.com/path'));
        $this->assertSame('docs.claudsoft.com', DnsValue::host('docs.claudsoft.com:2083'));
        $this->assertSame('', DnsValue::host(null));
        $this->assertSame('', DnsValue::host('   '));
    }

    public function test_txt_unquotes_and_collapses_whitespace_but_keeps_case(): void
    {
        $this->assertSame('v=spf1 +mx ~all', DnsValue::txt('"v=spf1 +mx ~all"'));
        $this->assertSame('v=spf1 +mx ~all', DnsValue::txt("v=spf1   +mx\n~all"));
        // Base64 and DMARC tags are case-sensitive — must not be lowercased.
        $this->assertSame('v=DKIM1; k=rsa; p=MIIBAb', DnsValue::txt('"v=DKIM1; k=rsa; p=MIIBAb"'));
        $this->assertSame('', DnsValue::txt(null));
    }

    public function test_txt_joins_wire_chunks_with_no_separator(): void
    {
        // A >255-byte DKIM key arrives split. Inserting any separator corrupts base64.
        $this->assertSame(
            'v=DKIM1; k=rsa; p=AAAABBBBCCCCDDDD',
            DnsValue::txt('"v=DKIM1; k=rsa; p=AAAABBBB" "CCCCDDDD"')
        );
        $this->assertStringNotContainsString(
            ' CCCC',
            DnsValue::txt('"v=DKIM1; k=rsa; p=AAAABBBB" "CCCCDDDD"')
        );
    }

    public function test_txt_leaves_a_value_that_merely_contains_quotes_alone(): void
    {
        // Not a fully-quoted chunk run, so the reconstruction must not fire.
        $this->assertSame('some"thing', DnsValue::txt('some"thing'));
    }

    public function test_a_long_txt_round_trips_through_chunking(): void
    {
        $long = 'v=DKIM1; k=rsa; p='.str_repeat('MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A', 15);
        $this->assertGreaterThan(400, strlen($long));

        $chunks = DnsValue::txtChunks($long);
        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(DnsValue::TXT_CHUNK_LIMIT, strlen($chunk));
        }

        $requoted = implode(' ', array_map(fn (string $c): string => '"'.$c.'"', $chunks));
        $this->assertSame($long, DnsValue::txt($requoted));
    }

    public function test_txt_prefix_matching_is_case_insensitive_and_anchored(): void
    {
        $this->assertTrue(DnsValue::txtHasPrefix('"v=spf1 +mx ~all"', 'v=spf1'));
        $this->assertTrue(DnsValue::txtHasPrefix('V=SPF1 +mx', 'v=spf1'));
        $this->assertTrue(DnsValue::txtHasPrefix('v=DMARC1; p=none;', 'v=DMARC1'));

        // A verification token that merely mentions spf must not be matched.
        $this->assertFalse(DnsValue::txtHasPrefix('google-site-verification=v=spf1', 'v=spf1'));
        $this->assertFalse(DnsValue::txtHasPrefix('', 'v=spf1'));
        $this->assertFalse(DnsValue::txtHasPrefix(null, 'v=spf1'));
    }

    public function test_ip_is_normalised(): void
    {
        $this->assertSame('46.4.193.156', DnsValue::ip(' 46.4.193.156 '));
        $this->assertSame('2001:db8::1', DnsValue::ip('2001:0DB8:0000:0000:0000:0000:0000:0001'));
        $this->assertSame('not-an-ip', DnsValue::ip('NOT-AN-IP'));
    }

    public function test_records_equal_ignores_cosmetic_differences(): void
    {
        // WHM's shape vs Cloudflare's shape for the same MX record.
        $whm = ['type' => 'MX', 'name' => 'docs.claudsoft.com.', 'content' => 'main.claudsoft.com.', 'preference' => 10];
        $cf = ['type' => 'MX', 'name' => 'docs.claudsoft.com', 'content' => 'MAIN.claudsoft.com', 'priority' => 10];

        $this->assertTrue(DnsValue::recordsEqual($whm, $cf));
    }

    public function test_records_equal_is_false_when_mx_priority_differs(): void
    {
        $a = ['type' => 'MX', 'name' => 'd.com', 'content' => 'mail.d.com', 'priority' => 10];
        $b = ['type' => 'MX', 'name' => 'd.com', 'content' => 'mail.d.com', 'priority' => 20];

        $this->assertFalse(DnsValue::recordsEqual($a, $b));
    }

    public function test_records_equal_is_false_across_types_and_for_empty_types(): void
    {
        $a = ['type' => 'TXT', 'name' => 'd.com', 'content' => 'x'];
        $b = ['type' => 'CNAME', 'name' => 'd.com', 'content' => 'x'];
        $this->assertFalse(DnsValue::recordsEqual($a, $b));

        $this->assertFalse(DnsValue::recordsEqual(['name' => 'd.com'], ['name' => 'd.com']));
    }

    public function test_records_equal_treats_chunked_and_joined_txt_as_the_same(): void
    {
        $a = ['type' => 'TXT', 'name' => 'default._domainkey.d.com', 'content' => '"v=DKIM1; p=AAAA" "BBBB"'];
        $b = ['type' => 'TXT', 'name' => 'default._domainkey.d.com.', 'content' => 'v=DKIM1; p=AAAABBBB'];

        $this->assertTrue(DnsValue::recordsEqual($a, $b));
    }

    public function test_priority_reads_both_provider_spellings_and_defaults_to_zero(): void
    {
        $this->assertSame(10, DnsValue::priority(['priority' => 10]));
        $this->assertSame(5, DnsValue::priority(['preference' => '5']));
        $this->assertSame(0, DnsValue::priority([]));
    }

    public function test_is_within_gates_the_write_allow_list(): void
    {
        $this->assertTrue(DnsValue::isWithin('docs.claudsoft.com', 'docs.claudsoft.com'));
        $this->assertTrue(DnsValue::isWithin('default._domainkey.docs.claudsoft.com', 'docs.claudsoft.com'));

        // The critical negatives: a sibling, a suffix trick, and the parent zone.
        $this->assertFalse(DnsValue::isWithin('other.claudsoft.com', 'docs.claudsoft.com'));
        $this->assertFalse(DnsValue::isWithin('evildocs.claudsoft.com', 'docs.claudsoft.com'));
        $this->assertFalse(DnsValue::isWithin('claudsoft.com', 'docs.claudsoft.com'));
        $this->assertFalse(DnsValue::isWithin('', 'docs.claudsoft.com'));
        $this->assertFalse(DnsValue::isWithin('docs.claudsoft.com', null));
    }

    public function test_zone_candidates_walk_from_most_specific_to_tld(): void
    {
        $this->assertSame(
            ['docs.claudsoft.com', 'claudsoft.com', 'com'],
            DnsValue::zoneCandidates('docs.claudsoft.com')
        );
        $this->assertSame(['claudsoft.com', 'com'], DnsValue::zoneCandidates('CLAUDSOFT.com.'));

        // Multi-label public suffixes come out safely because we ask Cloudflare which
        // candidate is really a zone rather than guessing an apex.
        $this->assertSame(
            ['shop.example.co.uk', 'example.co.uk', 'co.uk', 'uk'],
            DnsValue::zoneCandidates('shop.example.co.uk')
        );
        $this->assertSame([], DnsValue::zoneCandidates(null));
    }
}
