<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Connecté avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'health_center_id' => $user->health_center_id,
                'project_id' => $user->project_id,
            ],
        ]);
    }
}
