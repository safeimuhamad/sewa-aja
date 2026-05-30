<?php

namespace App\AuthService\Repositories;

use PDO;
use Shared\Support\Uuid;

class VendorRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();

        $statement = $this->db->prepare(
            'INSERT INTO vendors (id, user_id, store_name, slug, description, address, city, province, postal_code, status)
             VALUES (:id, :user_id, :store_name, :slug, :description, :address, :city, :province, :postal_code, :status)'
        );
        $statement->execute([
            'id' => $id,
            'user_id' => $data['user_id'],
            'store_name' => $data['store_name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        return $this->findByUserId($data['user_id']);
    }

    public function findByUserId(string $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM vendors WHERE user_id = :user_id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $vendor = $statement->fetch();

        return $vendor ?: null;
    }
}
