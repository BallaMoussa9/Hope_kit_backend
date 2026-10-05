<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->load(['healthCenter', 'project', 'projects']);

        return response()->json([
            'id' => $user->id,
            'matricule' => $user->matricule,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'health_center' => $user->healthCenter,
            'project' => $user->project,
            'projects' => $user->projects,
            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : collect([$user->role]),
            'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->values() : collect(),
        ]);
    }
}
