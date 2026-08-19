<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\Evolution\EvolutionInstanceState;
use PHPUnit\Framework\TestCase;

/**
 * Pins the reader that decides whether a WhatsApp session is linked.
 *
 * The regression these guard: a phone genuinely linked by QR showed as "close" with no
 * number in the admin table, because an unreadable payload defaulted to "close" and
 * because the number was only read from "number" — which Evolution v2 leaves null unless
 * the instance was paired by code.
 */
class EvolutionInstanceStateTest extends TestCase
{
    public function test_reads_the_v2_nested_state(): void
    {
        $this->assertSame('open', EvolutionInstanceState::readConnectionState([
            'instance' => ['instanceName' => 'whatsapp ClaudSoft', 'state' => 'open'],
        ]));
    }

    public function test_reads_the_flat_and_legacy_spellings(): void
    {
        $this->assertSame('open', EvolutionInstanceState::readConnectionState(['state' => 'open']));
        $this->assertSame('open', EvolutionInstanceState::readConnectionState(['status' => 'OPEN']));
        $this->assertSame('open', EvolutionInstanceState::readConnectionState(['connectionStatus' => 'open']));
        $this->assertSame('close', EvolutionInstanceState::readConnectionState(['instance' => ['status' => 'close']]));
    }

    public function test_an_unreadable_payload_is_null_and_never_close(): void
    {
        // The whole point: "I could not read it" must not become "the phone is offline".
        foreach ([[], ['foo' => 'bar'], ['state' => ''], ['state' => '   '], 'not an array', null] as $payload) {
            $this->assertNull(EvolutionInstanceState::readConnectionState($payload));
        }
    }

    public function test_an_unknown_upstream_state_is_passed_through_lowercased(): void
    {
        $this->assertSame('refused', EvolutionInstanceState::readConnectionState(['state' => 'REFUSED']));
    }

    public function test_finds_a_row_in_a_bare_v2_list(): void
    {
        $row = EvolutionInstanceState::findRow($this->v2List(), 'whatsapp ClaudSoft');

        $this->assertNotNull($row);
        $this->assertSame('open', EvolutionInstanceState::readConnectionState($row));
    }

    public function test_finds_a_row_despite_stray_whitespace_and_case(): void
    {
        // The name is typed by hand into the admin form and contains a space, so a pasted
        // trailing space or NBSP must not read as "disconnected".
        foreach (['whatsapp ClaudSoft ', ' whatsapp claudsoft', "whatsapp\u{00A0}ClaudSoft", 'whatsapp  ClaudSoft'] as $typed) {
            $this->assertNotNull(
                EvolutionInstanceState::findRow($this->v2List(), $typed),
                'should still match: ['.$typed.']'
            );
        }
    }

    public function test_a_missing_name_returns_null_instead_of_the_first_row(): void
    {
        // Falling back to the first row would stamp another phone's JID and number onto
        // this instance, which is worse than reporting nothing.
        $this->assertNull(EvolutionInstanceState::findRow($this->v2List(), 'whatsapp Sales'));
    }

    public function test_unwraps_wrapped_and_v1_shaped_lists(): void
    {
        $wrapped = ['value' => [['name' => 'a', 'connectionStatus' => 'open']]];
        $this->assertNotNull(EvolutionInstanceState::findRow($wrapped, 'a'));

        $v1 = [['instance' => ['instanceName' => 'b', 'status' => 'open']]];
        $row = EvolutionInstanceState::findRow($v1, 'b');
        $this->assertNotNull($row);
        $this->assertSame('open', EvolutionInstanceState::readConnectionState($row));
    }

    public function test_lists_every_remote_name(): void
    {
        $this->assertSame(
            ['whatsapp ClaudSoft', 'whatsapp ClaudSoft 2'],
            EvolutionInstanceState::names($this->v2List())
        );
    }

    public function test_derives_the_number_from_owner_jid_when_number_is_null(): void
    {
        // Exactly the production shape that showed "—" for a connected phone.
        $this->assertSame('905519665883', EvolutionInstanceState::phoneNumber([
            'name' => 'whatsapp ClaudSoft',
            'connectionStatus' => 'open',
            'number' => null,
            'ownerJid' => '905519665883@s.whatsapp.net',
        ]));
    }

    public function test_prefers_an_explicit_number_and_strips_formatting(): void
    {
        $this->assertSame('905519665883', EvolutionInstanceState::phoneNumber([
            'number' => '+90 551 966 58 83',
            'ownerJid' => '963944123456@s.whatsapp.net',
        ]));
    }

    public function test_a_group_jid_is_not_a_phone_number(): void
    {
        $this->assertNull(EvolutionInstanceState::phoneNumber(['ownerJid' => '123456789-1600000000@g.us']));
        $this->assertNull(EvolutionInstanceState::phoneNumber([]));
    }

    /**
     * The shape Evolution API v2 actually returns from GET /instance/fetchInstances.
     *
     * @return list<array<string, mixed>>
     */
    private function v2List(): array
    {
        return [
            [
                'id' => 'e4f1c0aa-0000-4000-8000-000000000001',
                'name' => 'whatsapp ClaudSoft',
                'connectionStatus' => 'open',
                'ownerJid' => '905519665883@s.whatsapp.net',
                'profileName' => 'ClaudSoft',
                'number' => null,
                'integration' => 'WHATSAPP-BAILEYS',
            ],
            [
                'id' => 'e4f1c0aa-0000-4000-8000-000000000002',
                'name' => 'whatsapp ClaudSoft 2',
                'connectionStatus' => 'close',
                'ownerJid' => null,
                'number' => null,
            ],
        ];
    }
}
