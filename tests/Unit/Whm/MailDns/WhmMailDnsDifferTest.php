<?php

namespace Tests\Unit\Whm\MailDns;

use App\Services\Whm\MailDns\WhmMailDnsDiffer;
use Tests\TestCase;

/**
 * The differ decides what actually gets written, so a mistake here either clobbers a
 * live record or reports a broken one as fine. Pure arrays — no DB, no HTTP.
 */
class WhmMailDnsDifferTest extends TestCase
{
    protected string $domain = 'docs.claudsoft.com';

    protected function differ(): WhmMailDnsDiffer
    {
        return new WhmMailDnsDiffer;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function planItem(string $key, array $overrides = []): array
    {
        $defaults = [
            'spf' => ['type' => 'TXT', 'name' => $this->domain, 'content' => 'v=spf1 +mx ~all', 'proxy' => 'off'],
            'dkim' => ['type' => 'TXT', 'name' => 'default._domainkey.'.$this->domain, 'content' => 'v=DKIM1; p=AAA', 'proxy' => 'off'],
            'dmarc' => ['type' => 'TXT', 'name' => '_dmarc.'.$this->domain, 'content' => 'v=DMARC1; p=none;', 'proxy' => 'off'],
            'mx' => ['type' => 'MX', 'name' => $this->domain, 'content' => 'docs.claudsoft.com', 'priority' => 0, 'proxy' => 'off'],
            'mail_host' => ['type' => 'A', 'name' => 'mail.'.$this->domain, 'content' => '46.4.193.156', 'proxy' => 'off'],
            'webmail' => ['type' => 'A', 'name' => 'webmail.'.$this->domain, 'content' => '46.4.193.156', 'proxy' => 'inherit'],
        ];

        return array_merge([
            'key' => $key,
            'label' => strtoupper($key),
            'priority' => null,
            'origin' => 'mirrored',
            'note' => null,
        ], $defaults[$key], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function cfRecord(array $overrides = []): array
    {
        return array_merge([
            'id' => 'rec-'.bin2hex(random_bytes(3)),
            'name' => $this->domain,
            'type' => 'TXT',
            'content' => '',
            'proxied' => false,
            'ttl' => 1,
        ], $overrides);
    }

    protected function changeFor(array $result, string $key): ?array
    {
        foreach ($result['changes'] as $change) {
            if ($change['key'] === $key) {
                return $change;
            }
        }

        return null;
    }

    public function test_a_missing_record_is_a_create(): void
    {
        $result = $this->differ()->diff([$this->planItem('spf')], []);

        $change = $this->changeFor($result, 'spf');
        $this->assertSame('create', $change['verdict']);
        $this->assertNull($change['record_id']);
        $this->assertNull($change['old_content']);
        $this->assertSame(1, $result['counts']['create']);
    }

    public function test_an_identical_record_is_unchanged(): void
    {
        $existing = [$this->cfRecord(['id' => 'r1', 'content' => 'v=spf1 +mx ~all'])];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('spf')], $existing), 'spf');

        $this->assertSame('unchanged', $change['verdict']);
        $this->assertSame('r1', $change['record_id']);
    }

    public function test_cosmetic_differences_do_not_trigger_a_rewrite(): void
    {
        // Quoted, chunked, trailing dot, different case — all the same record.
        $existing = [
            $this->cfRecord(['id' => 'r1', 'name' => 'docs.claudsoft.com.', 'content' => '"v=spf1  +mx   ~all"']),
            $this->cfRecord([
                'id' => 'r2',
                'name' => 'default._domainkey.docs.claudsoft.com.',
                'content' => '"v=DKIM1; p=AA" "A"',
            ]),
            $this->cfRecord(['id' => 'r3', 'name' => 'MAIL.docs.claudsoft.com', 'type' => 'A', 'content' => '46.4.193.156']),
        ];

        $result = $this->differ()->diff([
            $this->planItem('spf'),
            $this->planItem('dkim'),
            $this->planItem('mail_host'),
        ], $existing);

        $this->assertSame(3, $result['counts']['unchanged']);
        $this->assertSame(0, $result['counts']['update']);
    }

