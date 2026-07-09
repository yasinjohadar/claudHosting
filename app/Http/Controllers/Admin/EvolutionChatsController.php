<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\Evolution\EvolutionService;
use Illuminate\View\View;

class EvolutionChatsController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService
    ) {}

    public function index(): View
    {
        $chats = [];
        $error = null;
        $instance = $this->evolutionService->activeInstanceName();

        try {
            $response = $this->evolutionService->client()->findChats($instance);
            $chats = is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('admin.pages.evolution-api.chats.index', compact('chats', 'error', 'instance'));
    }
}
