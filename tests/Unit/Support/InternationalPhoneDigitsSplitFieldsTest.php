<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\InternationalPhoneDigits;
use Tests\TestCase;

/**
 * Users store the NATIONAL number in `phone` and the dial code in `country_code`.
 *
 * Reading `phone` on its own passed the generic E.164 shape check for a Turkish
 * 5519665883, so it was treated as already-canonical. Two consequences, both live:
 * WhatsApp password reset answered "no account with this number" for an account that
 * existed, and the OTP for that account would have been addressed to +5519665883 — a
 * Brazilian number belonging to a stranger.
 */
class InternationalPhoneDigitsSplitFieldsTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string, 2: ?string, 3: string}>
     */
    public static function splitFieldCases(): array
    {
        return [
            ['+90', '5519665883', '905519665883', 'the reported account: TR national number'],
            ['+963', '944123456', '963944123456', 'SY national number'],
            ['+90', '05519665883', '905519665883', 'national number carrying a trunk zero'],
            // Single-digit dial codes are the trap: the national part must not be shortened.
            ['+1', '2025550123', '12025550123', 'NANP national number'],
            // Legacy rows that kept the full international number in `phone`.
            ['+90', '905519665883', '905519665883', 'dial code already inside phone'],
            ['+90', '+905519665883', '905519665883', 'stored with a leading plus'],
            ['+90', '+90 551 966 58 83', '905519665883', 'stored formatted with a plus'],
            ['', '905519665883', '905519665883', 'no country_code column value'],
            ['+90', '', null, 'no phone at all'],
            ['', '', null, 'nothing stored'],
        ];
    }

    /**
     * @dataProvider splitFieldCases
     */
    public function test_split_fields_resolve_to_canonical_e164(string $countryCode, string $phone, ?string $expected, string $label): void
    {
        $this->assertSame(
            $expected,
            InternationalPhoneDigits::fromSplitFields($countryCode, $phone),
            $label
        );
    }

    public function test_for_user_matches_what_the_reset_form_produces(): void
    {
        // The exact pair the login form and the stored row have to agree on.
        $user = new User(['phone' => '5519665883', 'country_code' => '+90']);

        $this->assertSame(
            InternationalPhoneDigits::fromCountryAndLocal('+90', '5519665883'),
            InternationalPhoneDigits::forUser($user),
            'lookup and storage must canonicalise identically or reset can never find the account'
        );
    }

    public function test_for_user_never_drops_the_country_code(): void
    {
        $user = new User(['phone' => '5519665883', 'country_code' => '+90']);

        $digits = InternationalPhoneDigits::forUser($user);

        $this->assertSame('905519665883', $digits);
        // The whole point: the OTP recipient must not be a different country's number.
        $this->assertSame('+905519665883', InternationalPhoneDigits::toDisplay($digits));
    }

    public function test_for_user_handles_a_legacy_row_that_stored_the_full_number(): void
    {
        $user = new User(['phone' => '905519665883', 'country_code' => '+90']);

        $this->assertSame('905519665883', InternationalPhoneDigits::forUser($user));
    }

    public function test_for_user_is_null_without_a_phone(): void
    {
        $this->assertNull(InternationalPhoneDigits::forUser(new User(['country_code' => '+90'])));
    }
}
