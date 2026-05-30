<?php

namespace App\AuthService\Repositories;

use PDO;
use Shared\Support\Uuid;

class UserRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();

        $statement = $this->db->prepare(
            'INSERT INTO users (id, name, email, password_hash, phone, role, status)
             VALUES (:id, :name, :email, :password_hash, :phone, :role, :status)'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password_hash' => $data['password_hash'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->findById($id);
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['email' => strtolower($email)]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(string $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function updateProfile(string $id, array $data): ?array
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 phone = :phone
             WHERE id = :id
             AND deleted_at IS NULL'
        );
        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return $this->findById($id);
    }

    public function publicUser(array $user): array
    {
        unset($user['password_hash']);

        return $user;
    }
}
