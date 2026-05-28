<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;

class TemplateRendererService
{
    /**
     * @param  array<string, scalar|null>  $context
     */
    public function render(string $template, array $context): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($context) {
            $key = $matches[1];
            if (! array_key_exists($key, $context)) {
                Log::warning('Mail template variable not found.', ['variable' => $key]);

                return '';
            }

            $value = $context[$key];

            return $value === null ? '' : (string) $value;
        }, $template);
    }
}
