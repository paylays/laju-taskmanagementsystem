<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_bearer_token_and_the_user(): void
    {
        $user = User::factory()->create(['email' => 'rina@lajur.test']);

        $this->postJson('/api/auth/login', ['email' => 'rina@lajur.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['access_token', 'expires_in']);
    }

    public function test_wrong_password_is_rejected_with_a_generic_message(): void
    {
        User::factory()->create(['email' => 'rina@lajur.test']);

        $this->postJson('/api/auth/login', ['email' => 'rina@lajur.test', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email or password is incorrect.');

        $this->postJson('/api/auth/login', ['email' => 'nobody@lajur.test', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email or password is incorrect.');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer garbage'])->assertUnauthorized();
    }

    public function test_me_returns_the_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $headers = ['Authorization' => 'Bearer '.$this->tokenFor($user)];

        $this->postJson('/api/auth/logout', [], $headers)->assertNoContent();

        // Forget the user resolved for the previous request so the guard re-reads the token.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/auth/me', $headers)->assertUnauthorized();
    }
}
