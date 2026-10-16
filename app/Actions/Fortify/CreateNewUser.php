<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $user = User::create([
            'email'    => $input['email'],
            'password' => $input['password'],
        ]);

        // `role` no está en $fillable: se asigna de forma explícita.
        $user->role = UserRole::Student;
        $user->save();

        $user->profile()->create([
            'first_name' => $input['first_name'],
            'last_name'  => $input['last_name'],
        ]);

        return $user;
    }
}
