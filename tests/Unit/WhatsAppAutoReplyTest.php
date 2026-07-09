<?php

use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyHumanizer;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyPromptBuilder;

test('humanizer splits long text into chunks respecting max chars', function () {
    $humanizer = new WhatsAppAutoReplyHumanizer;
    $long = str_repeat('كلمة ', 80);
    $chunks = $humanizer->splitIntoChunks(trim($long), 100, 5);

    expect($chunks)->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(100);
    }
    expect(count($chunks))->toBeLessThanOrEqual(5);
});

test('humanizer splits on paragraph breaks', function () {
    $humanizer = new WhatsAppAutoReplyHumanizer;
    $text = "مرحباً بك.\n\nهذا الجزء الثاني.\n\nوالثالث.";
    $chunks = $humanizer->splitIntoChunks($text, 350, 3);

    expect($chunks)->toHaveCount(3);
});

test('humanizer respects max chunks limit', function () {
    $humanizer = new WhatsAppAutoReplyHumanizer;
    $text = "أ\n\nب\n\nج\n\nد\n\nه";
    $chunks = $humanizer->splitIntoChunks($text, 350, 2);

    expect($chunks)->toHaveCount(2);
});

test('prompt builder includes faq section when provided', function () {
    $builder = new WhatsAppAutoReplyPromptBuilder;
    $prompt = $builder->buildSystemPrompt([
        'auto_reply_ai_system_prompt' => '',
        'auto_reply_faq_context' => 'س: مواعيد الدعم؟ ج: 9–5',
    ]);

    expect($prompt)->toContain('=== FAQ ===');
    expect($prompt)->toContain('مواعيد الدعم');
});

test('prompt builder uses custom system prompt when set', function () {
    $builder = new WhatsAppAutoReplyPromptBuilder;
    $prompt = $builder->buildSystemPrompt([
        'auto_reply_ai_system_prompt' => 'رد مخصص',
        'auto_reply_faq_context' => '',
    ]);

    expect($prompt)->toBe('رد مخصص');
});

test('prompt builder without faq instructs general ai answers', function () {
    $builder = new WhatsAppAutoReplyPromptBuilder;
    $prompt = $builder->buildSystemPrompt([
        'auto_reply_ai_system_prompt' => '',
        'auto_reply_faq_context' => '',
    ]);

    expect($prompt)->toContain('لا توجد قائمة أسئلة شائعة');
    expect($prompt)->toContain('أجب على سؤال الطالب');
});

test('prompt builder builds chat messages from incoming list', function () {
    $builder = new WhatsAppAutoReplyPromptBuilder;
    $messages = $builder->buildChatMessages(
        ['auto_reply_ai_system_prompt' => 'sys', 'auto_reply_faq_context' => ''],
        ['السلام', 'عندي سؤال']
    );

    expect($messages)->toHaveCount(2);
    expect($messages[0]['role'])->toBe('system');
    expect($messages[1]['content'])->toContain('السلام');
    expect($messages[1]['content'])->toContain('عندي سؤال');
});

test('instances match ignores case and spaces', function () {
    $svc = app(App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyService::class);

    expect($svc->instancesMatch('WhatsApp ClaudSoft', 'whatsapp claudsoft'))->toBeTrue();
    expect($svc->instancesMatch('yurtuyrt', 'whatsapp ClaudSoft'))->toBeFalse();
});

test('evolution service parses bulk instance names', function () {
    $svc = app(App\Services\WhatsApp\Evolution\EvolutionService::class);
    $names = $svc->parseInstanceNamesList("whatsapp A\nwhatsapp B\n\nwhatsapp C  ");

    expect($names)->toBe(['whatsapp A', 'whatsapp B', 'whatsapp C']);
});
