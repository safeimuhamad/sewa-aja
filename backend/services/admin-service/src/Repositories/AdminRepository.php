<?php

namespace App\AdminService\Repositories;

use PDO;
use Shared\Support\Uuid;

class AdminRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function dashboard(): array
    {
        return [
            'widgets' => [
                'users' => $this->count('users'),
                'categories' => $this->count('categories'),
                'locations' => $this->count('service_locations'),
                'vendors_pending' => $this->count('vendors', "status = 'pending'"),
                'products_active' => $this->count('products', "status = 'active'"),
                'bookings_active' => $this->count('bookings', "status IN ('pending', 'confirmed', 'ongoing')"),
                'payments_pending' => $this->count('payments', "status = 'pending'"),
                'revenue_paid' => $this->money("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid' AND deleted_at IS NULL"),
            ],
            'booking_status' => $this->groupCounts('bookings', 'status'),
            'payment_status' => $this->groupCounts('payments', 'status'),
            'recent_bookings' => array_slice($this->bookings(['per_page' => 5])['items'], 0, 5),
            'recent_payments' => array_slice($this->payments(['per_page' => 5])['items'], 0, 5),
        ];
    }

    public function users(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(name LIKE :q OR email LIKE :q OR phone LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['role'])) {
            $where[] = 'role = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM users WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT id, name, email, phone, role, status, email_verified_at, created_at
             FROM users
             WHERE {$whereSql}
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function vendors(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(v.store_name LIKE :q OR u.email LIKE :q OR v.city LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM vendors v INNER JOIN users u ON u.id = v.user_id WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT v.id, v.store_name, v.slug, v.city, v.province, v.status, v.created_at, u.name AS owner_name, u.email AS owner_email
             FROM vendors v
             INNER JOIN users u ON u.id = v.user_id
             WHERE {$whereSql}
             ORDER BY v.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function products(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE :q OR v.store_name LIKE :q OR c.name LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total(
            "SELECT COUNT(*) FROM products p INNER JOIN vendors v ON v.id = p.vendor_id INNER JOIN categories c ON c.id = p.category_id WHERE {$whereSql}",
            $params
        );
        $statement = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price_per_day, p.stock_quantity, p.unit_label, p.status, p.created_at,
                    v.store_name, c.name AS category_name
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             INNER JOIN categories c ON c.id = p.category_id
             WHERE {$whereSql}
             ORDER BY p.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function bookings(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['b.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(b.booking_code LIKE :q OR u.name LIKE :q OR v.store_name LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'b.status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM bookings b INNER JOIN users u ON u.id = b.customer_id INNER JOIN vendors v ON v.id = b.vendor_id WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT b.id, b.booking_code, b.status, b.start_date, b.end_date, b.total_amount, b.created_at,
                    u.name AS customer_name, v.store_name
             FROM bookings b
             INNER JOIN users u ON u.id = b.customer_id
             INNER JOIN vendors v ON v.id = b.vendor_id
             WHERE {$whereSql}
             ORDER BY b.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function payments(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(p.payment_code LIKE :q OR b.booking_code LIKE :q OR p.transaction_reference LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM payments p INNER JOIN bookings b ON b.id = p.booking_id WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT p.id, p.payment_code, p.method, p.status, p.amount, p.paid_at, p.transaction_reference, p.created_at,
                    b.booking_code
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE {$whereSql}
             ORDER BY p.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function reports(array $filters): array
    {
        $start = $filters['start_date'] ?? date('Y-m-01');
        $end = $filters['end_date'] ?? date('Y-m-d');
        $params = ['start_date' => $start, 'end_date' => $end];

        return [
            'range' => $params,
            'summary' => [
                'gross_revenue' => $this->money(
                    "SELECT COALESCE(SUM(amount), 0)
                     FROM payments
                     WHERE status = 'paid'
                     AND DATE(COALESCE(paid_at, created_at)) BETWEEN :start_date AND :end_date
                     AND deleted_at IS NULL",
                    $params
                ),
                'bookings' => $this->scalar(
                    "SELECT COUNT(*)
                     FROM bookings
                     WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                     AND deleted_at IS NULL",
                    $params
                ),
                'new_users' => $this->scalar(
                    "SELECT COUNT(*)
                     FROM users
                     WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                     AND deleted_at IS NULL",
                    $params
                ),
                'paid_payments' => $this->scalar(
                    "SELECT COUNT(*)
                     FROM payments
                     WHERE status = 'paid'
                     AND DATE(COALESCE(paid_at, created_at)) BETWEEN :start_date AND :end_date
                     AND deleted_at IS NULL",
                    $params
                ),
            ],
            'revenue_by_day' => $this->rows(
                "SELECT DATE(COALESCE(paid_at, created_at)) AS label, COALESCE(SUM(amount), 0) AS total
                 FROM payments
                 WHERE status = 'paid'
                 AND DATE(COALESCE(paid_at, created_at)) BETWEEN :start_date AND :end_date
                 AND deleted_at IS NULL
                 GROUP BY DATE(COALESCE(paid_at, created_at))
                 ORDER BY label ASC",
                $params
            ),
            'bookings_by_status' => $this->groupCounts('bookings', 'status'),
            'payment_analytics' => $this->groupCounts('payments', 'status'),
            'vendor_performance' => $this->rows(
                "SELECT v.store_name AS label, COUNT(b.id) AS bookings, COALESCE(SUM(p.amount), 0) AS revenue
                 FROM vendors v
                 LEFT JOIN bookings b ON b.vendor_id = v.id AND b.deleted_at IS NULL
                 LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'paid' AND p.deleted_at IS NULL
                 WHERE v.deleted_at IS NULL
                 GROUP BY v.id, v.store_name
                 ORDER BY revenue DESC, bookings DESC
                 LIMIT 10"
            ),
            'product_statistics' => $this->rows(
                "SELECT p.name AS label, COALESCE(SUM(bi.quantity), 0) AS rented_quantity, COUNT(DISTINCT bi.booking_id) AS bookings
                 FROM products p
                 LEFT JOIN booking_items bi ON bi.product_id = p.id AND bi.deleted_at IS NULL
                 WHERE p.deleted_at IS NULL
                 GROUP BY p.id, p.name
                 ORDER BY rented_quantity DESC
                 LIMIT 10"
            ),
            'user_growth' => $this->rows(
                "SELECT DATE(created_at) AS label, COUNT(*) AS total
                 FROM users
                 WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                 AND deleted_at IS NULL
                 GROUP BY DATE(created_at)
                 ORDER BY label ASC",
                $params
            ),
        ];
    }

    public function reviews(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['r.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(u.name LIKE :q OR p.name LIKE :q OR v.store_name LIKE :q OR r.comment LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total(
            "SELECT COUNT(*)
             FROM reviews r
             INNER JOIN users u ON u.id = r.customer_id
             LEFT JOIN products p ON p.id = r.product_id
             INNER JOIN vendors v ON v.id = r.vendor_id
             WHERE {$whereSql}",
            $params
        );

        $statement = $this->db->prepare(
            "SELECT r.id, r.rating, r.review_type, r.comment, r.status, r.created_at,
                    u.name AS customer_name, p.name AS product_name, v.store_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.customer_id
             LEFT JOIN products p ON p.id = r.product_id
             INNER JOIN vendors v ON v.id = r.vendor_id
             WHERE {$whereSql}
             ORDER BY r.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function categories(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(name LIKE :q OR slug LIKE :q OR description LIKE :q OR icon_key LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = in_array((string) $filters['status'], ['1', 'active'], true) ? 1 : 0;
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM categories WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT id, name, slug, description, icon_key, is_active, sort_order, created_at
             FROM categories
             WHERE {$whereSql}
             ORDER BY sort_order ASC, name ASC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function saveCategory(array $data, ?string $id = null): array
    {
        $id = $id ?: Uuid::v4();
        $name = trim((string) $data['name']);
        $slug = trim((string) ($data['slug'] ?? '')) ?: $this->slug($name);
        $payload = [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'icon_key' => trim((string) ($data['icon_key'] ?? '')) ?: 'box',
            'is_active' => !array_key_exists('is_active', $data) || (bool) $data['is_active'] ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];

        if ($this->exists('categories', $id)) {
            $statement = $this->db->prepare(
                'UPDATE categories
                 SET name = :name, slug = :slug, description = :description, icon_key = :icon_key, is_active = :is_active, sort_order = :sort_order
                 WHERE id = :id AND deleted_at IS NULL'
            );
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO categories (id, name, slug, description, icon_key, is_active, sort_order)
                 VALUES (:id, :name, :slug, :description, :icon_key, :is_active, :sort_order)'
            );
        }

        $statement->execute($payload);

        return $this->findById('categories', $id);
    }

    public function deleteCategory(string $id): bool
    {
        $statement = $this->db->prepare('UPDATE categories SET is_active = 0, deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function locations(array $filters): array
    {
        [$pagination, $limit, $offset] = $this->pagination($filters);
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(name LIKE :q OR city LIKE :q OR province LIKE :q OR region_code LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['province'])) {
            $where[] = 'province = :province';
            $params['province'] = $filters['province'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = in_array((string) $filters['status'], ['1', 'active'], true) ? 1 : 0;
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM service_locations WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT id, region_code, name, city, type, province, slug, is_active, created_at
             FROM service_locations
             WHERE {$whereSql}
             ORDER BY province ASC, city ASC
             LIMIT :limit OFFSET :offset"
        );
        $this->bindList($statement, $params, $limit, $offset);

        return ['items' => $statement->fetchAll(), 'meta' => $this->meta($pagination, $total)];
    }

    public function saveLocation(array $data, ?string $id = null): array
    {
        $id = $id ?: Uuid::v4();
        $city = trim((string) $data['city']);
        $type = (string) ($data['type'] ?? 'Kota');
        $province = trim((string) $data['province']);
        $name = trim((string) ($data['name'] ?? '')) ?: "{$type} {$city}";
        $slug = trim((string) ($data['slug'] ?? '')) ?: $this->slug("{$name} {$province}");
        $payload = [
            'id' => $id,
            'region_code' => trim((string) ($data['region_code'] ?? '')) ?: 'custom-' . substr($id, 0, 8),
            'name' => $name,
            'city' => $city,
            'type' => $type,
            'province' => $province,
            'slug' => $slug,
            'is_active' => !array_key_exists('is_active', $data) || (bool) $data['is_active'] ? 1 : 0,
        ];

        if ($this->exists('service_locations', $id)) {
            $statement = $this->db->prepare(
                'UPDATE service_locations
                 SET region_code = :region_code, name = :name, city = :city, type = :type, province = :province, slug = :slug, is_active = :is_active
                 WHERE id = :id AND deleted_at IS NULL'
            );
        } else {
            $statement = $this->db->prepare(
                'INSERT INTO service_locations (id, region_code, name, city, type, province, slug, is_active)
                 VALUES (:id, :region_code, :name, :city, :type, :province, :slug, :is_active)'
            );
        }

        $statement->execute($payload);

        return $this->findById('service_locations', $id);
    }

    public function deleteLocation(string $id): bool
    {
        $statement = $this->db->prepare('UPDATE service_locations SET is_active = 0, deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function updateStatus(string $table, string $id, string $status): bool
    {
        $allowed = [
            'users' => ['active', 'inactive', 'suspended'],
            'vendors' => ['pending', 'active', 'suspended'],
            'products' => ['draft', 'active', 'inactive'],
            'bookings' => ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'],
            'payments' => ['pending', 'paid', 'failed', 'refunded', 'expired'],
            'reviews' => ['pending', 'approved', 'rejected'],
        ];

        if (!isset($allowed[$table]) || !in_array($status, $allowed[$table], true)) {
            return false;
        }

        $statement = $this->db->prepare("UPDATE {$table} SET status = :status WHERE id = :id AND deleted_at IS NULL");
        $statement->execute(['status' => $status, 'id' => $id]);

        return $statement->rowCount() > 0;
    }

    private function count(string $table, ?string $condition = null): int
    {
        $where = $condition ? "deleted_at IS NULL AND {$condition}" : 'deleted_at IS NULL';

        return (int) $this->db->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
    }

    private function exists(string $table, string $id): bool
    {
        $statement = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE id = :id AND deleted_at IS NULL");
        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function findById(string $table, string $id): array
    {
        $statement = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: [];
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));

        return $slug ?: 'item';
    }

    private function groupCounts(string $table, string $column): array
    {
        $statement = $this->db->query("SELECT {$column} AS label, COUNT(*) AS total FROM {$table} WHERE deleted_at IS NULL GROUP BY {$column}");

        return $statement->fetchAll();
    }

    private function money(string $sql, array $params = []): float
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (float) $statement->fetchColumn();
    }

    private function scalar(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function rows(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function pagination(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(50, max(5, (int) ($filters['per_page'] ?? 10)));

        return [['page' => $page, 'per_page' => $perPage], $perPage, ($page - 1) * $perPage];
    }

    private function total(string $sql, array $params): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function bindList(\PDOStatement $statement, array $params, int $limit, int $offset): void
    {
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . ltrim((string) $key, ':'), $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
    }

    private function meta(array $pagination, int $total): array
    {
        return [
            ...$pagination,
            'total' => $total,
            'total_pages' => (int) ceil($total / $pagination['per_page']),
        ];
    }
}
