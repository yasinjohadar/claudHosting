<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppContact;
use App\Services\WhatsApp\Evolution\EvolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvolutionContactsController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService
    ) {}

    public function index(): View
    {
        $contacts = [];
        $error = null;
        $instance = $this->evolutionService->activeInstanceName();

        try {
            $response = $this->evolutionService->client()->findContacts($instance);
            $contacts = is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('admin.pages.evolution-api.contacts.index', compact('contacts', 'error', 'instance'));
    }

    public function sync(): RedirectResponse
    {
        $instance = $this->evolutionService->activeInstanceName();
        $response = $this->evolutionService->client()->findContacts($instance);
        $list = is_array($response) ? $response : [];

        $count = 0;
        foreach ($list as $contact) {
            if (! is_array($contact)) {
                continue;
            }
            $waId = (string) ($contact['id'] ?? $contact['remoteJid'] ?? '');
            if ($waId === '') {
                continue;
            }
            WhatsAppContact::findOrCreateByWaId($waId);
            $count++;
        }

        return back()->with('success', "تمت مزامنة {$count} جهة اتصال إلى قاعدة المنصة.");
    }
}
