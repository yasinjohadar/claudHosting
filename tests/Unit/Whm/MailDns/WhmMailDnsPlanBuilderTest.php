<?php

namespace Tests\Unit\Whm\MailDns;

use App\Services\Whm\MailDns\WhmMailDnsPlanBuilder;
use Tests\TestCase;

/**
 * The plan decides what gets written to live DNS, so every extraction rule is pinned
 * here. Pure arrays — no DB, no HTTP.
 */
class WhmMailDnsPlanBuilderTest extends TestCase
{
    protected string $domain = 'docs.claudsoft.com';

    protected function builder(): WhmMailDnsPlanBuilder
    {
        return new WhmMailDnsPlanBuilder;
    }

    /**
     * A realistic cPanel dumpzone for a subdomain account, in WHM's own key spellings.
     *
     * @return list<array<string, mixed>>
     */
    protected function zone(array $overrides = []): array
    {
        $zone = [
            ['type' => 'SOA', 'name' => 'docs.claudsoft.com.', 'ttl' => 86400],
            ['type' => 'NS', 'name' => 'docs.claudsoft.com.', 'address' => 'ns1.claudsoft.com.'],
            ['type' => 'A', 'name' => 'docs.claudsoft.com.', 'address' => '46.4.193.156', 'ttl' => 14400],
            ['type' => 'MX', 'name' => 'docs.claudsoft.com.', 'exchange' => 'docs.claudsoft.com.', 'preference' => 0],
            ['type' => 'TXT', 'name' => 'docs.claudsoft.com.', 'txtdata' => 'v=spf1 +mx +a +ip4:46.4.193.156 ~all'],
            ['type' => 'TXT', 'name' => 'docs.claudsoft.com.', 'txtdata' => 'google-site-verification=abc123'],
            ['type' => 'TXT', 'name' => 'default._domainkey.docs.claudsoft.com.', 'txtdata' => 'v=DKIM1; k=rsa; p=MIIBIjANBg'],
            ['type' => 'A', 'name' => 'mail.docs.claudsoft.com.', 'address' => '46.4.193.156'],
            ['type' => 'A', 'name' => 'webmail.docs.claudsoft.com.', 'address' => '46.4.193.156'],
            ['type' => 'CNAME', 'name' => 'autodiscover.docs.claudsoft.com.', 'cname' => 'docs.claudsoft.com.'],
            ['type' => 'CNAME', 'name' => 'autoconfig.docs.claudsoft.com.', 'cname' => 'docs.claudsoft.com.'],
            ['type' => 'A', 'name' => 'cpanel.docs.claudsoft.com.', 'address' => '46.4.193.156'],
            ['type' => 'A', 'name' => 'webdisk.docs.claudsoft.com.', 'address' => '46.4.193.156'],
        ];

        return array_merge($zone, $overrides);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function build(?array $zone = null, array $options = []): array
    {
        return $this->builder()->build($this->domain, $zone ?? $this->zone(), $options);
    }

    protected function item(array $plan, string $key): ?array
    {
        foreach ($plan['items'] as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function itemsOf(array $plan, string $key): array
    {
        return array_values(array_filter($plan['items'], fn (array $i): bool => $i['key'] === $key));
    }

    public function test_it_plans_every_requested_record_kind(): void
    {
        $plan = $this->build();
        $keys = array_values(array_unique(array_column($plan['items'], 'key')));

        foreach (['mx', 'spf', 'dkim', 'mail_host', 'webmail', 'autodiscover', 'autoconfig', 'dmarc'] as $expected) {
            $this->assertContains($expected, $keys, "plan is missing {$expected}");
        }
    }

    public function test_all_names_are_fqdns_inside_the_account_domain(): void
    {
        $plan = $this->build();

        foreach ($plan['items'] as $item) {
            $this->assertStringNotContainsString('@', $item['name']);
            $this->assertStringEndsWith($this->domain, $item['name'], "{$item['key']} escaped the domain");
            $this->assertStringNotContainsString('..', $item['name']);
        }
    }

    public function test_mirrored_records_carry_cpanels_own_values(): void
    {
        $plan = $this->build();

        $spf = $this->item($plan, 'spf');
        $this->assertSame('mirrored', $spf['origin']);
        $this->assertSame('v=spf1 +mx +a +ip4:46.4.193.156 ~all', $spf['content']);
        $this->assertSame('TXT', $spf['type']);
        $this->assertSame($this->domain, $spf['name']);

        $dkim = $this->item($plan, 'dkim');
        $this->assertSame('mirrored', $dkim['origin']);
        $this->assertSame('default._domainkey.'.$this->domain, $dkim['name']);
        $this->assertSame('v=DKIM1; k=rsa; p=MIIBIjANBg', $dkim['content']);
    }

    public function test_unrelated_apex_txt_is_never_mistaken_for_spf(): void
    {
        $plan = $this->build();
        $contents = array_column($plan['items'], 'content');

        // The verification token shares the apex name but must not be planned.
        $this->assertNotContains('google-site-verification=abc123', $contents);
        $this->assertCount(1, $this->itemsOf($plan, 'spf'));
    }

    public function test_chunked_dkim_is_joined_without_a_separator(): void
    {
        $zone = $this->zone();
        foreach ($zone as $i => $row) {
            if (($row['name'] ?? '') === 'default._domainkey.docs.claudsoft.com.') {
                $zone[$i]['txtdata'] = ['v=DKIM1; k=rsa; p=AAAABBBB', 'CCCCDDDD'];
            }
        }

        $dkim = $this->item($this->build($zone), 'dkim');

        $this->assertSame('v=DKIM1; k=rsa; p=AAAABBBBCCCCDDDD', $dkim['content']);
    }

    public function test_multiple_mx_records_are_all_planned_with_their_priorities(): void
    {
        $zone = $this->zone([
            ['type' => 'MX', 'name' => 'docs.claudsoft.com.', 'exchange' => 'backup.claudsoft.com.', 'preference' => 20],
        ]);

        $mx = $this->itemsOf($this->build($zone), 'mx');

        $this->assertCount(2, $mx);
        $priorities = array_column($mx, 'priority');
        sort($priorities);
        $this->assertSame([0, 20], $priorities);
    }

    public function test_zone_infrastructure_is_skipped_with_a_reason(): void
    {
        $plan = $this->build();
        $skippedTypes = array_column($plan['skipped'], 'type');

        $this->assertContains('SOA', $skippedTypes);
        $this->assertContains('NS', $skippedTypes);
        foreach ($plan['skipped'] as $row) {
            $this->assertNotSame('', $row['reason'], 'every skip must explain itself');
        }
    }

    public function test_control_panel_service_hosts_are_skipped_not_planned(): void
    {
        $plan = $this->build();

        $plannedNames = array_column($plan['items'], 'name');
        $this->assertNotContains('cpanel.'.$this->domain, $plannedNames);
        $this->assertNotContains('webdisk.'.$this->domain, $plannedNames);

        $skippedNames = array_column($plan['skipped'], 'name');
        $this->assertContains('cpanel.'.$this->domain, $skippedNames);
        $this->assertContains('webdisk.'.$this->domain, $skippedNames);
    }

    public function test_the_apex_a_record_is_not_planned(): void
    {
        // The website's own A record has nothing to do with mail and must be left alone.
        $plan = $this->build();

        foreach ($plan['items'] as $item) {
            if ($item['type'] === 'A') {
                $this->assertNotSame($this->domain, $item['name']);
            }
        }
    }

    public function test_mail_host_must_never_be_proxied_but_http_hosts_may_be(): void
    {
        $plan = $this->build();

        // Cloudflare's proxy handles HTTP only, so an orange-clouded mail A breaks SMTP.
        $this->assertSame('off', $this->item($plan, 'mail_host')['proxy']);
        $this->assertNotNull($this->item($plan, 'mail_host')['note']);

        // webmail/autodiscover are HTTP services — forcing them grey would undo a
        // deliberate operator choice.
        $this->assertSame('inherit', $this->item($plan, 'webmail')['proxy']);
        $this->assertSame('inherit', $this->item($plan, 'autodiscover')['proxy']);
        $this->assertSame('off', $this->item($plan, 'mx')['proxy']);
        $this->assertSame('off', $this->item($plan, 'spf')['proxy']);
    }

    public function test_dmarc_is_generated_and_labelled_as_such(): void
    {
        $dmarc = $this->item($this->build(), 'dmarc');

        $this->assertSame('generated', $dmarc['origin']);
        $this->assertSame('_dmarc.'.$this->domain, $dmarc['name']);
        $this->assertSame('v=DMARC1; p=none;', $dmarc['content']);
        $this->assertStringContainsString('مُولَّدة', $dmarc['note']);
    }

    public function test_an_existing_dmarc_is_mirrored_instead_of_regenerated(): void
    {
        $zone = $this->zone([
            ['type' => 'TXT', 'name' => '_dmarc.docs.claudsoft.com.', 'txtdata' => 'v=DMARC1; p=reject; rua=mailto:a@b.com'],
        ]);

        $dmarc = $this->item($this->build($zone), 'dmarc');

        $this->assertSame('mirrored', $dmarc['origin']);
        $this->assertSame('v=DMARC1; p=reject; rua=mailto:a@b.com', $dmarc['content']);
    }

    public function test_dmarc_generation_can_be_switched_off(): void
    {
        $plan = $this->build(null, ['generate_dmarc' => false]);

        $this->assertNull($this->item($plan, 'dmarc'));
    }

    public function test_generated_dmarc_includes_a_valid_rua_and_rejects_junk(): void
    {
        $withRua = $this->build(null, ['dmarc_rua' => 'dmarc@claudsoft.com']);
        $this->assertStringContainsString('rua=mailto:dmarc@claudsoft.com;', $this->item($withRua, 'dmarc')['content']);

        $withJunk = $this->build(null, ['dmarc_rua' => 'not-an-email']);
        $this->assertStringNotContainsString('rua=', $this->item($withJunk, 'dmarc')['content']);
    }

    public function test_a_stricter_dmarc_policy_is_honoured_but_warned_about(): void
    {
        $plan = $this->build(null, ['dmarc_policy' => 'reject']);

        $this->assertSame('v=DMARC1; p=reject;', $this->item($plan, 'dmarc')['content']);
        $this->assertNotEmpty(array_filter($plan['notes'], fn (string $n): bool => str_contains($n, 'DMARC')));
    }

    public function test_an_unknown_dmarc_policy_falls_back_to_none(): void
    {
        $plan = $this->build(null, ['dmarc_policy' => 'bogus']);

        $this->assertSame('v=DMARC1; p=none;', $this->item($plan, 'dmarc')['content']);
    }

    public function test_more_than_one_spf_blocks_the_spf_item_and_notes_why(): void
    {
        $zone = $this->zone([
            ['type' => 'TXT', 'name' => 'docs.claudsoft.com.', 'txtdata' => 'v=spf1 include:other.example ~all'],
        ]);

        $plan = $this->build($zone);

        // Merging two SPF records is a human decision, never ours.
        $this->assertNull($this->item($plan, 'spf'));
        $this->assertNotEmpty(array_filter($plan['notes'], fn (string $n): bool => str_contains($n, 'SPF')));
    }

    public function test_missing_dkim_and_mx_are_reported_as_notes(): void
    {
        $bare = [
            ['type' => 'A', 'name' => 'docs.claudsoft.com.', 'address' => '46.4.193.156'],
        ];

        $plan = $this->build($bare);

        $this->assertNull($this->item($plan, 'dkim'));
        $this->assertNull($this->item($plan, 'mx'));
        $joined = implode(' | ', $plan['notes']);
        $this->assertStringContainsString('DKIM', $joined);
        $this->assertStringContainsString('MX', $joined);
    }

    public function test_records_outside_the_account_domain_are_never_planned(): void
    {
        $zone = $this->zone([
            ['type' => 'A', 'name' => 'mail.other.claudsoft.com.', 'address' => '1.2.3.4'],
            ['type' => 'TXT', 'name' => 'claudsoft.com.', 'txtdata' => 'v=spf1 -all'],
        ]);

        $plan = $this->build($zone);

        foreach ($plan['items'] as $item) {
            $this->assertStringEndsWith($this->domain, $item['name']);
        }
        $this->assertSame('v=spf1 +mx +a +ip4:46.4.193.156 ~all', $this->item($plan, 'spf')['content']);
    }

    public function test_deeper_subdomains_are_not_treated_as_mail_hosts(): void
    {
        $zone = $this->zone([
            ['type' => 'A', 'name' => 'a.mail.docs.claudsoft.com.', 'address' => '1.2.3.4'],
        ]);

        $names = array_column($this->build($zone)['items'], 'name');

        $this->assertNotContains('a.mail.'.$this->domain, $names);
    }

    public function test_ipv6_mail_hosts_are_skipped_unless_enabled(): void
    {
        $zone = $this->zone([
            ['type' => 'AAAA', 'name' => 'mail.docs.claudsoft.com.', 'address' => '2001:db8::1'],
        ]);

        $plan = $this->build($zone);
        $this->assertContains('AAAA', array_column($plan['skipped'], 'type'));

        config(['whm.mail_dns_include_ipv6' => true]);
        $enabled = $this->build($zone);
        $aaaa = array_values(array_filter($enabled['items'], fn (array $i): bool => $i['type'] === 'AAAA'));
        $this->assertCount(1, $aaaa);
        $this->assertSame('mail.'.$this->domain, $aaaa[0]['name']);
    }

    public function test_an_empty_zone_yields_no_items_but_still_explains_itself(): void
    {
        $plan = $this->build([]);

        // DMARC is still generated: it needs no cPanel source.
        $this->assertSame(['dmarc'], array_column($plan['items'], 'key'));
        $this->assertNotEmpty($plan['notes']);
    }

    public function test_an_invalid_domain_is_rejected(): void
    {
        $plan = $this->builder()->build('', $this->zone());

        $this->assertSame([], $plan['items']);
        $this->assertNotEmpty($plan['notes']);
    }
}