    public function test_a_changed_value_is_an_update_carrying_the_old_content(): void
    {
        $existing = [$this->cfRecord(['id' => 'r1', 'content' => 'v=spf1 -all'])];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('spf')], $existing), 'spf');

        $this->assertSame('update', $change['verdict']);
        $this->assertSame('r1', $change['record_id']);
        $this->assertSame('v=spf1 -all', $change['old_content']);
    }

    public function test_spf_never_touches_an_unrelated_apex_txt(): void
    {
        // The verification token shares the apex name. Matching by name alone would
        // overwrite it — this is the assertion that prevents that.
        $existing = [
            $this->cfRecord(['id' => 'verify', 'content' => 'google-site-verification=abc123']),
            $this->cfRecord(['id' => 'spf', 'content' => 'v=spf1 -all']),
        ];

        $result = $this->differ()->diff([$this->planItem('spf')], $existing);
        $change = $this->changeFor($result, 'spf');

        $this->assertSame('update', $change['verdict']);
        $this->assertSame('spf', $change['record_id'], 'the SPF row must be the one matched');
        $this->assertSame([], $result['extras'], 'an unrelated TXT is not an extra');
    }

    public function test_a_verification_token_alone_yields_a_create_not_an_update(): void
    {
        $existing = [$this->cfRecord(['id' => 'verify', 'content' => 'MS=ms12345'])];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('spf')], $existing), 'spf');

        $this->assertSame('create', $change['verdict']);
        $this->assertNull($change['record_id']);
    }

    public function test_mx_is_compared_as_a_set(): void
    {
        $plan = [
            $this->planItem('mx', ['content' => 'docs.claudsoft.com', 'priority' => 0]),
            $this->planItem('mx', ['content' => 'backup.claudsoft.com', 'priority' => 20]),
        ];
        $existing = [
            $this->cfRecord(['id' => 'mx1', 'type' => 'MX', 'content' => 'docs.claudsoft.com', 'priority' => 0]),
        ];

        $result = $this->differ()->diff($plan, $existing);

        $this->assertSame(1, $result['counts']['unchanged']);
        $this->assertSame(1, $result['counts']['create']);
    }

    public function test_an_mx_priority_change_is_an_update_not_a_recreate(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'mx1', 'type' => 'MX', 'content' => 'docs.claudsoft.com', 'priority' => 30]),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('mx')], $existing), 'mx');

        $this->assertSame('update', $change['verdict']);
        $this->assertSame('mx1', $change['record_id']);
        $this->assertSame(30, $change['old_priority']);
    }

    public function test_an_unplanned_extra_mx_is_surfaced_but_never_deleted(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'mx1', 'type' => 'MX', 'content' => 'docs.claudsoft.com', 'priority' => 0]),
            $this->cfRecord(['id' => 'mx2', 'type' => 'MX', 'content' => 'stale.example.com', 'priority' => 50]),
        ];

        $result = $this->differ()->diff([$this->planItem('mx')], $existing);

        $this->assertSame(1, $result['counts']['unchanged']);
        $this->assertCount(1, $result['extras']);
        $this->assertSame('stale.example.com', $result['extras'][0]['content']);
        // No verdict anywhere authorises a delete.
        foreach ($result['changes'] as $change) {
            $this->assertNotSame('delete', $change['verdict']);
        }
    }

    public function test_a_second_spf_row_is_reported_as_an_extra(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'spf1', 'content' => 'v=spf1 +mx ~all']),
            $this->cfRecord(['id' => 'spf2', 'content' => 'v=spf1 include:other.example -all']),
        ];

        $result = $this->differ()->diff([$this->planItem('spf')], $existing);

        // The matched row is claimed; only the leftover SPF is reported.
        $this->assertCount(1, $result['extras']);
        $this->assertSame('v=spf1 include:other.example -all', $result['extras'][0]['content']);
        $this->assertSame('unchanged', $this->changeFor($result, 'spf')['verdict']);
    }

    public function test_an_orange_clouded_mail_host_is_an_update_even_when_content_matches(): void
    {
        // The Cloudflare proxy serves HTTP only, so a proxied mail A resolves to an
        // address that never answers on 25/465/587 — broken despite matching content.
        $existing = [
            $this->cfRecord(['id' => 'a1', 'name' => 'mail.'.$this->domain, 'type' => 'A', 'content' => '46.4.193.156', 'proxied' => true]),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('mail_host')], $existing), 'mail_host');

        $this->assertSame('update', $change['verdict']);
        $this->assertTrue($change['old_proxied']);
    }

    public function test_a_proxied_http_host_is_left_alone(): void
    {
        // webmail is an HTTP service; proxying it is a legitimate operator choice.
        $existing = [
            $this->cfRecord(['id' => 'a2', 'name' => 'webmail.'.$this->domain, 'type' => 'A', 'content' => '46.4.193.156', 'proxied' => true]),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('webmail')], $existing), 'webmail');

        $this->assertSame('unchanged', $change['verdict']);
    }

    public function test_a_dmarc_cname_is_a_conflict_and_is_never_deleted(): void
    {
        // Usually a deliberate delegation to a DMARC reporting vendor.
        $existing = [
            $this->cfRecord(['id' => 'c1', 'name' => '_dmarc.'.$this->domain, 'type' => 'CNAME', 'content' => 'x.dmarcvendor.com']),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('dmarc')], $existing), 'dmarc');

        $this->assertSame('conflict', $change['verdict']);
        $this->assertStringContainsString('CNAME', $change['reason']);
    }

    public function test_a_cname_at_the_mail_name_makes_mx_a_conflict(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'c2', 'name' => $this->domain, 'type' => 'CNAME', 'content' => 'somewhere.example']),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('mx')], $existing), 'mx');

        $this->assertSame('conflict', $change['verdict']);
    }

    public function test_a_wrong_type_at_a_host_name_is_a_conflict(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'c3', 'name' => 'mail.'.$this->domain, 'type' => 'CNAME', 'content' => 'elsewhere.example']),
        ];

        $change = $this->changeFor($this->differ()->diff([$this->planItem('mail_host')], $existing), 'mail_host');

        $this->assertSame('conflict', $change['verdict']);
        $this->assertStringContainsString('CNAME', $change['reason']);
    }

    public function test_third_party_mx_is_detected(): void
    {
        $existing = [
            $this->cfRecord([
                'id' => 'mx-g',
                'type' => 'MX',
                'content' => 'aspmx.l.google.com',
                'priority' => 1,
            ]),
        ];

        $result = $this->differ()->diff([$this->planItem('mx')], $existing);

        $this->assertCount(1, $result['third_party_mx']);
        $this->assertSame('Google Workspace', $result['third_party_mx'][0]['provider']);
    }

    public function test_microsoft_mx_is_detected_by_suffix(): void
    {
        $existing = [
            $this->cfRecord([
                'id' => 'mx-m',
                'type' => 'MX',
                'content' => 'claudsoft-com.mail.protection.outlook.com',
                'priority' => 0,
            ]),
        ];

        $result = $this->differ()->diff([$this->planItem('mx')], $existing);

        $this->assertSame('Microsoft 365', $result['third_party_mx'][0]['provider']);
    }

    public function test_the_accounts_own_mx_is_not_flagged_as_third_party(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'mx1', 'type' => 'MX', 'content' => 'docs.claudsoft.com', 'priority' => 0]),
        ];

        $result = $this->differ()->diff([$this->planItem('mx')], $existing);

        $this->assertSame([], $result['third_party_mx']);
    }

    public function test_error_sentinels_and_malformed_rows_are_ignored(): void
    {
        $existing = [
            ['_error' => 'فشل جلب السجلات'],
            ['id' => 'no-name', 'type' => 'TXT'],
            ['id' => 'no-type', 'name' => $this->domain],
            'not-an-array',
        ];

        $result = $this->differ()->diff([$this->planItem('spf')], $existing);

        $this->assertSame('create', $this->changeFor($result, 'spf')['verdict']);
    }

    public function test_counts_summarise_every_verdict(): void
    {
        $existing = [
            $this->cfRecord(['id' => 'r1', 'content' => 'v=spf1 +mx ~all']),
            $this->cfRecord(['id' => 'r2', 'name' => 'default._domainkey.'.$this->domain, 'content' => 'v=DKIM1; p=OLD']),
            $this->cfRecord(['id' => 'r3', 'name' => '_dmarc.'.$this->domain, 'type' => 'CNAME', 'content' => 'v.example']),
        ];

        $result = $this->differ()->diff([
            $this->planItem('spf'),
            $this->planItem('dkim'),
            $this->planItem('dmarc'),
            $this->planItem('mail_host'),
        ], $existing);

        $this->assertSame(
            ['create' => 1, 'update' => 1, 'unchanged' => 1, 'conflict' => 1],
            $result['counts']
        );
    }

    public function test_an_empty_plan_produces_nothing(): void
    {
        $result = $this->differ()->diff([], [$this->cfRecord(['content' => 'v=spf1 -all'])]);

        $this->assertSame([], $result['changes']);
        $this->assertSame([], $result['extras']);
        $this->assertSame([], $result['third_party_mx']);
    }
}
