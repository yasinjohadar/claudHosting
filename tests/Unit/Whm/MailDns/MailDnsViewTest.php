<?php

namespace Tests\Unit\Whm\MailDns;

use App\Models\WhmAccount;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The diff UI and — most importantly — that the DNS-write trigger cannot leak onto the
 * client portal. Blade only, no DB, no HTTP.
 */
class MailDnsViewTest extends TestCase
{
    protected string $domain = 'docs.claudsoft.com';

    protected function account(int $id = 44): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = $id;
        $account->username = 'docsclaudsoft';
        $account->domain = $this->domain;
        $account->status = 'active';

        return $account;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function change(array $overrides = []): array
    {
        return array_merge([
            'key' => 'spf',
            'label' => 'SPF',
            'type' => 'TXT',
            'name' => $this->domain,
            'content' => 'v=spf1 +mx ~all',
            'priority' => null,
            'proxy' => 'off',
            'origin' => 'mirrored',
            'note' => null,
            'verdict' => 'create',
            'record_id' => null,
            'old_content' => null,
            'old_priority' => null,
            'old_proxied' => null,
            'reason' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function renderBody(array $overrides = []): string
    {
        $data = array_merge([
            'ok' => true,
            'can_apply' => true,
            'domain' => $this->domain,
            'zone' => ['id' => 'zone1', 'name' => 'claudsoft.com'],
            'plan' => ['items' => [], 'skipped' => [], 'notes' => []],
            'changes' => [$this->change()],
            'extras' => [],
            'counts' => ['create' => 1, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
            'blockers' => [],
            'warnings' => [],
            'results' => [],
            'message' => 'مطلوب 1 تغيير',
        ], $overrides);

        return view('admin.whm.accounts.partials.mail-dns-body', ['data' => $data])->render();
    }

    // ------------------------------------------------- the client-safety assertions

    public function test_the_write_trigger_is_absent_unless_explicitly_enabled(): void
    {
        // This is the client portal's exact call shape: it never passes canWriteDns.
        $html = view('admin.whm.accounts.partials.email-deliverability-tab', [
            'account' => $this->account(),
            'url' => '/client/hosting/44/email-deliverability',
            'embedded' => true,
            'showTitle' => false,
        ])->render();

        $this->assertStringNotContainsString('data-whm-dns-open', $html);
        $this->assertStringNotContainsString('mail-dns', $html);
        $this->assertStringNotContainsString('/admin/', $html);
        // The read-only panel itself still renders.
        $this->assertStringContainsString('data-whm-mail-pane', $html);
        $this->assertStringContainsString('قراءة فقط', $html);
    }

    public function test_enabling_it_renders_the_trigger_with_both_endpoints(): void
    {
        $html = view('admin.whm.accounts.partials.email-deliverability-tab', [
            'account' => $this->account(),
            'embedded' => true,
            'canWriteDns' => true,
        ])->render();

        $this->assertStringContainsString('data-whm-dns-open', $html);
        $this->assertStringContainsString('/admin/whm/accounts/44/mail-dns/preview', $html);
        $this->assertStringContainsString('/admin/whm/accounts/44/mail-dns/apply', $html);
    }

    public function test_many_panes_share_one_modal_and_one_style_block(): void
    {
        // The accordion renders the tab N times; a per-instance modal would collide.
        $both = Blade::render(
            '@include("admin.whm.accounts.partials.email-deliverability-tab", ["account" => $a, "embedded" => true, "canWriteDns" => true])'
            .'@include("admin.whm.accounts.partials.email-deliverability-tab", ["account" => $b, "embedded" => true, "canWriteDns" => true])',
            ['a' => $this->account(44), 'b' => $this->account(45)]
        );

        $this->assertSame(2, substr_count($both, 'data-whm-mail-pane'));
        $this->assertSame(2, substr_count($both, 'data-whm-dns-open'), 'one trigger per account');
        $this->assertSame(1, substr_count($both, 'data-whm-dns-modal'), 'the modal must be a singleton');
        $this->assertSame(1, substr_count($both, '.whm-mail-check-label {'));

        // Each trigger targets its own account.
        $this->assertStringContainsString('/accounts/44/mail-dns/preview', $both);
        $this->assertStringContainsString('/accounts/45/mail-dns/preview', $both);
    }

    // ------------------------------------------------- the diff body

    public function test_a_create_renders_its_verdict_and_a_copy_button(): void
    {
        $html = $this->renderBody();

        $this->assertStringContainsString('سيُنشأ', $html);
        $this->assertStringContainsString('claudsoft.com', $html);
        $this->assertStringContainsString('whm-copy-email', $html);
        $this->assertStringContainsString('data-copy="v=spf1 +mx ~all"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function test_an_update_shows_both_the_old_and_the_new_value(): void
    {
        $html = $this->renderBody([
            'changes' => [$this->change([
                'verdict' => 'update',
                'old_content' => 'v=spf1 -all',
                'record_id' => 'r1',
            ])],
            'counts' => ['create' => 0, 'update' => 1, 'unchanged' => 0, 'conflict' => 0],
        ]);

        $this->assertStringContainsString('سيُعدَّل', $html);
        $this->assertStringContainsString('v=spf1 -all', $html);
        $this->assertStringContainsString('ستُستبدل', $html);
        $this->assertStringContainsString('v=spf1 +mx ~all', $html);
    }

    public function test_a_generated_record_is_labelled_as_not_mirrored(): void
    {
        $html = $this->renderBody([
            'changes' => [$this->change([
                'key' => 'dmarc',
                'label' => 'DMARC',
                'name' => '_dmarc.'.$this->domain,
                'content' => 'v=DMARC1; p=none;',
                'origin' => 'generated',
                'note' => 'قيمة مُولَّدة لا منقولة من cPanel',
            ])],
        ]);

        $this->assertStringContainsString('مُولَّد محلياً', $html);
        $this->assertStringContainsString('قيمة مُولَّدة', $html);
    }

    public function test_a_conflict_shows_its_reason(): void
    {
        $html = $this->renderBody([
            'changes' => [$this->change([
                'verdict' => 'conflict',
                'reason' => 'يوجد CNAME على نفس الاسم',
            ])],
            'counts' => ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 1],
        ]);

        $this->assertStringContainsString('تعارض', $html);
        $this->assertStringContainsString('يوجد CNAME', $html);
    }

    public function test_a_proxied_record_explains_why_it_will_be_changed(): void
    {
        $html = $this->renderBody([
            'changes' => [$this->change([
                'key' => 'mail_host',
                'label' => 'mail (A)',
                'type' => 'A',
                'name' => 'mail.'.$this->domain,
                'content' => '46.4.193.156',
                'verdict' => 'update',
                'old_content' => '46.4.193.156',
                'old_proxied' => true,
            ])],
        ]);

        $this->assertStringContainsString('بروكسي Cloudflare', $html);
        $this->assertStringContainsString('SMTP', $html);
    }

    public function test_blockers_and_warnings_are_rendered(): void
    {
        $html = $this->renderBody([
            'blockers' => [['key' => 'third_party_mx', 'message' => 'سجل MX يشير إلى Google Workspace']],
            'warnings' => [['key' => 'dmarc_generated', 'message' => 'قيمة DMARC مُولَّدة']],
        ]);

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Google Workspace', $html);
        $this->assertStringContainsString('alert-warning', $html);
    }

    public function test_extras_are_shown_as_not_deleted(): void
    {
        $html = $this->renderBody([
            'extras' => [[
                'type' => 'MX',
                'name' => $this->domain,
                'content' => 'stale.example.com',
                'priority' => 50,
                'reason' => 'سجل MX إضافي لم نخطّط له — لن يُحذف',
            ]],
        ]);

        $this->assertStringContainsString('لن تُحذف', $html);
        $this->assertStringContainsString('stale.example.com', $html);
    }

    public function test_skipped_rows_explain_themselves(): void
    {
        $html = $this->renderBody([
            'plan' => [
                'items' => [],
                'notes' => ['لا سجل SPF في منطقة cPanel'],
                'skipped' => [[
                    'key' => 'panel_service',
                    'label' => 'cpanel',
                    'type' => 'A',
                    'name' => 'cpanel.'.$this->domain,
                    'reason' => 'خدمة لوحة تحكم لا بريد',
                ]],
            ],
        ]);

        $this->assertStringContainsString('تُرِكت عن قصد', $html);
        $this->assertStringContainsString('خدمة لوحة تحكم', $html);
        $this->assertStringContainsString('لا سجل SPF', $html);
    }

    public function test_a_missing_zone_is_reported(): void
    {
        $html = $this->renderBody([
            'zone' => null,
            'can_apply' => false,
            'blockers' => [['key' => 'zone_not_found', 'message' => 'النطاق غير مُدار على Cloudflare']],
            'changes' => [$this->change(['verdict' => 'manual'])],
        ]);

        $this->assertStringContainsString('غير موجودة', $html);
        $this->assertStringContainsString('تركيب يدوي', $html);
        // Still copyable so the operator can paste it at the real DNS provider.
        $this->assertStringContainsString('whm-copy-email', $html);
    }

    public function test_apply_results_are_rendered_per_record(): void
    {
        $html = $this->renderBody([
            'results' => [
                ['key' => 'spf', 'label' => 'SPF', 'type' => 'TXT', 'name' => $this->domain, 'verdict' => 'create', 'before' => null, 'after' => 'v=spf1', 'ok' => true, 'status' => 0, 'message' => null],
                ['key' => 'dkim', 'label' => 'DKIM', 'type' => 'TXT', 'name' => 'default._domainkey.'.$this->domain, 'verdict' => 'create', 'before' => null, 'after' => 'v=DKIM1', 'ok' => false, 'status' => 400, 'message' => 'content is required'],
            ],
        ]);

        $this->assertStringContainsString('نتيجة التطبيق', $html);
        $this->assertStringContainsString('content is required', $html);
        $this->assertStringContainsString('fe fe-check', $html);
        $this->assertStringContainsString('fe fe-x', $html);
    }

    public function test_a_long_dkim_value_renders_in_full(): void
    {
        $long = 'v=DKIM1; k=rsa; p='.str_repeat('MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A', 14);
        $this->assertGreaterThan(400, strlen($long));

        $html = $this->renderBody([
            'changes' => [$this->change(['key' => 'dkim', 'label' => 'DKIM', 'content' => $long])],
        ]);

        // Once in the visible span, once in the clipboard payload — never truncated.
        $this->assertSame(2, substr_count($html, e($long)));
        $this->assertStringContainsString('whm-mail-value', $html);
        $this->assertStringNotContainsString('whm-email-text', $html);
    }

    public function test_record_content_is_escaped(): void
    {
        $html = $this->renderBody([
            'changes' => [$this->change(['content' => 'v=spf1 "><script>alert(1)</script>'])],
        ]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_the_modal_starts_with_apply_disabled(): void
    {
        $html = view('admin.whm.accounts.partials.mail-dns-modal')->render();

        // Nothing is applicable until a preview says so.
        $this->assertMatchesRegularExpression('/data-whm-dns-apply[^>]*disabled|disabled[^>]*data-whm-dns-apply/', $html);
        $this->assertStringContainsString('data-whm-dns-ack', $html);
        $this->assertStringContainsString('أُقِرّ بالتحذيرات', $html);
    }
}
