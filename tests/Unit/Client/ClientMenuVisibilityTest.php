<?php

namespace Tests\Unit\Client;

use App\Support\ClientMenuVisibility;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The sidebar's per-user visibility conditions, and that the renderer honours them.
 *
 * The DB-backed branch (does this client have Coolify WordPress sites?) is exercised by
 * substituting a fake resolver into the container — the project's Feature suite cannot
 * run here, because four migrations query MySQL's information_schema and so
 * RefreshDatabase dies on sqlite.
 */
class ClientMenuVisibilityTest extends TestCase
{
    public function test_an_item_without_a_condition_is_always_visible(): void
    {
        $visibility = new ClientMenuVisibility;

        $this->assertTrue($visibility->passes(null));
        $this->assertTrue($visibility->passes(''));
        $this->assertTrue($visibility->passes('   '));
    }

    public function test_an_unknown_condition_leaves_the_item_visible(): void
    {
        // A typo in config must not silently remove navigation.
        Auth::setUser(new \App\Models\User(['name' => 'client']));

        $this->assertTrue((new ClientMenuVisibility)->passes('no_such_condition'));
    }

    public function test_a_guest_fails_every_real_condition(): void
    {
        Auth::logout();

        $this->assertFalse((new ClientMenuVisibility)->passes('has_wordpress_sites'));
        // ...but an unconditional item still renders, so a guest layout is not emptied.
        $this->assertTrue((new ClientMenuVisibility)->passes(null));
    }

    public function test_an_unreachable_database_does_not_break_the_layout(): void
    {
        // The sidebar renders on every page, so this must degrade rather than throw:
        // a 500 on every route would be far worse than one link to an empty page.
        // (No migrations have run here, so coolify_wordpress_sites does not exist.)
        Auth::setUser(new \App\Models\User(['name' => 'client']));
        Auth::user()->id = 4242;

        $this->assertTrue((new ClientMenuVisibility)->passes('has_wordpress_sites'));
    }

    public function test_a_user_without_an_id_does_not_hide_navigation(): void
    {
        Auth::setUser(new \App\Models\User(['name' => 'client']));

        $this->assertTrue((new ClientMenuVisibility)->passes('has_wordpress_sites'));
    }

    public function test_the_wordpress_item_declares_the_condition(): void
    {
        $wordpress = null;
        foreach (config('client-menu', []) as $item) {
            if (($item['label'] ?? null) === 'WordPress') {
                $wordpress = $item;
            }
        }

        $this->assertNotNull($wordpress, 'the WordPress menu item is missing');
        $this->assertSame('has_wordpress_sites', $wordpress['visible'] ?? null);
    }

    public function test_every_other_item_is_unconditional(): void
    {
        // Guards against a future edit accidentally gating core navigation.
        foreach (config('client-menu', []) as $item) {
            if (($item['label'] ?? null) === 'WordPress') {
                continue;
            }
            $this->assertArrayNotHasKey('visible', $item, ($item['label'] ?? '?').' should be unconditional');
        }
    }

    /**
     * @param  list<string>  $failing  conditions the fake should report as not passing
     */
    protected function renderSidebarWith(array $failing): string
    {
        $this->instance(ClientMenuVisibility::class, new class($failing) extends ClientMenuVisibility
        {
            /** @param list<string> $failing */
            public function __construct(protected array $failing) {}

            public function passes(?string $condition): bool
            {
                return ! in_array((string) $condition, $this->failing, true);
            }
        });

        Auth::setUser(new \App\Models\User(['name' => 'client']));

        return view('client.layouts.partials.sidebar-menu')->render();
    }

    public function test_the_sidebar_hides_wordpress_when_the_condition_fails(): void
    {
        $html = $this->renderSidebarWith(['has_wordpress_sites']);

        $this->assertStringNotContainsString('WordPress', $html);
        // Everything unconditional survives.
        $this->assertStringContainsString('الرئيسية', $html);
        $this->assertStringContainsString('الفواتير', $html);
        $this->assertStringContainsString('الموقع العام', $html);
    }

    public function test_the_sidebar_shows_wordpress_when_the_condition_passes(): void
    {
        $html = $this->renderSidebarWith([]);

        $this->assertStringContainsString('WordPress', $html);
        $this->assertStringContainsString('الرئيسية', $html);
    }

    public function test_coolify_stays_hidden(): void
    {
        // It was removed from the config outright, so no condition can bring it back.
        $html = $this->renderSidebarWith([]);

        $this->assertStringNotContainsString('Coolify', $html);
    }
}
