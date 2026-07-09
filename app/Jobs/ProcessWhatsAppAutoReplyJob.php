<?php

namespace App\Jobs;

use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $timeout = 120;

    public string $queue = 'whatsapp';

    public function __construct(
        public int $contactId,
    ) {}

    public function handle(WhatsAppAutoReplyService $autoReplyService): void
    {
        if ($autoReplyService->shouldDeferAutoReply($this->contactId)) {
            $wait = $autoReplyService->secondsUntilAutoReplyReady($this->contactId);
            Log::channel('whatsapp')->info('AutoReply: deferring job for debounce', [
                'contact_id' => $this->contactId,
                'wait_seconds' => $wait,
            ]);
            $this->release(max(1, $wait));

            return;
        }

        $autoReplyService->processContact($this->contactId);
    }
}
