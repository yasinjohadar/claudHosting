<?php

namespace App\Services\Coolify;

class TerminalCommandPolicy
{
    /**
     * @return array{allowed: bool, message?: string}
     */
    public function checkLine(string $line): array
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return ['allowed' => true];
        }

        foreach (config('terminal_commands.blocked_patterns', []) as $pattern) {
            if (@preg_match($pattern, $trimmed) === 1) {
                return ['allowed' => false, 'message' => 'أمر محظور لأسباب أمنية'];
            }
        }

        return ['allowed' => true];
    }
}
