<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    /**
     * Redirige al usuario a Google para autenticarse.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Recibe la respuesta de Google y resuelve el login o el registro.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::findByGoogle($googleUser->getId());

        if (! $user) {
            $user = User::findByEmail($googleUser->getEmail());

            if ($user) {
                // El email ya existe: vinculamos la cuenta de Google y refrescamos el avatar.
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);

                if ($user->profile) {
                    $user->profile->update([
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                $nameParts = $this->splitName($googleUser->getName());

                $user = User::create([
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => Hash::make(Str::random(24)),
                ]);

                $user->profile()->create([
                    'first_name' => $nameParts['first'],
                    'last_name'  => $nameParts['last'],
                    'avatar'     => $googleUser->getAvatar(),
                ]);

                // `role` y `email_verified_at` no están en $fillable: se asignan manualmente.
                $user->role              = UserRole::Student;
                $user->email_verified_at = now();
                $user->save();
            }
        }

        Auth::login($user);

        return redirect()->intended(route($user->role->dashboardRoute(), absolute: false));
    }

    /**
     * @return array{first: string, last: string}
     */
    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [
            'first' => $parts[0],
            'last'  => $parts[1] ?? '',
        ];
    }
}
