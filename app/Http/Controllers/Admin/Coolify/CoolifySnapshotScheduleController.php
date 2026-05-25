<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Models\CoolifySnapshotSchedule;
use App\Services\Coolify\CoolifyBackupService;
use App\Services\Coolify\CoolifyScheduledSnapshotService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoolifySnapshotScheduleController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyScheduledSnapshotService $scheduler
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $schedules = CoolifySnapshotSchedule::with('creator')->latest()->paginate(20);

        return view('admin.coolify.backups.schedules.index', compact('schedules'));
    }

    public function create()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $projects = $this->coolifyList($this->coolify->listProjects());

        return view('admin.coolify.backups.schedules.create', [
            'projects' => $projects,
            'frequencies' => $this->scheduleFrequencies(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchedule($request);

        CoolifySnapshotSchedule::create([
            'project_uuid' => $validated['project_uuid'],
            'project_name' => $validated['project_name'] ?? null,
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'enabled' => $request->boolean('enabled', true),
            'options' => [
                'include_databases' => $request->boolean('include_databases', true),
                'include_applications' => $request->boolean('include_applications', true),
                'include_services' => $request->boolean('include_services', true),
            ],
            'next_run_at' => $this->scheduler->calculateNextRunAt($validated['frequency']),
            'created_by' => Auth::id(),
        ]);

        $this->logCoolify('create', 'snapshot_schedule', null, $validated['name']);

        return $this->coolifyRedirectSuccess('تم إنشاء الجدولة', 'admin.coolify.backups.schedules.index');
    }

    public function edit(string $uuid)
    {
        $schedule = CoolifySnapshotSchedule::where('uuid', $uuid)->firstOrFail();

        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $projects = $this->coolifyList($this->coolify->listProjects());

        return view('admin.coolify.backups.schedules.edit', [
            'schedule' => $schedule,
            'projects' => $projects,
            'frequencies' => $this->scheduleFrequencies(),
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        $schedule = CoolifySnapshotSchedule::where('uuid', $uuid)->firstOrFail();
        $validated = $this->validateSchedule($request, $schedule->project_uuid);

        $schedule->update([
            'project_uuid' => $validated['project_uuid'],
            'project_name' => $validated['project_name'] ?? null,
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'enabled' => $request->boolean('enabled', true),
            'options' => [
                'include_databases' => $request->boolean('include_databases', true),
                'include_applications' => $request->boolean('include_applications', true),
                'include_services' => $request->boolean('include_services', true),
            ],
            'next_run_at' => $this->scheduler->calculateNextRunAt($validated['frequency']),
        ]);

        $this->logCoolify('update', 'snapshot_schedule', $schedule->uuid, $schedule->name);

        return $this->coolifyRedirectSuccess('تم تحديث الجدولة', 'admin.coolify.backups.schedules.index');
    }

    public function destroy(string $uuid)
    {
        $schedule = CoolifySnapshotSchedule::where('uuid', $uuid)->firstOrFail();
        $name = $schedule->name;
        $schedule->delete();

        $this->logCoolify('delete', 'snapshot_schedule', $uuid, $name);

        return $this->coolifyRedirectSuccess('تم حذف الجدولة', 'admin.coolify.backups.schedules.index');
    }

    public function toggle(string $uuid)
    {
        $schedule = CoolifySnapshotSchedule::where('uuid', $uuid)->firstOrFail();
        $schedule->update(['enabled' => ! $schedule->enabled]);

        return back()->with('success', $schedule->enabled ? 'تم تفعيل الجدولة' : 'تم تعطيل الجدولة');
    }

    public function runNow(string $uuid)
    {
        $schedule = CoolifySnapshotSchedule::where('uuid', $uuid)->firstOrFail();

        if (! $this->scheduler->runSchedule($schedule)) {
            return back()->with('error', 'فشل تشغيل الجدولة — راجع سجل النشاط أو إعدادات S3');
        }

        return back()->with('success', 'بدأت لقطة مجدولة');
    }

    /**
     * @return array<string, string>
     */
    protected function scheduleFrequencies(): array
    {
        return [
            'hourly' => 'كل ساعة',
            'daily' => 'يومي',
            'weekly' => 'أسبوعي',
            'monthly' => 'شهري',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSchedule(Request $request, ?string $defaultProject = null): array
    {
        $validated = $request->validate([
            'project_uuid' => 'required|string',
            'project_name' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'enabled' => 'nullable|boolean',
            'include_databases' => 'nullable|boolean',
            'include_applications' => 'nullable|boolean',
            'include_services' => 'nullable|boolean',
        ]);

        if (empty($validated['project_name']) && $validated['project_uuid'] !== ($defaultProject ?? '')) {
            $project = $this->coolify->getProject($validated['project_uuid']);
            $item = $this->coolifyItem($project);
            $validated['project_name'] = $item['name'] ?? null;
        }

        return $validated;
    }
}
