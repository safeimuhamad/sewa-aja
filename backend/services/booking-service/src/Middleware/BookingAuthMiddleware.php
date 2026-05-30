<?php

namespace App\BookingService\Middleware;

use PDO;
use Shared\Http\Request;
use Shared\Http\Response;
use Shared\Support\Jwt;

class BookingAuthMiddleware
{
    public function __construct(private PDO $db)
    {
    }

    public function requireCustomer(): ?array
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::error('Silakan login untuk checkout.', 401);
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

        $user = $this->findUser($payload['sub']);

        if (!$user || $user['status'] !== 'active') {
            Response::error('Akun tidak aktif atau tidak ditemukan.', 401);
            return null;
        }

        if ($user['role'] !== 'customer') {
            Response::error('Checkout hanya tersedia untuk akun customer.', 403);
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

    private function findUser(string $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user ?: null;
    }
}
