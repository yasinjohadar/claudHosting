<?php

namespace App\Http\Controllers\Admin\Coolify\Concerns;

use Illuminate\Http\RedirectResponse;

trait HandlesCoolifyResponses
{
    use LogsCoolifyActivity;
    protected function coolifyRedirectError(string $message, string $route = 'admin.coolify.settings.index', array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('error', $message);
    }

    protected function coolifyRedirectSuccess(string $message, string $route, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('success', $message);
    }

    protected function validatedReturnUrl(): ?string
    {
        $return = request()->input('_return');
        if (! is_string($return) || $return === '') {
            return null;
        }

        $returnPath = parse_url($return, PHP_URL_PATH);
        $appPath = parse_url(url('/'), PHP_URL_PATH) ?: '';

        if ($returnPath === null || ! str_starts_with($returnPath, $appPath !== '' ? $appPath : '/')) {
            return null;
        }

        return $return;
    }

    protected function redirectAfterResourceDestroy(
        bool $success,
        string $message,
        string $errorRoute,
        array $errorParams,
        string $defaultSuccessRoute,
        array $defaultSuccessParams = []
    ): RedirectResponse {
        $return = $this->validatedReturnUrl();

        if (! $success) {
            if ($return !== null) {
                return redirect()->to($return)->with('error', $message);
            }

            return redirect()->route($errorRoute, $errorParams)->with('error', $message);
        }

        if ($return !== null) {
            return redirect()->to($return)->with('success', $message);
        }

        return redirect()->route($defaultSuccessRoute, $defaultSuccessParams)->with('success', $message);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function coolifyList(array $response): array
    {
        if (! ($response['success'] ?? false)) {
            return [];
        }

        return app(\App\Services\CoolifyApiService::class)->normalizeList($response['data'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function coolifyItem(array $response): ?array
    {
        if (! ($response['success'] ?? false)) {
            return null;
        }

        $data = $response['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    protected function resourceUuid(array $item): string
    {
        return (string) ($item['uuid'] ?? $item['id'] ?? '');
    }
}
