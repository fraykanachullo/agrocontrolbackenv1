<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
    ) {}

    public function register(array $data): array
    {
        $user = $this->userRepo->create([
            'name' => $data['nombre_completo'],
            'nombre_completo' => $data['nombre_completo'],
            'email' => $data['correo_electronico'],
            'password' => Hash::make($data['password']),
            'plan_suscripcion' => 'gratuito',
            'rol' => 'propietario',
        ]);

        $user->update(['tenant_id' => $user->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            return ['error' => true];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function forgotPassword(string $email): string
    {
        return Password::broker()->sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $password): string
    {
        return Password::broker()->reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );
    }
}
