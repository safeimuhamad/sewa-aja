<?php

namespace App\ProductService\Repositories;

use PDO;
use Shared\Support\Uuid;

class ProductRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(24, max(6, (int) ($filters['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildWhere($filters);
        $orderBy = $this->orderBy($filters['sort'] ?? 'newest');

        $countSql = "SELECT COUNT(*) FROM products p
            INNER JOIN vendors v ON v.id = p.vendor_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE {$where}";
        $count = $this->db->prepare($countSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT
                p.id,
                p.name,
                p.slug,
                p.description,
                p.price_per_day,
                p.deposit_amount,
                p.stock_quantity,
                p.unit_label,
                p.status,
                p.created_at,
                v.id AS vendor_id,
                v.store_name,
                v.city,
                v.province,
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                c.icon_key AS category_icon_key,
                (
                    SELECT pi.image_url
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    AND pi.deleted_at IS NULL
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS primary_image
            FROM products p
            INNER JOIN vendors v ON v.id = p.vendor_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset";

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function categories(): array
    {
        $statement = $this->db->query(
            "SELECT id, name, slug, description, icon_key
             FROM categories
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order ASC, name ASC"
        );

        return $statement->fetchAll();
    }

    public function findDetail(string $identifier): ?array
    {
        $statement = $this->db->prepare(
            "SELECT
                p.*,
                v.store_name,
                v.slug AS vendor_slug,
                v.description AS vendor_description,
                v.address AS vendor_address,
                v.city,
                v.province,
                v.postal_code,
                c.name AS category_name,
                c.slug AS category_slug,
                c.icon_key AS category_icon_key
            FROM products p
            INNER JOIN vendors v ON v.id = p.vendor_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE (p.id = :identifier OR p.slug = :identifier)
            AND p.status = 'active'
            AND p.deleted_at IS NULL
            AND v.deleted_at IS NULL
            AND v.status = 'active'
            LIMIT 1"
        );
        $statement->execute(['identifier' => $identifier]);
        $product = $statement->fetch();

        if (!$product) {
            return null;
        }

        $product['images'] = $this->images($product['id']);
        $product['units'] = $this->units($product['id']);
        $product['related_products'] = $this->related($product['id'], $product['category_id']);

        return $product;
    }

    public function availability(string $identifier, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $product = $this->findDetail($identifier);

        if (!$product) {
            return null;
        }

        $start = new \DateTimeImmutable($startDate ?: date('Y-m-d'));
        $end = new \DateTimeImmutable($endDate ?: $start->modify('+29 days')->format('Y-m-d'));

        if ($end < $start) {
            $end = $start;
        }

        $bookedByDate = $this->bookedQuantities($product['id'], $start->format('Y-m-d'), $end->format('Y-m-d'));
        $blockedByDate = $this->blockedQuantities($product['id'], $start->format('Y-m-d'), $end->format('Y-m-d'));
        $days = [];

        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $dateKey = $date->format('Y-m-d');
            $booked = $bookedByDate[$dateKey] ?? 0;
            $blocked = $blockedByDate[$dateKey] ?? 0;
            $available = max(0, (int) $product['stock_quantity'] - $booked - $blocked);
            $days[] = [
                'date' => $dateKey,
                'booked_quantity' => $booked,
                'blocked_quantity' => $blocked,
                'available_quantity' => $available,
                'is_available' => $available > 0,
            ];
        }

        return [
            'product_id' => $product['id'],
            'stock_quantity' => (int) $product['stock_quantity'],
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $days,
        ];
    }

    public function locations(): array
    {
        $statement = $this->db->query(
            "SELECT city, province, name, type, slug
             FROM service_locations
             WHERE is_active = 1
             AND deleted_at IS NULL
             ORDER BY province ASC, city ASC"
        );

        return $statement->fetchAll();
    }

    public function vendorProducts(string $vendorId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                p.*,
                c.name AS category_name,
                c.icon_key AS category_icon_key,
                (
                    SELECT pi.image_url
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    AND pi.deleted_at IS NULL
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS primary_image
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.vendor_id = :vendor_id
             AND p.deleted_at IS NULL
             ORDER BY p.created_at DESC"
        );
        $statement->execute(['vendor_id' => $vendorId]);

        return $statement->fetchAll();
    }

    public function findVendorProduct(string $vendorId, string $productId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM products WHERE id = :id AND vendor_id = :vendor_id AND deleted_at IS NULL LIMIT 1');
        $statement->execute([
            'id' => $productId,
            'vendor_id' => $vendorId,
        ]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    public function createForVendor(string $vendorId, array $data): array
    {
        $id = Uuid::v4();
        $statement = $this->db->prepare(
            'INSERT INTO products
             (id, vendor_id, category_id, name, slug, description, price_per_day, deposit_amount, stock_quantity, unit_label, status)
             VALUES
             (:id, :vendor_id, :category_id, :name, :slug, :description, :price_per_day, :deposit_amount, :stock_quantity, :unit_label, :status)'
        );
        $statement->execute([
            'id' => $id,
            'vendor_id' => $vendorId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'],
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'unit_label' => $data['unit_label'] ?? 'unit',
            'status' => $data['status'] ?? 'draft',
        ]);

        return $this->findVendorProduct($vendorId, $id);
    }

    public function updateForVendor(string $vendorId, string $productId, array $data): ?array
    {
        if (!$this->findVendorProduct($vendorId, $productId)) {
            return null;
        }

        $statement = $this->db->prepare(
            'UPDATE products
             SET category_id = :category_id,
                 name = :name,
                 description = :description,
                 price_per_day = :price_per_day,
                 deposit_amount = :deposit_amount,
                 stock_quantity = :stock_quantity,
                 unit_label = :unit_label,
                 status = :status
             WHERE id = :id AND vendor_id = :vendor_id'
        );
        $statement->execute([
            'id' => $productId,
            'vendor_id' => $vendorId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_per_day' => $data['price_per_day'],
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'unit_label' => $data['unit_label'] ?? 'unit',
            'status' => $data['status'] ?? 'draft',
        ]);

        return $this->findVendorProduct($vendorId, $productId);
    }

    public function deleteForVendor(string $vendorId, string $productId): bool
    {
        $statement = $this->db->prepare('UPDATE products SET deleted_at = NOW() WHERE id = :id AND vendor_id = :vendor_id AND deleted_at IS NULL');
        $statement->execute([
            'id' => $productId,
            'vendor_id' => $vendorId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function addImage(string $vendorId, string $productId, array $data): ?array
    {
        if (!$this->findVendorProduct($vendorId, $productId)) {
            return null;
        }

        $id = Uuid::v4();
        $statement = $this->db->prepare(
            'INSERT INTO product_images
             (id, product_id, image_url, thumbnail_url, alt_text, mime_type, file_size, width, height, sort_order, is_primary)
             VALUES
             (:id, :product_id, :image_url, :thumbnail_url, :alt_text, :mime_type, :file_size, :width, :height, :sort_order, :is_primary)'
        );
        $statement->execute([
            'id' => $id,
            'product_id' => $productId,
            'image_url' => $data['image_url'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
        ]);

        if (!empty($data['is_primary'])) {
            $this->db->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id AND id <> :id')->execute([
                'product_id' => $productId,
                'id' => $id,
            ]);
        }

        return ['id' => $id, 'product_id' => $productId, ...$data];
    }

    public function updateInventory(string $vendorId, string $productId, array $data): ?array
    {
        $product = $this->findVendorProduct($vendorId, $productId);

        if (!$product) {
            return null;
        }

        $stock = max(0, (int) ($data['stock_quantity'] ?? $product['stock_quantity']));
        $this->db->prepare('UPDATE products SET stock_quantity = :stock_quantity WHERE id = :id AND vendor_id = :vendor_id')->execute([
            'stock_quantity' => $stock,
            'id' => $productId,
            'vendor_id' => $vendorId,
        ]);

        return $this->findVendorProduct($vendorId, $productId);
    }

    public function createAvailabilityBlock(string $vendorId, string $productId, string $userId, array $data): ?array
    {
        if (!$this->findVendorProduct($vendorId, $productId)) {
            return null;
        }

        $id = Uuid::v4();
        $statement = $this->db->prepare(
            'INSERT INTO product_availability_blocks
             (id, product_id, start_date, end_date, quantity_blocked, reason, created_by)
             VALUES
             (:id, :product_id, :start_date, :end_date, :quantity_blocked, :reason, :created_by)'
        );
        $statement->execute([
            'id' => $id,
            'product_id' => $productId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'quantity_blocked' => max(1, (int) ($data['quantity_blocked'] ?? 1)),
            'reason' => $data['reason'] ?? null,
            'created_by' => $userId,
        ]);

        return [
            'id' => $id,
            'product_id' => $productId,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'quantity_blocked' => max(1, (int) ($data['quantity_blocked'] ?? 1)),
            'reason' => $data['reason'] ?? null,
        ];
    }

    public function sortImages(string $vendorId, string $productId, array $images): ?array
    {
        if (!$this->findVendorProduct($vendorId, $productId)) {
            return null;
        }

        foreach ($images as $index => $image) {
            $this->db->prepare(
                'UPDATE product_images
                 SET sort_order = :sort_order, is_primary = :is_primary
                 WHERE id = :id AND product_id = :product_id AND deleted_at IS NULL'
            )->execute([
                'sort_order' => (int) ($image['sort_order'] ?? $index),
                'is_primary' => !empty($image['is_primary']) ? 1 : 0,
                'id' => $image['id'] ?? '',
                'product_id' => $productId,
            ]);
        }

        return $this->images($productId);
    }

    public function suggestions(string $keyword): array
    {
        $statement = $this->db->prepare(
            "SELECT DISTINCT p.name
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE p.status = 'active'
             AND p.deleted_at IS NULL
             AND v.status = 'active'
             AND (p.name LIKE :q OR p.description LIKE :q)
             ORDER BY p.name ASC
             LIMIT 8"
        );
        $statement->execute(['q' => '%' . trim($keyword) . '%']);

        return array_column($statement->fetchAll(), 'name');
    }

    public function reviews(string $productId, array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(20, max(5, (int) ($filters['per_page'] ?? 5)));
        $offset = ($page - 1) * $perPage;
        $count = $this->db->prepare(
            "SELECT COUNT(*)
             FROM reviews r
             WHERE r.product_id = :product_id
             AND r.review_type = 'product'
             AND r.status = 'approved'
             AND r.deleted_at IS NULL"
        );
        $count->execute(['product_id' => $productId]);
        $total = (int) $count->fetchColumn();

        $statement = $this->db->prepare(
            "SELECT r.id, r.rating, r.comment, r.created_at, u.name AS customer_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.customer_id
             WHERE r.product_id = :product_id
             AND r.review_type = 'product'
             AND r.status = 'approved'
             AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':product_id', $productId);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
            'rating' => $this->ratingSummary($productId),
        ];
    }

    public function createReview(string $customerId, string $productId, array $data): ?array
    {
        $booking = $this->verifiedRental($customerId, $productId);

        if (!$booking) {
            return null;
        }

        $id = Uuid::v4();
        $statement = $this->db->prepare(
            'INSERT INTO reviews
             (id, booking_id, customer_id, vendor_id, product_id, rating, review_type, comment, status)
             VALUES
             (:id, :booking_id, :customer_id, :vendor_id, :product_id, :rating, :review_type, :comment, :status)'
        );
        $statement->execute([
            'id' => $id,
            'booking_id' => $booking['booking_id'],
            'customer_id' => $customerId,
            'vendor_id' => $booking['vendor_id'],
            'product_id' => $productId,
            'rating' => max(1, min(5, (int) $data['rating'])),
            'review_type' => 'product',
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return ['id' => $id, 'status' => 'pending'];
    }

    private function images(string $productId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, image_url, thumbnail_url, alt_text, mime_type, file_size, width, height, sort_order, is_primary
             FROM product_images
             WHERE product_id = :product_id
             AND deleted_at IS NULL
             ORDER BY is_primary DESC, sort_order ASC"
        );
        $statement->execute(['product_id' => $productId]);

        return $statement->fetchAll();
    }

    private function units(string $productId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, sku, name, condition_status, availability_status
             FROM product_units
             WHERE product_id = :product_id
             AND deleted_at IS NULL
             ORDER BY name ASC"
        );
        $statement->execute(['product_id' => $productId]);

        return $statement->fetchAll();
    }

    private function related(string $productId, string $categoryId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                p.id,
                p.name,
                p.slug,
                p.price_per_day,
                p.stock_quantity,
                p.unit_label,
                v.store_name,
                v.city,
                c.name AS category_name,
                c.icon_key AS category_icon_key,
                (
                    SELECT pi.image_url
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    AND pi.deleted_at IS NULL
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS primary_image
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id <> :product_id
             AND p.category_id = :category_id
             AND p.status = 'active'
             AND p.deleted_at IS NULL
             ORDER BY p.created_at DESC
             LIMIT 4"
        );
        $statement->execute([
            'product_id' => $productId,
            'category_id' => $categoryId,
        ]);

        return $statement->fetchAll();
    }

    private function bookedQuantities(string $productId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT bi.start_date, bi.end_date, SUM(bi.quantity) AS quantity
             FROM booking_items bi
             INNER JOIN bookings b ON b.id = bi.booking_id
             WHERE bi.product_id = :product_id
             AND bi.deleted_at IS NULL
             AND b.deleted_at IS NULL
             AND b.status IN ('pending', 'confirmed', 'ongoing')
             AND bi.start_date <= :end_date
             AND bi.end_date >= :start_date
             GROUP BY bi.start_date, bi.end_date"
        );
        $statement->execute([
            'product_id' => $productId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $booked = [];
        foreach ($statement->fetchAll() as $row) {
            $from = new \DateTimeImmutable($row['start_date']);
            $to = new \DateTimeImmutable($row['end_date']);

            for ($date = $from; $date <= $to; $date = $date->modify('+1 day')) {
                $key = $date->format('Y-m-d');
                $booked[$key] = ($booked[$key] ?? 0) + (int) $row['quantity'];
            }
        }

        return $booked;
    }

    private function blockedQuantities(string $productId, string $startDate, string $endDate): array
    {
        $statement = $this->db->prepare(
            "SELECT start_date, end_date, SUM(quantity_blocked) AS quantity
             FROM product_availability_blocks
             WHERE product_id = :product_id
             AND deleted_at IS NULL
             AND start_date <= :end_date
             AND end_date >= :start_date
             GROUP BY start_date, end_date"
        );
        $statement->execute([
            'product_id' => $productId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $blocked = [];
        foreach ($statement->fetchAll() as $row) {
            $from = new \DateTimeImmutable($row['start_date']);
            $to = new \DateTimeImmutable($row['end_date']);

            for ($date = $from; $date <= $to; $date = $date->modify('+1 day')) {
                $key = $date->format('Y-m-d');
                $blocked[$key] = ($blocked[$key] ?? 0) + (int) $row['quantity'];
            }
        }

        return $blocked;
    }

    private function buildWhere(array $filters): array
    {
        $where = [
            "p.status = 'active'",
            'p.deleted_at IS NULL',
            'v.deleted_at IS NULL',
            "v.status = 'active'",
            'c.deleted_at IS NULL',
        ];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE :q OR p.description LIKE :q OR v.store_name LIKE :q)';
            $params[':q'] = '%' . trim($filters['q']) . '%';
        }

        if (!empty($filters['category'])) {
            $where[] = '(c.slug = :category OR c.id = :category)';
            $params[':category'] = trim($filters['category']);
        }

        if (!empty($filters['location'])) {
            $where[] = '(v.city LIKE :location OR v.province LIKE :location)';
            $params[':location'] = '%' . trim($filters['location']) . '%';
        }

        if (isset($filters['latitude'], $filters['longitude']) && $filters['latitude'] !== '' && $filters['longitude'] !== '') {
            $latitude = (float) $filters['latitude'];
            $longitude = (float) $filters['longitude'];
            $radiusKm = min(100, max(1, (float) ($filters['radius_km'] ?? 25)));
            $latDelta = $radiusKm / 111;
            $lngDelta = $radiusKm / max(1, 111 * cos(deg2rad($latitude)));

            $where[] = 'v.latitude BETWEEN :min_latitude AND :max_latitude';
            $where[] = 'v.longitude BETWEEN :min_longitude AND :max_longitude';
            $params[':min_latitude'] = $latitude - $latDelta;
            $params[':max_latitude'] = $latitude + $latDelta;
            $params[':min_longitude'] = $longitude - $lngDelta;
            $params[':max_longitude'] = $longitude + $lngDelta;
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $where[] = 'p.price_per_day >= :min_price';
            $params[':min_price'] = (float) $filters['min_price'];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $where[] = 'p.price_per_day <= :max_price';
            $params[':max_price'] = (float) $filters['max_price'];
        }

        if (!empty($filters['available_start']) && !empty($filters['available_end'])) {
            $where[] = "p.stock_quantity > (
                SELECT COALESCE(SUM(bi.quantity), 0)
                FROM booking_items bi
                INNER JOIN bookings b ON b.id = bi.booking_id
                WHERE bi.product_id = p.id
                AND bi.deleted_at IS NULL
                AND b.deleted_at IS NULL
                AND b.status IN ('pending', 'confirmed', 'ongoing')
                AND bi.start_date <= :available_end
                AND bi.end_date >= :available_start
            ) + (
                SELECT COALESCE(SUM(pab.quantity_blocked), 0)
                FROM product_availability_blocks pab
                WHERE pab.product_id = p.id
                AND pab.deleted_at IS NULL
                AND pab.start_date <= :available_end
                AND pab.end_date >= :available_start
            )";
            $params[':available_start'] = $filters['available_start'];
            $params[':available_end'] = $filters['available_end'];
        }

        return [implode(' AND ', $where), $params];
    }

    private function ratingSummary(string $productId): array
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) AS total_reviews, COALESCE(AVG(rating), 0) AS average_rating
             FROM reviews
             WHERE product_id = :product_id
             AND review_type = 'product'
             AND status = 'approved'
             AND deleted_at IS NULL"
        );
        $statement->execute(['product_id' => $productId]);
        $row = $statement->fetch() ?: ['total_reviews' => 0, 'average_rating' => 0];

        return [
            'total_reviews' => (int) $row['total_reviews'],
            'average_rating' => round((float) $row['average_rating'], 2),
        ];
    }

    private function verifiedRental(string $customerId, string $productId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT b.id AS booking_id, b.vendor_id
             FROM bookings b
             INNER JOIN booking_items bi ON bi.booking_id = b.id
             WHERE b.customer_id = :customer_id
             AND bi.product_id = :product_id
             AND b.status = 'completed'
             AND b.deleted_at IS NULL
             AND bi.deleted_at IS NULL
             ORDER BY b.end_date DESC
             LIMIT 1"
        );
        $statement->execute([
            'customer_id' => $customerId,
            'product_id' => $productId,
        ]);
        $booking = $statement->fetch();

        return $booking ?: null;
    }

    private function orderBy(string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'p.price_per_day ASC, p.created_at DESC',
            'price_desc' => 'p.price_per_day DESC, p.created_at DESC',
            'name_asc' => 'p.name ASC',
            'stock_desc' => 'p.stock_quantity DESC, p.created_at DESC',
            default => 'p.created_at DESC',
        };
    }

    private function uniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')) ?: 'produk';

        return $base . '-' . substr(Uuid::v4(), 0, 8);
    }
}
