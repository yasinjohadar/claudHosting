<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionInstance;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\Evolution\EvolutionRotatingSendService;
use App\Services\WhatsApp\Evolution\EvolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EvolutionSendController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService,
        private EvolutionRotatingSendService $rotatingSendService,
    ) {}

    public function textForm(): View
    {
        return view('admin.pages.evolution-api.send.text', [
            'instanceName' => $this->evolutionService->activeInstanceName(),
            'rotationActive' => $this->rotatingSendService->isRotationActive(),
            'rotationPoolCount' => EvolutionInstance::rotationPoolCount(),
        ]);
    }

    public function sendText(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $result = $this->rotatingSendService->sendWithRotation(
                fn (string $instanceName) => $this->evolutionService
                    ->providerForInstance($instanceName)
                    ->sendText($validated['to'], $validated['text'])
            );

            return back()->with(
                'success',
                'تم إرسال الرسالة إلى '.$validated['to'].' عبر «'.$result['instance_name'].'».'
            );
        } catch (Throwable $e) {
            return back()->with('error', EvolutionApiException::resolveUserMessage($e));
        }
    }

    public function mediaForm(): View
    {
        return view('admin.pages.evolution-api.send.media', [
            'instanceName' => $this->evolutionService->activeInstanceName(),
            'rotationActive' => $this->rotatingSendService->isRotationActive(),
            'rotationPoolCount' => EvolutionInstance::rotationPoolCount(),
        ]);
    }

    public function sendMedia(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:100'],
            'mediatype' => ['required', 'in:image,video,audio,document'],
            'media' => ['required', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'fileName' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'number' => $validated['to'],
            'mediatype' => $validated['mediatype'],
            'media' => $validated['media'],
            'caption' => $validated['caption'] ?? null,
            'fileName' => $validated['fileName'] ?? null,
        ];

        try {
            $result = $this->rotatingSendService->sendWithRotation(
                fn (string $instanceName) => $this->evolutionService
                    ->providerForInstance($instanceName)
                    ->sendMediaMessage($payload)
            );

            return back()->with(
                'success',
                'تم إرسال الوسائط ('.$validated['mediatype'].') عبر «'.$result['instance_name'].'».'
            );
        } catch (Throwable $e) {
            return back()->with('error', EvolutionApiException::resolveUserMessage($e));
        }
    }

    public function advancedForm(string $type): View
    {
        $allowed = ['buttons', 'list', 'poll', 'location', 'contact', 'sticker', 'status'];
        abort_unless(in_array($type, $allowed, true), 404);

        return view('admin.pages.evolution-api.send.advanced', [
            'type' => $type,
            'instanceName' => $this->evolutionService->activeInstanceName(),
            'rotationActive' => $this->rotatingSendService->isRotationActive(),
            'rotationPoolCount' => EvolutionInstance::rotationPoolCount(),
        ]);
    }

    public function sendAdvanced(Request $request, string $type): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:100'],
            'payload' => ['required', 'string'],
        ]);

        $payload = json_decode($validated['payload'], true);
        if (! is_array($payload)) {
            return back()->with('error', 'صيغة JSON غير صالحة.');
        }

        $payload['number'] = $validated['to'];
        $client = $this->evolutionService->client();

        try {
            $result = $this->rotatingSendService->sendWithRotation(
                function (string $instanceName) use ($client, $type, $payload) {
                    return match ($type) {
                        'buttons' => $client->sendButtons($instanceName, $payload),
                        'list' => $client->sendList($instanceName, $payload),
                        'poll' => $client->sendPoll($instanceName, $payload),
                        'location' => $client->sendLocation($instanceName, $payload),
                        'contact' => $client->sendContact($instanceName, $payload),
                        'sticker' => $client->sendSticker($instanceName, $payload),
                        'status' => $client->sendStatus($instanceName, $payload),
                        default => abort(404),
                    };
                }
            );

            return back()->with(
                'success',
                'تم إرسال رسالة '.$type.' عبر «'.$result['instance_name'].'».'
            );
        } catch (Throwable $e) {
            return back()->with('error', EvolutionApiException::resolveUserMessage($e));
        }
    }

    public function messagesIndex(): View
    {
        $messages = WhatsAppMessage::with('contact')
            ->latest()
            ->paginate(30);

        return view('admin.pages.evolution-api.messages.index', compact('messages'));
    }
}
