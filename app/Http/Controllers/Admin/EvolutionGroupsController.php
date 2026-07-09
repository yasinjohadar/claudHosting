<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Support\EvolutionGroupMemberParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvolutionGroupsController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService
    ) {}

    public function index(Request $request): View
    {
        $groups = [];
        $error = null;
        $errorHint = null;
        $instance = $this->evolutionService->activeInstanceName();

        try {
            $response = $this->evolutionService->clientFor(null, $instance)->fetchAllGroups(
                $instance,
                $request->boolean('with_participants')
            );
            $groups = is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            $error = EvolutionApiException::resolveUserMessage($e);
            $errorHint = str_contains(strtolower($error), 'bad request')
                ? 'تأكد أن Instance متصل (open) وأن الإعدادات محفوظة بشكل صحيح.'
                : 'تحقق من اتصال Evolution API أو أعد تحميل الصفحة.';
        }

        return view('admin.pages.evolution-api.groups.index', compact('groups', 'error', 'errorHint', 'instance'));
    }

    public function show(Request $request): View
    {
        $context = $this->loadGroupContext((string) $request->query('jid', ''));

        return view('admin.pages.evolution-api.groups.show', $context);
    }

    public function members(Request $request): View
    {
        $context = $this->loadGroupContext((string) $request->query('jid', ''));

        return view('admin.pages.evolution-api.groups.members', $context);
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_jid' => ['required', 'string'],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $this->evolutionService->provider()->sendText($validated['group_jid'], $validated['text']);

        return back()->with('success', 'تم إرسال الرسالة إلى المجموعة بنجاح.');
    }

    public function sendMemberMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:50'],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $this->evolutionService->provider()->sendText($validated['to'], $validated['text']);

        return back()->with('success', 'تم إرسال الرسالة إلى ' . $validated['to'] . ' بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroupContext(string $groupJid): array
    {
        abort_if($groupJid === '', 404);

        $instance = $this->evolutionService->activeInstanceName();
        $error = null;

        try {
            $client = $this->evolutionService->clientFor(null, $instance);
            $group = $client->findGroupByJid($instance, $groupJid);
            $membersRaw = $client->findGroupMembers($instance, $groupJid);
        } catch (\Throwable $e) {
            $error = EvolutionApiException::resolveUserMessage($e);
            $group = [];
            $membersRaw = [];
        }

        $groupInfo = EvolutionGroupMemberParser::summarizeGroup($group, $groupJid);
        $members = EvolutionGroupMemberParser::parse($membersRaw);

        if ($groupInfo['size'] === 0 && $members !== []) {
            $groupInfo['size'] = count($members);
        }

        return compact('group', 'membersRaw', 'members', 'groupInfo', 'groupJid', 'instance', 'error');
    }
}
