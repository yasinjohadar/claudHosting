<?php

use App\Support\EvolutionGroupMemberParser;

test('parses evolution group participants', function () {
    $payload = [
        'participants' => [
            ['id' => '1@lid', 'phoneNumber' => '967773246680@s.whatsapp.net', 'admin' => 'admin'],
            ['id' => '2@lid', 'phoneNumber' => '905050580036@s.whatsapp.net', 'admin' => null],
        ],
    ];

    $members = EvolutionGroupMemberParser::parse($payload);

    expect($members)->toHaveCount(2);
    expect($members[0]['phone'])->toBe('967773246680');
    expect($members[0]['is_admin'])->toBeTrue();
    expect($members[1]['phone'])->toBe('905050580036');
    expect($members[1]['is_admin'])->toBeFalse();
});

test('summarizes group info', function () {
    $summary = EvolutionGroupMemberParser::summarizeGroup([
        'id' => '120363@g.us',
        'subject' => 'Test Group',
        'size' => 5,
        'owner' => '905050580036@s.whatsapp.net',
        'announce' => true,
    ], '120363@g.us');

    expect($summary['name'])->toBe('Test Group');
    expect($summary['size'])->toBe(5);
    expect($summary['owner'])->toBe('905050580036');
    expect($summary['is_announce'])->toBeTrue();
});
