<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role'  => UserRole::Admin->value,
        ]);

        $admin->profile()->create(
            Profile::factory()->make([
                'first_name' => 'Admin',
                'last_name'  => 'User',
            ])->toArray()
        );

        $teacher = User::factory()->create([
            'email' => 'teacher@example.com',
            'role'  => UserRole::Teacher->value,
        ]);

        $teacher->profile()->create(
            Profile::factory()->make([
                'first_name' => 'Teacher',
                'last_name'  => 'User',
            ])->toArray()
        );

        User::factory()
            ->count(10)
            ->create()
            ->each(function (User $user) {
                Profile::factory()->create([
                    'user_id' => $user->id,
                ]);
            });
    }
}
