<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class JwtService
{
    private const ALGO = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlMinutes,
        private readonly string $issuer,
    ) {}

    /**
     * @return array{token: string, expires_in: int}
     */
    public function issue(User $user): array
    {
        $now = time();
        $ttl = $this->ttlMinutes * 60;

        $token = JWT::encode([
            'iss' => $this->issuer,
            'sub' => (string) $user->id,
            'jti' => (string) Str::uuid(),
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + $ttl,
        ], $this->secret, self::ALGO);

        return ['token' => $token, 'expires_in' => $ttl];
    }

    /**
     * Returns the claims, or null when the token is malformed, expired, tampered with, or revoked.
     */
    public function decode(string $token): ?stdClass
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret, self::ALGO));
        } catch (\Throwable) {
            return null;
        }

        if (($claims->iss ?? null) !== $this->issuer || empty($claims->jti) || $this->isRevoked($claims->jti)) {
            return null;
        }

        return $claims;
    }

    public function revoke(stdClass $claims): void
    {
        DB::table('revoked_tokens')->insertOrIgnore([
            'jti' => $claims->jti,
            'expires_at' => date('Y-m-d H:i:s', $claims->exp),
        ]);
    }

    private function isRevoked(string $jti): bool
    {
        return DB::table('revoked_tokens')->where('jti', $jti)->exists();
    }
}
