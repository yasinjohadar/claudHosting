<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Services\Auth\PasswordResetDeliveryService;
use App\Support\InternationalPhoneDigits;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reproduces the reported failure: WhatsApp password reset answered "لا يمكننا العثور على
 * حساب مرتبط بهذا الرقم" for +90 5519665883 while that account existed and the admin user
 * list showed exactly that number.
 *
 * RefreshDatabase is unusable here — four unrelated migrations query MySQL
 * information_schema and abort on sqlite — so only the tables this lookup reads are built.
 */
class FindUserByPhoneTest extends TestCase
{
    /** @var list<string> */
    private array $migrations = [
        '0001_01_01_000000_create_users_table.php',
        '2026_05_31_120000_add_country_code_to_users_table.php',
        '2023_12_16_000001_create_customers_table.php',
        '2026_03_01_150000_add_user_id_to_customers_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->migrations as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        foreach (['customers', 'users', 'password_reset_tokens', 'sessions'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function makeUser(array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'phone' => '5519665883',
            'country_code' => '+90',
        ], $attributes));

        $user->password = 'irrelevant-for-this-lookup';
        $user->save();

        return $user;
    }

    private function service(): PasswordResetDeliveryService
    {
        return app(PasswordResetDeliveryService::class);
    }

    public function test_finds_the_account_from_the_country_code_and_national_number(): void
    {
        $user = $this->makeUser();

        // What the reset form submits for "🇹🇷 +90" plus "5519665883".
        $lookup = InternationalPhoneDigits::fromCountryAndLocal('+90', '5519665883');

        $found = $this->service()->findUserByPhone($lookup);

        $this->assertNotNull($found, 'the account exists and must be found');
        $this->assertSame($user->id, $found->id);
    }

    public function test_finds_the_account_from_the_full_international_number(): void
    {
        $user = $this->makeUser();

        foreach (['905519665883', '+905519665883', '+90 551 966 58 83'] as $typed) {
            $found = $this->service()->findUserByPhone($typed);

            $this->assertNotNull($found, 'should match for input: '.$typed);
            $this->assertSame($user->id, $found->id);
        }
    }

    public function test_finds_a_legacy_row_that_stored_the_full_number_in_phone(): void
    {
        $user = $this->makeUser(['phone' => '905519665883']);

        $found = $this->service()->findUserByPhone(
            InternationalPhoneDigits::fromCountryAndLocal('+90', '5519665883')
        );

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_a_different_number_is_still_not_found(): void
    {
        $this->makeUser();

        // The same national digits under another dial code are a different person, and the
        // old bare-national comparison would have matched them against each other.
        $this->assertNull($this->service()->findUserByPhone(
            InternationalPhoneDigits::fromCountryAndLocal('+963', '5519665883')
        ));
        $this->assertNull($this->service()->findUserByPhone('905519665884'));
    }

    public function test_the_whatsapp_recipient_keeps_the_country_code(): void
    {
        $user = $this->makeUser();

        // Before the fix this returned +5519665883 — a Brazilian number, i.e. the reset
        // message would have gone to someone else entirely.
        $this->assertSame('+905519665883', $this->service()->resolveWhatsAppRecipient($user));
    }
}
