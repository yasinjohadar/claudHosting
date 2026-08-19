<?php

namespace Tests\Unit\Support;

use App\Support\WhatsAppTemplateVariables as Vars;
use PHPUnit\Framework\TestCase;

/**
 * The catalogue feeds two consumers: the chips the admin clicks, and the values substituted
 * at send time. These lock the properties that keep those two in step — a key offered in the
 * UI but absent from the resolver would render as nothing in a real customer's message.
 */
class WhatsAppTemplateVariablesTest extends TestCase
{
    public function test_every_variable_is_fully_described(): void
    {
        foreach (Vars::definitions() as $key => $definition) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $key, "bad key: {$key}");
            $this->assertNotSame('', trim($definition['label']), "{$key} has no label");
            // The sample drives the preview; a blank one makes the preview useless.
            $this->assertNotSame('', trim($definition['sample']), "{$key} has no sample value");
            $this->assertArrayHasKey($definition['group'], Vars::groups(), "{$key} is in an unknown group");
        }
    }

    public function test_every_group_has_at_least_one_variable(): void
    {
        foreach (Vars::grouped() as $groupKey => $group) {
            $this->assertNotEmpty($group['variables'], "group {$groupKey} would render as an empty section");
            $this->assertNotSame('', trim($group['label']));
            $this->assertNotSame('', trim($group['icon']));
        }
    }

    public function test_the_four_requested_groups_exist(): void
    {
        $this->assertSame(
            [Vars::GROUP_CUSTOMER, Vars::GROUP_SUBSCRIPTION, Vars::GROUP_BILLING, Vars::GROUP_SYSTEM],
            array_keys(Vars::groups())
        );
    }

    public function test_no_key_or_alias_is_claimed_twice(): void
    {
        // A duplicate would make substitution order decide the value — silently.
        $all = [];
        foreach (Vars::definitions() as $key => $definition) {
            $all[] = $key;
            foreach ($definition['aliases'] as $alias) {
                $all[] = $alias;
            }
        }

        $duplicates = array_keys(array_filter(array_count_values($all), static fn (int $n): bool => $n > 1));
        $this->assertSame([], $duplicates, 'duplicated keys: '.implode(', ', $duplicates));
    }

    public function test_grouped_covers_exactly_the_canonical_keys(): void
    {
        $fromGroups = [];
        foreach (Vars::grouped() as $group) {
            foreach ($group['variables'] as $variable) {
                $fromGroups[] = $variable['key'];
            }
        }

        sort($fromGroups);
        $canonical = Vars::keys();
        sort($canonical);

        $this->assertSame($canonical, $fromGroups, 'the UI must offer every catalogue key and nothing else');
    }

    public function test_sample_values_cover_every_key_and_alias(): void
    {
        $samples = Vars::sampleValues();

        foreach (Vars::allKnownKeys() as $key) {
            $this->assertArrayHasKey($key, $samples, "{$key} has no preview value");
        }
    }

    public function test_legacy_spellings_are_recognised(): void
    {
        // Written into templates and the password-reset body before this catalogue existed.
        foreach (['student_name', 'user_name', 'student_email', 'email', 'phone'] as $legacy) {
            $this->assertTrue(Vars::isKnown($legacy), "{$legacy} must stay accepted");
        }

        $this->assertSame('customer_name', Vars::canonical('student_name'));
        $this->assertSame('customer_email', Vars::canonical('email'));
        $this->assertSame('customer_name', Vars::canonical('customer_name'));
    }

    public function test_the_dead_course_variables_are_gone(): void
    {
        // {course_name} and {group_name} always resolved to an empty string in the old
        // engine; offering them would promise data this panel does not hold.
        $this->assertFalse(Vars::isKnown('course_name'));
        $this->assertFalse(Vars::isKnown('group_name'));
    }

    public function test_unknown_keys_are_rejected(): void
    {
        $this->assertFalse(Vars::isKnown('customer_nmae'));
        $this->assertNull(Vars::canonical('customer_nmae'));
    }
}
