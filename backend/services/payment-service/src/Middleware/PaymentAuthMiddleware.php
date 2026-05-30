<?php

namespace App\PaymentService\Middleware;

use PDO;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Support\Jwt;

class PaymentAuthMiddleware
{
    public function __construct(private PDO $db)
    {
    }

    public function requireCustomer(): ?array
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::error('Silakan login untuk melanjutkan pembayaran.', 401);
            return null;
        }

        $payload = Jwt::decode($token, getenv('JWT_SECRET') ?: 'change-this-to-a-long-random-secret');

        if (!$payload || empty($payload['sub']) || empty($payload['jti'])) {
            Response::error('Token tidak valid atau kedaluwarsa.', 401);
            return null;
        }

        if (!$this->isTokenActive($payload['jti'])) {
            Response::error('Token sudah tidak aktif.', 401);
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $payload['sub']]);
        $user = $statement->fetch();

        if (!$user || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            Response::error('Pembayaran hanya tersedia untuk customer aktif.', 403);
            return null;
        }

        return $user;
    }

    private function isTokenActive(string $tokenId): bool
    {
        $statement = $this->db->prepare(
            'SELECT id FROM auth_tokens
             WHERE token_id = :token_id
             AND revoked_at IS NULL
             AND deleted_at IS NULL
             AND expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute(['token_id' => $tokenId]);

        return (bool) $statement->fetch();
    }
}
