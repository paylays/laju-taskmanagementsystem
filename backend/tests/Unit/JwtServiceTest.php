<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\JwtService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-that-is-long-enough-for-hs256';

    private function service(int $ttl = 60): JwtService
    {
        return new JwtService(self::SECRET, $ttl, 'http://lajur.test');
    }

    public function test_issued_token_decodes_to_the_user(): void
    {
        $user = User::factory()->admin()->create();

        $issued = $this->service()->issue($user);
        $claims = $this->service()->decode($issued['token']);

        $this->assertSame((string) $user->id, $claims->sub);
        $this->assertSame('admin', $claims->role);
        $this->assertSame(3600, $issued['expires_in']);
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = JWT::encode([
            'iss' => 'http://lajur.test', 'sub' => '1', 'jti' => 'x',
            'iat' => time() - 7200, 'exp' => time() - 3600,
        ], self::SECRET, 'HS256');

        $this->assertNull($this->service()->decode($token));
    }

    public function test_token_signed_with_another_key_is_rejected(): void
    {
        $token = JWT::encode([
            'iss' => 'http://lajur.test', 'sub' => '1', 'jti' => 'x', 'exp' => time() + 60,
        ], 'some-other-secret-that-is-also-long-enough', 'HS256');

        $this->assertNull($this->service()->decode($token));
    }

    public function test_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->service()->issue($user)['token'];

        $this->service()->revoke($this->service()->decode($token));

        $this->assertNull($this->service()->decode($token));
    }

    public function test_garbage_is_rejected(): void
    {
        $this->assertNull($this->service()->decode('not.a.jwt'));
    }
}
