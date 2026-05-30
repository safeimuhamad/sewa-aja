<?php

namespace Shared\Http;

use PDO;
use Shared\Support\Jwt;

class AuthGuard
{
    public function __construct(private PDO $db)
    {
    }

    public function requireRole(array $roles): ?array
    {
        $token = Request::bearerToken();

        if (!$token) {
            Response::error('Token autentikasi wajib dikirim.', 401);
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

        if (!in_array($user['role'], $roles, true)) {
            Response::error('Anda tidak memiliki akses untuk aksi ini.', 403);
            return null;
        }

        return $user;
    }

    public function vendorForUser(string $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM vendors WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $vendor = $statement->fetch();

        return $vendor ?: null;
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
