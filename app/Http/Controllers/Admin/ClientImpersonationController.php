<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ClientImpersonationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ClientImpersonationController extends Controller
{
    public function __construct(protected ClientImpersonationService $impersonation)
    {
        $this->middleware('auth');
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();

        if (! $admin || ! $admin->isAdminPanelUser()) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        try {
            $payload = $this->impersonation->createLink($user, $admin);

            return response()->json($payload);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
