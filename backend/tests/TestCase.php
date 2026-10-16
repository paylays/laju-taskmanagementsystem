<?php

namespace Tests;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tokenFor(User $user): string
    {
        return app(JwtService::class)->issue($user)['token'];
    }

    protected function actingAsJwt(User $user): static
    {
        // The app instance is shared across requests in a test; drop the user the guard cached last time.
        $this->app['auth']->forgetGuards();

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$this->tokenFor($user),
            'Accept' => 'application/json',
        ]);
    }
}
