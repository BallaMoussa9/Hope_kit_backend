<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Connexion agent depuis l'app mobile — retourne un token Sanctum
     * (et non une session, contrairement au dashboard web via Fortify).
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez votre coordinateur.',
            ], 403);
        }

        // Un token par appareil — permet de révoquer l'accès d'un téléphone
        // précis sans déconnecter les autres appareils de l'agent.
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
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

    /**
     * Déconnecte uniquement l'appareil courant (révoque le token utilisé
     * pour cette requête) — important pour le mode hors-ligne : un agent
     * peut rester connecté sur son téléphone même si le token d'un autre
     * appareil est révoqué à distance.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès.',
        ]);
    }

    /**
     * Révoque tous les tokens de l'utilisateur (tous appareils) — utile
     * si un téléphone est perdu ou volé.
     */
    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnecté de tous les appareils.',
        ]);
    }
}
