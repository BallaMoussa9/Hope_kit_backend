<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeAccountMail;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Les comptes créés publiquement ne peuvent jamais choisir un rôle
     * privilégié. Ils commencent comme agent de santé et pourront être
     * affectés à un centre/projet par la direction.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'matricule' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'matricule'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'matricule' => $input['matricule'],
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => 'unassigned',
            'is_active' => true,
            'preferred_language' => 'fr',
        ]);

        if ($user->email) { Mail::to($user->email)->send(new WelcomeAccountMail($user)); }
        return $user;
    }
}
