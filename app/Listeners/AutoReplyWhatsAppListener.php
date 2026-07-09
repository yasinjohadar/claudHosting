<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AutoReplyWhatsAppListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'whatsapp';

    public function __construct(
        private WhatsAppAutoReplyService $autoReplyService,
    ) {}

    public function handle(WhatsAppMessageReceived $event): void
    {
        $this->autoReplyService->scheduleForReply($event->message);
    }
}
