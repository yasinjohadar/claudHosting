<?php

use App\Models\User;
use App\Services\WhatsApp\Evolution\EvolutionGroupCompareService;
use App\Support\WapiPhoneNormalizer;
use Tests\TestCase;

uses(TestCase::class);

test('phone index matches numbers with different country prefix lengths', function () {
    $service = new EvolutionGroupCompareService(app(\App\Services\WhatsApp\Evolution\EvolutionService::class));

    $index = $service->buildPhoneIndex([
        ['phone' => '905516764205', 'phone_jid' => '905516764205@s.whatsapp.net'],
    ]);

    expect($service->isInWhatsAppGroup('905516764205', $index))->toBeTrue();
    expect($service->isInWhatsAppGroup('5516764205', $index))->toBeTrue();
    expect($service->isInWhatsAppGroup('9999999999', $index))->toBeFalse();
});

test('wapi normalizer strips non digits', function () {
    expect(WapiPhoneNormalizer::normalize('+90 551 676 4205'))->toBe('905516764205');
});
