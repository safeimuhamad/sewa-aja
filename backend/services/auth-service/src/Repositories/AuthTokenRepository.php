<?php

namespace App\AuthService\Repositories;

use PDO;
use Shared\Support\Uuid;

class AuthTokenRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(string $userId, string $tokenId, int $expiresAt): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO auth_tokens (id, user_id, token_id, expires_at)
             VALUES (:id, :user_id, :token_id, FROM_UNIXTIME(:expires_at))'
        );
        $statement->execute([
            'id' => Uuid::v4(),
            'user_id' => $userId,
            'token_id' => $tokenId,
            'expires_at' => $expiresAt,
        ]);
    }

    public function isActive(string $tokenId): bool
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

    public function revoke(string $tokenId): void
    {
        $statement = $this->db->prepare(
            'UPDATE auth_tokens SET revoked_at = NOW() WHERE token_id = :token_id AND revoked_at IS NULL'
        );
        $statement->execute(['token_id' => $tokenId]);
    }
}
