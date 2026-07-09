<?php

use App\Services\WhatsApp\Evolution\EvolutionWebhookParser;

test('parses evolution inbound text message', function () {
    $parser = new EvolutionWebhookParser;
    $parsed = $parser->parse([
        'event' => 'messages.upsert',
        'instance' => 'test',
        'data' => [
            'key' => [
                'remoteJid' => '966501234567@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG123',
            ],
            'message' => ['conversation' => 'مرحباً'],
            'messageTimestamp' => 1710000000,
        ],
    ]);

    expect($parsed['messages'])->toHaveCount(1);
    expect($parsed['messages'][0]->from)->toBe('966501234567@s.whatsapp.net');
    expect($parsed['messages'][0]->textBody)->toBe('مرحباً');
});

test('resolves remoteJidAlt when remoteJid is lid', function () {
    $parser = new EvolutionWebhookParser;
    $parsed = $parser->parse([
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => 'abc123@lid',
                'remoteJidAlt' => '966501234567@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG456',
            ],
            'message' => ['conversation' => 'test'],
        ],
    ]);

    expect($parsed['messages'])->toHaveCount(1);
    expect($parsed['messages'][0]->from)->toBe('966501234567@s.whatsapp.net');
});

test('falls back to lid jid when no phone jid available', function () {
    $parser = new EvolutionWebhookParser;
    $parsed = $parser->parse([
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => 'abc123@lid',
                'fromMe' => false,
                'id' => 'MSG789',
            ],
            'message' => ['conversation' => 'hello lid'],
        ],
    ]);

    expect($parsed['messages'])->toHaveCount(1);
    expect($parsed['messages'][0]->from)->toBe('abc123@lid');
});

test('skips group and outbound messages', function () {
    $parser = new EvolutionWebhookParser;
    $group = $parser->parse([
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '120363001234567890@g.us',
                'fromMe' => false,
                'id' => 'G1',
            ],
            'message' => ['conversation' => 'hi'],
        ],
    ]);

    $outbound = $parser->parse([
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '966501234567@s.whatsapp.net',
                'fromMe' => true,
                'id' => 'O1',
            ],
            'message' => ['conversation' => 'sent'],
        ],
    ]);

    expect($group['messages'])->toBeEmpty();
    expect($outbound['messages'])->toBeEmpty();
});
