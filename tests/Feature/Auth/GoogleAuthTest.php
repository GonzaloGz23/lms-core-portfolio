<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_sends_the_user_to_google()
    {
        Socialite::fake('google');

        $this->get(route('auth.google'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_callback_logs_in_an_existing_user_by_google_id()
    {
        $user = User::factory()->create(['google_id' => '123456789']);
        Socialite::fake('google', GoogleUser::fake());

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_callback_links_google_account_to_an_existing_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user->profile()->create([
            'first_name' => 'Gonzalo',
            'last_name'  => 'Gomez',
            'avatar'     => 'https://old-avatar.com/x.jpg',
        ]);
        Socialite::fake('google', GoogleUser::fake(['id' => 'google-id-999']));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-id-999', $user->fresh()->google_id);
        $this->assertSame('https://example.com/avatar.jpg', $user->fresh()->profile->avatar);
    }

    public function test_callback_creates_a_new_user_with_profile()
    {
        Socialite::fake('google', GoogleUser::fake([
            'id'   => '123456789',
            'name' => 'Gonzalo Gomez',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email'     => 'test@example.com',
            'google_id' => '123456789',
            'role'      => 'student',
        ]);
        $this->assertDatabaseHas('profiles', [
            'first_name' => 'Gonzalo',
            'last_name'  => 'Gomez',
            'avatar'     => 'https://example.com/avatar.jpg',
        ]);
        $this->assertNotNull(
            User::where('email', 'test@example.com')->first()->email_verified_at
        );
    }

    public function test_callback_redirects_an_existing_admin_to_the_admin_dashboard()
    {
        $admin = User::factory()->admin()->create(['google_id' => '123456789']);
        Socialite::fake('google', GoogleUser::fake());

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_callback_does_not_invent_a_last_name_when_the_google_name_has_none()
    {
        Socialite::fake('google', GoogleUser::fake(['name' => 'Carlos']));

        $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('profiles', [
            'first_name' => 'Carlos',
            'last_name'  => '',
        ]);
        $this->assertSame(
            'Carlos',
            User::where('email', 'test@example.com')->first()->profile->fullName()
        );
    }
}
