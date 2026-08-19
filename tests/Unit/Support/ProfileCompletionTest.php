<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ProfileCompletion;
use Tests\TestCase;

/**
 * The widget used to read 0% for an account that had a name and an email, because the old
 * inline calculation counted neither. 0% tells someone who has registered that they have
 * done nothing, which is both wrong and discouraging.
 */
class ProfileCompletionTest extends TestCase
{
    private function user(array $attributes = []): User
    {
        return new User(array_merge([
            'name' => 'Osama Addas',
            'email' => 'eng.osama.addas@gmail.com',
        ], $attributes));
    }

    public function test_a_freshly_registered_account_is_not_zero_percent(): void
    {
        $completion = ProfileCompletion::for($this->user());

        // name + email out of six required fields.
        $this->assertSame(33, $completion->percent());
        $this->assertSame(2, $completion->completedCount());
        $this->assertSame(6, $completion->totalCount());
        $this->assertFalse($completion->isComplete());
    }

    public function test_a_truly_empty_profile_is_zero_percent(): void
    {
        $completion = ProfileCompletion::for(new User);

        $this->assertSame(0, $completion->percent());
        $this->assertSame('danger', $completion->tone());
    }

    public function test_all_required_fields_reach_one_hundred_without_the_optional_ones(): void
    {
        // An individual with no company name, photo or username must still be able to finish.
        $completion = ProfileCompletion::for($this->user([
            'phone' => '5519665883',
            'country_code' => '+963',
            'country' => 'SY',
            'city' => 'Damascus',
            'address1' => 'Mazzeh St 12',
        ]));

        $this->assertSame(100, $completion->percent());
        $this->assertTrue($completion->isComplete());
        $this->assertSame([], $completion->missing());
        $this->assertSame('success', $completion->tone());

        // Still surfaced, just not blocking.
        $this->assertSame(
            ['photo', 'username', 'companyname'],
            array_column($completion->optionalMissing(), 'key')
        );
    }

    public function test_a_phone_without_its_dial_code_does_not_count(): void
    {
        // A bare national number cannot be dialled or used for WhatsApp delivery, so
        // marking it done would promise a channel that does not work.
        $completion = ProfileCompletion::for($this->user(['phone' => '5519665883']));

        $this->assertContains('phone', array_column($completion->missing(), 'key'));

        $withCode = ProfileCompletion::for($this->user(['phone' => '5519665883', 'country_code' => '+963']));
        $this->assertNotContains('phone', array_column($withCode->missing(), 'key'));
    }

    public function test_whitespace_only_values_do_not_count_as_filled(): void
    {
        $completion = ProfileCompletion::for($this->user(['city' => '   ', 'address1' => '']));

        $missing = array_column($completion->missing(), 'key');
        $this->assertContains('city', $missing);
        $this->assertContains('address1', $missing);
    }

    public function test_missing_and_done_together_cover_every_required_field(): void
    {
        $completion = ProfileCompletion::for($this->user(['country' => 'SY']));

        $this->assertCount(
            $completion->totalCount(),
            array_merge($completion->missing(), $completion->done())
        );
    }

    public function test_every_field_explains_why_it_matters(): void
    {
        // A blank reason would leave the UI with an empty hint line.
        $completion = ProfileCompletion::for(new User);

        foreach (array_merge($completion->missing(), $completion->optionalMissing()) as $item) {
            $this->assertNotSame('', trim($item['why']), $item['key'].' has no reason text');
            $this->assertNotSame('', trim($item['label']), $item['key'].' has no label');
            $this->assertNotSame('', trim($item['icon']), $item['key'].' has no icon');
        }
    }

    public function test_the_tone_tracks_the_percentage(): void
    {
        $this->assertSame('danger', ProfileCompletion::for(new User)->tone());
        // 33% (name + email) must not be painted red — it reads as an error, not as progress.
        $this->assertSame('warning', ProfileCompletion::for($this->user())->tone());
        $this->assertSame('primary', ProfileCompletion::for($this->user([
            'country' => 'SY', 'city' => 'Damascus',
        ]))->tone());
    }

    public function test_the_headline_changes_with_progress(): void
    {
        $this->assertSame('ملفك الشخصي غير مكتمل', ProfileCompletion::for($this->user())->headline());

        $complete = ProfileCompletion::for($this->user([
            'phone' => '5519665883', 'country_code' => '+963',
            'country' => 'SY', 'city' => 'Damascus', 'address1' => 'Mazzeh',
        ]));
        $this->assertSame('ملفك مكتمل', $complete->headline());
    }
}
