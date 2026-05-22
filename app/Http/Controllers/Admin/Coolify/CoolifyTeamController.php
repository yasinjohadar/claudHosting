<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Models\ClientCoolifyTeam;
use App\Models\User;
use App\Services\Coolify\CoolifyTeamService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyTeamController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyTeamService $teamService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $teamsResponse = $this->coolify->listTeams();
        $teams = $this->coolifyList($teamsResponse);
        $error = $teamsResponse['success'] ? null : ($teamsResponse['message'] ?? 'فشل جلب الفرق');

        $current = $this->coolifyItem($this->coolify->getCurrentTeam()) ?? [];
        $links = $this->teamService->teamLinksByCoolifyId();
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();

        foreach ($teams as $index => $team) {
            $teamId = (int) ($team['id'] ?? 0);
            $link = $links[$teamId] ?? null;
            $teams[$index]['_link'] = $link;
            $teams[$index]['_client'] = $link?->client;
            $teams[$index]['_has_token'] = $link?->hasApiToken() ?? false;
        }

        return view('admin.coolify.teams.index', compact(
            'teams',
            'current',
            'error',
            'clientUsers'
        ));
    }

    public function show(int $teamId)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $link = ClientCoolifyTeam::query()
            ->where('coolify_team_id', $teamId)
            ->with('client')
            ->first();

        $teamResponse = $this->coolify->getTeam($teamId);
        $team = $this->coolifyItem($teamResponse);

        if ($team === null) {
            return $this->coolifyRedirectError(
                $teamResponse['message'] ?? 'الفريق غير موجود أو غير متاح للتوكن الحالي',
                'admin.coolify.teams.index'
            );
        }

        $members = $this->coolifyList($this->coolify->getTeamMembers($teamId));
        $projects = collect();
        $projectsError = null;

        if ($link !== null) {
            if ($link->hasApiToken()) {
                $projects = $this->teamService->projectsForTeam($link);
                if ($projects->isEmpty()) {
                    $api = $this->teamService->apiForUser($link->user_id);
                    if ($api) {
                        $listRes = $api->listProjects();
                        if (! ($listRes['success'] ?? false)) {
                            $projectsError = $listRes['message'] ?? 'فشل جلب مشاريع الفريق';
                        }
                    }
                }
            } else {
                $projectsError = 'أضف توكن API الخاص بالفريق لعرض المشاريع';
            }
        }

        $clientUsers = User::query()
            ->where(function ($q) use ($link) {
                $q->whereDoesntHave('clientCoolifyTeam');
                if ($link !== null) {
                    $q->orWhere('id', $link->user_id);
                }
            })
            ->orderBy('name')
            ->select(['id', 'name', 'email'])
            ->limit(500)
            ->get();

        return view('admin.coolify.teams.show', compact(
            'teamId',
            'team',
            'members',
            'link',
            'projects',
            'projectsError',
            'clientUsers'
        ));
    }

    public function linkClient(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'coolify_team_id' => 'required|integer|min:1',
            'team_name' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            '_return' => 'nullable|string',
        ]);

        $user = User::findOrFail((int) $validated['user_id']);
        $teamId = (int) $validated['coolify_team_id'];
        $token = trim((string) ($validated['api_token'] ?? ''));
        $token = $token !== '' ? $token : null;

        $result = $this->teamService->linkTeamToUser(
            $user,
            $teamId,
            $validated['team_name'] ?? null,
            $token,
            $validated['notes'] ?? null
        );

        $return = $this->validatedReturnUrl();
        if ($return !== null) {
            return redirect()->to($return)->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        if ($result['success']) {
            return $this->coolifyRedirectSuccess(
                $result['message'],
                'admin.coolify.teams.show',
                ['teamId' => $teamId]
            );
        }

        return back()->withInput()->with('error', $result['message']);
    }

    public function unlink(User $user)
    {
        $link = $this->teamService->teamForUser($user->id);
        $teamId = $link?->coolify_team_id;

        $result = $this->teamService->unlinkTeam($user);

        $return = $this->validatedReturnUrl();
        if ($return !== null) {
            return redirect()->to($return)->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        if ($result['success'] && $teamId) {
            return $this->coolifyRedirectSuccess($result['message'], 'admin.coolify.teams.show', ['teamId' => $teamId]);
        }

        return $this->coolifyRedirectSuccess(
            $result['message'],
            'admin.coolify.teams.index'
        );
    }
}
