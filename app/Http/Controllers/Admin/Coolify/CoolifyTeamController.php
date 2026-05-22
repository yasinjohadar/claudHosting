<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;

class CoolifyTeamController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(protected CoolifyApiService $coolify)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $teams = $this->coolifyList($this->coolify->listTeams());
        $current = $this->coolifyItem($this->coolify->getCurrentTeam()) ?? [];
        $members = $this->coolifyList($this->coolify->getCurrentTeamMembers());

        return view('admin.coolify.teams.index', compact('teams', 'current', 'members'));
    }
}
