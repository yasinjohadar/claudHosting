<?php

namespace Tests\Unit\Whm\MailDns;

use App\Models\WhmAccount;
use App\Services\Whm\MailDns\WhmAccountLocator;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

/**
 * The command's guard rails and exit codes. Both the service and the account locator are
 * mocked, so there is no DB and no HTTP.
 */
class SyncMailDnsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function account(): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 44;
        $account->username = 'docsclaudsoft';
        $account->domain = 'docs.claudsoft.com';
        $account->status = 'active';

        return $account;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function preview(array $overrides = []): array
    {
        return array_merge([
            'ok' => true,
            'can_apply' => true,
            'domain' => 'docs.claudsoft.com',
            'zone' => ['id' => 'zone1', 'name' => 'claudsoft.com'],
            'plan' => ['items' => [], 'skipped' => [], 'notes' => []],
            'changes' => [[
                'key' => 'spf', 'label' => 'SPF', 'type' => 'TXT',
                'name' => 'docs.claudsoft.com', 'content' => 'v=spf1 +mx ~all',
                'priority' => null, 'origin' => 'mirrored', 'note' => null,
                'verdict' => 'create', 'record_id' => null, 'old_content' => null,
                'old_priority' => null, 'old_proxied' => null, 'reason' => null,
            ]],
            'extras' => [],
            'counts' => ['create' => 1, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
            'blockers' => [],
            'warnings' => [['key' => 'dmarc_generated', 'message' => 'قيمة مُولَّدة']],
            'plan_hash' => 'hash-1',
            'message' => 'مطلوب 1 تغيير',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>|null  $applyResult  null = apply must never be called
     */
    protected function bindService(array $preview, ?array $applyResult = null): Mockery\MockInterface
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->andReturn($preview);

        if ($applyResult === null) {
            $service->shouldNotReceive('apply');
        } else {
            $service->shouldReceive('apply')->andReturn($applyResult);
        }

        $this->instance(WhmMailDnsSyncService::class, $service);

        return $service;
    }

    protected function bindLocator(?WhmAccount $account, ?Collection $all = null): void
    {
        $locator = Mockery::mock(WhmAccountLocator::class);
        $locator->shouldReceive('find')->andReturn($account);
        $locator->shouldReceive('syncable')->andReturn($all ?? new Collection($account ? [$account] : []));

        $this->instance(WhmAccountLocator::class, $locator);
    }

    public function test_no_account_and_no_all_is_a_usage_error(): void
    {
        $this->bindService($this->preview(), null);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns')
            ->expectsOutputToContain('حدّد حساباً أو استخدم --all')
            ->assertExitCode(2);
    }

    public function test_an_unknown_account_is_a_usage_error(): void
    {
        $this->bindService($this->preview(), null);
        $this->bindLocator(null);

        $this->artisan('whm:sync-mail-dns', ['account' => 'nope'])
            ->expectsOutputToContain('لم يُعثر على حساب')
            ->assertExitCode(2);
    }

    public function test_non_interactive_without_yes_refuses_to_write(): void
    {
        // Writing to live DNS unattended must be opted into explicitly.
        $this->bindService($this->preview(), null);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44', '--no-interaction' => true])
            ->expectsOutputToContain('يتطلب --yes')
            ->assertExitCode(2);
    }

    public function test_non_interactive_dry_run_is_allowed(): void
    {
        $this->bindService($this->preview(), ['ok' => true, 'outcome' => 'dry_run', 'message' => 'معاينة فقط']);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', [
            'account' => '44',
            '--dry-run' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);
    }

    public function test_dry_run_asks_the_service_for_a_dry_run(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->andReturn($this->preview());
        $service->shouldReceive('apply')
            ->once()
            ->withArgs(function ($account, $domain, $hash, $acks, $dryRun, $source): bool {
                return $dryRun === true && $source === 'command';
            })
            ->andReturn(['ok' => true, 'outcome' => 'dry_run', 'message' => 'معاينة فقط']);
        $this->instance(WhmMailDnsSyncService::class, $service);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44', '--dry-run' => true, '--no-interaction' => true])
            ->assertExitCode(0);
    }

    public function test_declining_the_confirmation_applies_nothing(): void
    {
        $this->bindService($this->preview(), null);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44'])
            ->expectsConfirmation('تطبيق التغييرات على Cloudflare؟', 'no')
            ->expectsOutputToContain('تم الإلغاء')
            ->assertExitCode(0);
    }

    public function test_confirming_applies_and_carries_the_warning_acknowledgements(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->andReturn($this->preview());
        $service->shouldReceive('apply')
            ->once()
            ->withArgs(function ($account, $domain, $hash, $acks, $dryRun): bool {
                // The operator saw the warning on screen before answering yes.
                return in_array('dmarc_generated', $acks, true) && $dryRun === false;
            })
            ->andReturn(['ok' => true, 'outcome' => 'applied', 'message' => 'تم التطبيق', 'results' => []]);
        $this->instance(WhmMailDnsSyncService::class, $service);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44'])
            ->expectsConfirmation('تطبيق التغييرات على Cloudflare؟', 'yes')
            ->assertExitCode(0);
    }

    public function test_explicit_acks_are_passed_through(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->andReturn($this->preview());
        $service->shouldReceive('apply')
            ->once()
            ->withArgs(fn ($a, $d, $h, $acks): bool => in_array('dmarc_generated', $acks, true))
            ->andReturn(['ok' => true, 'outcome' => 'applied', 'message' => 'ok', 'results' => []]);
        $this->instance(WhmMailDnsSyncService::class, $service);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', [
            'account' => '44',
            '--yes' => true,
            '--ack' => ['dmarc_generated'],
            '--no-interaction' => true,
        ])->assertExitCode(0);
    }

    public function test_a_blocked_preview_exits_one_and_never_applies(): void
    {
        $this->bindService($this->preview([
            'ok' => false,
            'can_apply' => false,
            'blockers' => [['key' => 'third_party_mx', 'message' => 'MX يشير إلى Google Workspace']],
        ]), null);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44', '--yes' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Google Workspace')
            ->assertExitCode(1);
    }

    public function test_nothing_to_do_exits_zero_without_applying(): void
    {
        $this->bindService($this->preview([
            'can_apply' => false,
            'changes' => [],
            'counts' => ['create' => 0, 'update' => 0, 'unchanged' => 5, 'conflict' => 0],
            'warnings' => [],
            'message' => 'كل السجلات مطابقة بالفعل',
        ]), null);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44', '--yes' => true, '--no-interaction' => true])
            ->expectsOutputToContain('مطابقة')
            ->assertExitCode(0);
    }

    public function test_a_partial_apply_exits_one(): void
    {
        $this->bindService($this->preview(), [
            'ok' => false,
            'outcome' => 'partial',
            'message' => 'تم تطبيق 1 من 2 تغييرات. المنطقة الآن في حالة مختلطة',
            'results' => [
                ['key' => 'spf', 'label' => 'SPF', 'type' => 'TXT', 'name' => 'docs.claudsoft.com', 'ok' => true, 'message' => null],
                ['key' => 'dkim', 'label' => 'DKIM', 'type' => 'TXT', 'name' => 'default._domainkey.docs.claudsoft.com', 'ok' => false, 'message' => 'refused'],
            ],
        ]);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', ['account' => '44', '--yes' => true, '--no-interaction' => true])
            ->expectsOutputToContain('حالة مختلطة')
            ->assertExitCode(1);
    }

    public function test_json_output_is_valid_and_carries_no_html(): void
    {
        $this->bindService($this->preview(), [
            'ok' => true,
            'outcome' => 'applied',
            'domain' => 'docs.claudsoft.com',
            'zone' => ['id' => 'zone1', 'name' => 'claudsoft.com'],
            'counts' => ['create' => 1, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
            'blockers' => [],
            'warnings' => [],
            'created_count' => 1,
            'updated_count' => 0,
            'failed_count' => 0,
            'message' => 'تم التطبيق',
            'results' => [],
        ]);
        $this->bindLocator($this->account());

        // Artisan::call (not $this->artisan) so the buffered output is retrievable.
        $exit = Artisan::call('whm:sync-mail-dns', [
            'account' => '44',
            '--yes' => true,
            '--json' => true,
            '--no-interaction' => true,
        ]);
        $this->assertSame(0, $exit);

        $output = Artisan::output();
        $decoded = json_decode(trim($output), true);

        $this->assertIsArray($decoded, 'output was not valid JSON: '.$output);
        $this->assertTrue($decoded['ok']);
        $this->assertSame('applied', $decoded['outcome']);
        $this->assertArrayNotHasKey('html', $decoded);
    }

    public function test_all_continues_past_a_failing_account_and_still_exits_one(): void
    {
        $good = $this->account();
        $bad = $this->account();
        $bad->id = 45;
        $bad->username = 'other';
        $bad->domain = 'other.claudsoft.com';

        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')
            ->andReturnUsing(fn ($account, $domain = null, $fresh = false): array => $account->id === 45
                ? $this->preview([
                    'ok' => false,
                    'can_apply' => false,
                    'domain' => 'other.claudsoft.com',
                    'blockers' => [['key' => 'whm_zone_unreadable', 'message' => 'تعذّر قراءة المنطقة']],
                ])
                : $this->preview());
        $service->shouldReceive('apply')->andReturn([
            'ok' => true, 'outcome' => 'applied', 'message' => 'تم التطبيق', 'results' => [],
        ]);
        $this->instance(WhmMailDnsSyncService::class, $service);
        $this->bindLocator($good, new Collection([$good, $bad]));

        $this->artisan('whm:sync-mail-dns', ['--all' => true, '--yes' => true, '--no-interaction' => true])
            ->expectsOutputToContain('docs.claudsoft.com')
            ->expectsOutputToContain('other.claudsoft.com')
            ->assertExitCode(1);
    }

    public function test_the_domain_option_overrides_the_account_domain(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')
            ->once()
            ->withArgs(fn ($account, $domain, $fresh): bool => $domain === 'shop.claudsoft.com')
            ->andReturn($this->preview(['can_apply' => false, 'changes' => [], 'warnings' => []]));
        $service->shouldNotReceive('apply');
        $this->instance(WhmMailDnsSyncService::class, $service);
        $this->bindLocator($this->account());

        $this->artisan('whm:sync-mail-dns', [
            'account' => '44',
            '--domain' => 'shop.claudsoft.com',
            '--yes' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);
    }
}
