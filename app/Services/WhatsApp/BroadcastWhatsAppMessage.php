<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BroadcastWhatsAppMessage
{
    protected SendWhatsAppMessage $sendService;

    public function __construct(SendWhatsAppMessage $sendService)
    {
        $this->sendService = $sendService;
    }

    /**
     * Get students by criteria
     */
    public function getStudentsByCriteria(?int $courseId = null, ?int $groupId = null): Collection
    {
        $query = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_active', true);

        // Filter students only (if student role exists)
        $hasStudentRole = \Spatie\Permission\Models\Role::where('name', 'student')->exists();
        if ($hasStudentRole) {
            try {
                $query->role('student');
            } catch (\Exception $e) {
                Log::warning('Error filtering by student role: ' . $e->getMessage());
            }
        }

        // Filter by valid phone format (E.164 format)
        return $query->get()->filter(function ($user) {
            return preg_match('/^\+[1-9][0-9]{1,14}$/', $user->phone);
        })->values();
    }

    /**
     * Replace placeholders in message template
     */
    public function replacePlaceholders(
        string $template,
        User $student,
        $course = null,
        $group = null
    ): string {
        $replacements = [
            '{student_name}' => $student->name,
            '{student_email}' => $student->email ?? '',
            '{course_name}' => '', // Default empty
            '{group_name}' => '', // Default empty
        ];

        // Course and group placeholders are kept for compatibility but will be empty
        // They can be populated in the future if course/group models are added

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}

