<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // el registro crea el usuario Y su perfil (relación 1:1)
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('profiles', [
            'first_name' => 'Test',
            'last_name'  => 'User',
        ]);
    }

    public function test_registration_requires_first_and_last_name()
    {
        $this->post(route('register.store'), $this->validRegistrationData([
            'first_name' => '',
            'last_name'  => '',
        ]))
            ->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_a_valid_email()
    {
        $this->post(route('register.store'), $this->validRegistrationData([
            'email' => 'no-es-un-email',
        ]))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_password_confirmation()
    {
        $this->post(route('register.store'), $this->validRegistrationData([
            'password_confirmation' => 'distinta',
        ]))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_a_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('register.store'), $this->validRegistrationData())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_new_users_get_the_student_role()
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role'  => 'student',
        ]);
    }

    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }
}
