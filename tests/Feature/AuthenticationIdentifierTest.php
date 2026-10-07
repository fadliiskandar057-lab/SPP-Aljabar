<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_email_address(): void
    {
        $user = User::create([
            'name' => 'Admin Email',
            'username' => 'admin-email',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin_tu',
        ]);

        $this->post(route('login.post'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_can_be_requested_with_username(): void
    {
        $user = User::create([
            'name' => 'Admin Reset',
            'username' => 'admin-reset',
            'email' => 'reset@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin_tu',
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email]);

        $this->post(route('password.email'), ['login' => $user->username])
            ->assertSessionHas('success');
    }

    public function test_password_reset_explains_when_username_has_no_email(): void
    {
        $user = User::create([
            'name' => 'Admin Without Email',
            'username' => 'admin-without-email',
            'password' => Hash::make('password'),
            'role' => 'admin_tu',
        ]);

        Password::shouldReceive('sendResetLink')->never();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['login' => $user->username])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('login');
    }
}
