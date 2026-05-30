<?php

namespace App\AuthService\Repositories;

use PDO;
use Shared\Support\Uuid;

class PasswordResetRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(string $userId, string $email, string $plainToken): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO password_reset_tokens (id, user_id, email, token_hash, expires_at)
             VALUES (:id, :user_id, :email, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $statement->execute([
            'id' => Uuid::v4(),
            'user_id' => $userId,
            'email' => strtolower($email),
            'token_hash' => password_hash($plainToken, PASSWORD_DEFAULT),
        ]);
    }
}
