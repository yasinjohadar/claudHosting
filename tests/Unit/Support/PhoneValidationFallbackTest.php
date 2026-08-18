<?php

namespace Tests\Unit\Support;

use App\Support\PhoneField;
use App\Support\UserPhoneCountryValidator;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * libphonenumber is a hard composer dependency, but a server whose vendor/ predates it
 * fatals with "Class libphonenumber\PhoneNumberUtil not found" and 500s on every page that
 * validates a phone — which is what happened on production for PUT /client/profile.
 *
 * These lock the guarded fallback: with the library absent the same inputs must still be
 * accepted or rejected, and a valid number must still produce E.164 digits — because
 * returning null there would make PhoneField::normalizeForStorage() report "no phone" and
 * silently drop the number the user just typed.
 */
class PhoneValidationFallbackTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setLibraryAvailable(null);
        parent::tearDown();
    }

    /**
     * Force the memoised availability flag. null = re-detect for real.
     */
    protected function setLibraryAvailable(?bool $available): void
    {
        $reflection = new ReflectionClass(UserPhoneCountryValidator::class);

        $flag = $reflection->getProperty('libraryAvailable');
        $flag->setAccessible(true);
        $flag->setValue(null, $available);

        // Suppress the one-time warning so a deliberate test does not spam the log.
        $logged = $reflection->getProperty('missingLibraryLogged');
        $logged->setAccessible(true);
        $logged->setValue(null, $available !== true);
    }

    /**
     * @return list<array{0: string, 1: string, 2: bool, 3: string}>
     */
    public static function pairs(): array
    {
        return [
            // country code, local phone, should be accepted, label
            ['+90', '5519665883', true, 'valid TR mobile'],
            ['+963', '944123456', true, 'valid SY mobile'],
            ['', '', true, 'both empty — phone is optional'],
            ['+90', '905519665883', false, 'dial code duplicated inside the field'],
            ['+90', '', false, 'country code without a phone'],
            ['', '5519665883', false, 'phone without a country code'],
            ['+999', '5551234', false, 'unknown country code'],
            ['+90', '12', false, 'far too short to be E.164'],
        ];
    }

    public function test_the_library_is_actually_installed_locally(): void
    {
        // If this ever fails the fallback is masking a broken local install.
        $this->assertTrue(
            class_exists(\libphonenumber\PhoneNumberUtil::class),
            'libphonenumber is missing locally — run composer install'
        );
    }

    public function test_validation_verdicts_match_with_and_without_the_library(): void
    {
        foreach (self::pairs() as [$countryCode, $phone, $shouldPass, $label]) {
            $this->setLibraryAvailable(true);
            $withLibrary = UserPhoneCountryValidator::validatePair($countryCode, $phone);

            $this->setLibraryAvailable(false);
            $withoutLibrary = UserPhoneCountryValidator::validatePair($countryCode, $phone);

            $this->assertSame(
                $withLibrary === null,
                $withoutLibrary === null,
                "verdict diverged for: {$label}"
            );
            $this->assertSame($shouldPass, $withoutLibrary === null, "unexpected verdict for: {$label}");
        }
    }

    public function test_a_valid_number_still_yields_e164_without_the_library(): void
    {
        foreach ([['+90', '5519665883', '905519665883'], ['+963', '944123456', '963944123456']] as [$cc, $phone, $expected]) {
            $this->setLibraryAvailable(true);
            $this->assertSame($expected, UserPhoneCountryValidator::expectedE164Digits($cc, $phone));

            $this->setLibraryAvailable(false);
            $this->assertSame($expected, UserPhoneCountryValidator::expectedE164Digits($cc, $phone));
        }
    }

    public function test_a_leading_zero_in_the_local_part_is_stripped_either_way(): void
    {
        $this->setLibraryAvailable(false);

        $this->assertSame('905519665883', UserPhoneCountryValidator::expectedE164Digits('+90', '05519665883'));
    }

    public function test_storage_normalisation_survives_a_missing_library(): void
    {
        // The regression that mattered: this returning null wipes the user's phone.
        $this->setLibraryAvailable(false);

        $this->assertSame(
            ['country_code' => '+90', 'phone' => '5519665883', 'e164' => '905519665883'],
            PhoneField::normalizeForStorage('+90', '5519665883')
        );
    }

    public function test_rubbish_still_fails_normalisation_without_the_library(): void
    {
        $this->setLibraryAvailable(false);

        $this->assertNull(PhoneField::normalizeForStorage('+90', '1'));
        $this->assertNull(PhoneField::normalizeForStorage('', ''));
    }

    public function test_the_fallback_splitter_prefers_the_longest_dial_code(): void
    {
        $method = new ReflectionMethod(PhoneField::class, 'splitByLongestDialCode');
        $method->setAccessible(true);

        $this->assertSame(
            ['country_code' => '+963', 'phone' => '944123456'],
            $method->invoke(null, '963944123456')
        );
        $this->assertSame(
            ['country_code' => '+90', 'phone' => '5519665883'],
            $method->invoke(null, '905519665883')
        );
        // Nothing matches, or nothing is left after the code.
        $this->assertNull($method->invoke(null, '0000'));
    }

    public function test_form_values_still_split_a_full_number_without_the_library(): void
    {
        $this->setLibraryAvailable(false);

        $this->assertSame(
            ['country_code' => '+90', 'phone' => '5519665883'],
            PhoneField::valuesForForm(null, '905519665883')
        );
    }
}
