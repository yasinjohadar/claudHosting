<?php

namespace Tests\Unit\WhatsApp;

use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Evolution\EvolutionInstanceState;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * End-to-end over the sync path with Evolution stubbed, reproducing the production report:
 * the device was linked by QR, yet the admin table showed "close" and "—".
 *
 * RefreshDatabase is avoided on purpose — four unrelated migrations in this project query
 * MySQL information_schema and abort on sqlite. Only the three evolution_instances
 * migrations are run, which is all this path touches.
 */
class EvolutionInstanceRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_01_13_142821_create_system_settings_table.php',
            '2026_06_21_100000_create_evolution_instances_table.php',
            '2026_07_02_100000_add_rotation_fields_to_evolution_instances_table.php',
            '2026_07_03_100000_add_manual_fields_to_evolution_instances_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        config()->set('whatsapp.timeout', 5);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('evolution_instances');
        Schema::dropIfExists('system_settings');

        parent::tearDown();
    }

    protected function makeInstance(array $attributes = []): EvolutionInstance
    {
        return EvolutionInstance::create(array_merge([
            'instance_name' => 'whatsapp ClaudSoft',
            'connection_status' => 'pending',
            'is_manual' => true,
            'rotation_enabled' => true,
            'evolution_base_url' => 'http://evo.test:3000',
            'evolution_api_key' => 'secret-key',
        ], $attributes));
    }

    private function service(): EvolutionService
    {
        return new EvolutionService(app(WhatsAppSettingsService::class));
    }

    /**
     * @param  array<string, mixed>|null  $listRow
     */
    private function fakeEvolution(?array $listRow, mixed $stateBody, int $stateStatus = 200): void
    {
        Http::fake([
            '*/instance/fetchInstances*' => Http::response($listRow === null ? [] : [$listRow], 200),
            '*/instance/connectionState/*' => Http::response($stateBody, $stateStatus),
        ]);
    }

    public function test_a_linked_phone_becomes_open_with_its_number(): void
    {
        $instance = $this->makeInstance();

        // The exact v2 shape: number is null, the linked number lives in ownerJid.
        $this->fakeEvolution([
            'id' => 'uuid-1',
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'open',
            'ownerJid' => '905519665883@s.whatsapp.net',
            'profileName' => 'ClaudSoft',
            'number' => null,
        ], ['instance' => ['instanceName' => 'whatsapp ClaudSoft', 'state' => 'open']]);

        $fresh = $this->service()->refreshInstanceFromApi($instance);

        $this->assertSame('open', $fresh->connection_status);
        $this->assertSame('905519665883', $fresh->phone_number);
        $this->assertSame('905519665883@s.whatsapp.net', $fresh->owner_jid);
        $this->assertNotNull($fresh->connected_at);
        $this->assertNull($fresh->disconnected_at);
        $this->assertTrue($fresh->rotation_enabled);
    }

    public function test_an_unreadable_state_falls_back_to_the_list_and_never_invents_close(): void
    {
        $instance = $this->makeInstance();

        // The state endpoint answers 200 with a shape we do not know.
        $this->fakeEvolution([
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'open',
            'ownerJid' => '905519665883@s.whatsapp.net',
        ], ['unexpected' => 'payload']);

        $fresh = $this->service()->refreshInstanceFromApi($instance);

        $this->assertSame('open', $fresh->connection_status, 'must not be downgraded to close');
        $this->assertSame('905519665883', $fresh->phone_number);
    }

    public function test_an_unknown_name_reports_not_found_instead_of_close(): void
    {
        $instance = $this->makeInstance(['instance_name' => 'whatsapp Typo']);

        $this->fakeEvolution([
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'open',
        ], ['message' => 'instance not found'], 404);

        $fresh = $this->service()->refreshInstanceFromApi($instance);

        // "close" would send the admin to rescan a QR that is not the problem.
        $this->assertSame(EvolutionInstanceState::NOT_FOUND, $fresh->connection_status);
    }

    public function test_a_stray_trailing_space_in_the_stored_name_still_matches(): void
    {
        $instance = $this->makeInstance(['instance_name' => 'whatsapp ClaudSoft ']);

        $this->fakeEvolution([
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'open',
            'ownerJid' => '905519665883@s.whatsapp.net',
        ], ['instance' => ['state' => 'open']]);

        $fresh = $this->service()->refreshInstanceFromApi($instance);

        $this->assertSame('open', $fresh->connection_status);
        $this->assertSame('905519665883', $fresh->phone_number);
    }

    public function test_a_genuinely_closed_phone_is_reported_closed(): void
    {
        $instance = $this->makeInstance(['connection_status' => 'open', 'phone_number' => '905519665883']);

        $this->fakeEvolution([
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'close',
        ], ['instance' => ['state' => 'close']]);

        $fresh = $this->service()->refreshInstanceFromApi($instance);

        $this->assertSame('close', $fresh->connection_status);
        $this->assertNotNull($fresh->disconnected_at);
        // The last known number is history, not something to erase.
        $this->assertSame('905519665883', $fresh->phone_number);
    }

    public function test_the_instance_list_sync_does_not_erase_a_known_number_or_state(): void
    {
        // syncInstances() runs on the *global* credentials and used to overwrite the row
        // right after the authoritative per-instance refresh had filled it in correctly.
        $instance = $this->makeInstance([
            'connection_status' => 'open',
            'phone_number' => '905519665883',
            'owner_jid' => '905519665883@s.whatsapp.net',
            'profile_name' => 'ClaudSoft',
        ]);

        // A build that omits connectionStatus, number and profileName entirely.
        EvolutionInstance::syncFromApiArray(['name' => 'whatsapp ClaudSoft', 'id' => 'uuid-1']);

        $fresh = $instance->fresh();
        $this->assertSame('open', $fresh->connection_status);
        $this->assertSame('905519665883', $fresh->phone_number);
        $this->assertSame('ClaudSoft', $fresh->profile_name);
        $this->assertSame('uuid-1', $fresh->evolution_uuid);
    }

    public function test_sync_from_api_array_keeps_the_first_connected_at(): void
    {
        $instance = $this->makeInstance(['connection_status' => 'open', 'connected_at' => now()->subDays(3)]);
        $original = $instance->connected_at;

        EvolutionInstance::syncFromApiArray(['name' => 'whatsapp ClaudSoft', 'connectionStatus' => 'open']);

        $this->assertTrue($original->equalTo($instance->fresh()->connected_at));
    }

    public function test_a_new_row_with_no_readable_state_is_pending_not_close(): void
    {
        $model = EvolutionInstance::syncFromApiArray(['name' => 'whatsapp Fresh']);

        $this->assertSame('pending', $model->connection_status);
    }
}
