<?php

namespace App\BookingService\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Shared\Support\Uuid;

class BookingRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function quote(array $items): array
    {
        $quotedItems = [];
        $subtotal = 0.0;
        $deposit = 0.0;
        $errors = [];

        foreach ($items as $index => $item) {
            $product = $this->findProduct((string) ($item['product_id'] ?? $item['slug'] ?? ''));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $startDate = (string) ($item['start_date'] ?? '');
            $endDate = (string) ($item['end_date'] ?? '');

            if (!$product) {
                $errors["items.{$index}.product"][] = 'Produk tidak ditemukan.';
                continue;
            }

            if (!$this->validDateRange($startDate, $endDate)) {
                $errors["items.{$index}.date"][] = 'Tanggal sewa tidak valid.';
                continue;
            }

            $duration = $this->duration($startDate, $endDate);
            $availableQuantity = $this->availableQuantity($product['id'], $startDate, $endDate);
            $isAvailable = $availableQuantity >= $quantity;
            $lineSubtotal = (float) $product['price_per_day'] * $duration * $quantity;
            $lineDeposit = (float) $product['deposit_amount'] * $quantity;

            if (!$isAvailable) {
                $errors["items.{$index}.quantity"][] = "Stok tersedia hanya {$availableQuantity}.";
            }

            $subtotal += $lineSubtotal;
            $deposit += $lineDeposit;
            $quotedItems[] = [
                'product_id' => $product['id'],
                'vendor_id' => $product['vendor_id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'store_name' => $product['store_name'],
                'city' => $product['city'],
                'price_per_day' => (float) $product['price_per_day'],
                'deposit_amount' => (float) $product['deposit_amount'],
                'quantity' => $quantity,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_days' => $duration,
                'available_quantity' => $availableQuantity,
                'is_available' => $isAvailable,
                'line_subtotal' => $lineSubtotal,
                'line_deposit' => $lineDeposit,
                'line_total' => $lineSubtotal + $lineDeposit,
            ];
        }

        return [
            'items' => $quotedItems,
            'summary' => [
                'subtotal_amount' => $subtotal,
                'deposit_amount' => $deposit,
                'total_amount' => $subtotal + $deposit,
                'is_available' => $errors === [] && $quotedItems !== [],
            ],
            'errors' => $errors,
        ];
    }

    public function checkout(string $customerId, array $items, string $paymentMethod = 'bank_transfer', ?string $notes = null): array
    {
        $quote = $this->quote($items);

        if (!$quote['summary']['is_available']) {
            throw new RuntimeException(json_encode($quote['errors']));
        }

        $grouped = [];
        foreach ($quote['items'] as $item) {
            $grouped[$item['vendor_id']][] = $item;
        }

        $createdBookings = [];
        $this->db->beginTransaction();

        try {
            foreach ($grouped as $vendorId => $vendorItems) {
                $bookingTotals = $this->totals($vendorItems);
                $bookingId = Uuid::v4();
                $bookingCode = $this->code('BKG');

                $booking = $this->db->prepare(
                    'INSERT INTO bookings
                     (id, customer_id, vendor_id, booking_code, status, start_date, end_date, subtotal_amount, deposit_amount, total_amount, notes)
                     VALUES
                     (:id, :customer_id, :vendor_id, :booking_code, :status, :start_date, :end_date, :subtotal_amount, :deposit_amount, :total_amount, :notes)'
                );
                $booking->execute([
                    'id' => $bookingId,
                    'customer_id' => $customerId,
                    'vendor_id' => $vendorId,
                    'booking_code' => $bookingCode,
                    'status' => 'pending',
                    'start_date' => min(array_column($vendorItems, 'start_date')),
                    'end_date' => max(array_column($vendorItems, 'end_date')),
                    'subtotal_amount' => $bookingTotals['subtotal_amount'],
                    'deposit_amount' => $bookingTotals['deposit_amount'],
                    'total_amount' => $bookingTotals['total_amount'],
                    'notes' => $notes,
                ]);

                foreach ($vendorItems as $item) {
                    $bookingItem = $this->db->prepare(
                        'INSERT INTO booking_items
                         (id, booking_id, product_id, product_unit_id, product_name, quantity, price_per_day, start_date, end_date, line_total)
                         VALUES
                         (:id, :booking_id, :product_id, :product_unit_id, :product_name, :quantity, :price_per_day, :start_date, :end_date, :line_total)'
                    );
                    $bookingItem->execute([
                        'id' => Uuid::v4(),
                        'booking_id' => $bookingId,
                        'product_id' => $item['product_id'],
                        'product_unit_id' => null,
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price_per_day' => $item['price_per_day'],
                        'start_date' => $item['start_date'],
                        'end_date' => $item['end_date'],
                        'line_total' => $item['line_subtotal'],
                    ]);
                }

                $paymentId = Uuid::v4();
                $paymentCode = $this->code('PAY');
                $payment = $this->db->prepare(
                    'INSERT INTO payments (id, booking_id, payment_code, method, status, amount)
                     VALUES (:id, :booking_id, :payment_code, :method, :status, :amount)'
                );
                $payment->execute([
                    'id' => $paymentId,
                    'booking_id' => $bookingId,
                    'payment_code' => $paymentCode,
                    'method' => $paymentMethod,
                    'status' => 'pending',
                    'amount' => $bookingTotals['total_amount'],
                ]);

                $createdBookings[] = [
                    'id' => $bookingId,
                    'booking_code' => $bookingCode,
                    'payment_id' => $paymentId,
                    'payment_code' => $paymentCode,
                    'vendor_id' => $vendorId,
                    'items' => $vendorItems,
                    'summary' => $bookingTotals,
                    'status' => 'pending',
                ];
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return [
            'bookings' => $createdBookings,
            'summary' => $quote['summary'],
        ];
    }

    public function customerBookings(string $customerId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, booking_code, status, start_date, end_date, subtotal_amount, deposit_amount, total_amount, created_at
             FROM bookings
             WHERE customer_id = :customer_id
             AND deleted_at IS NULL
             ORDER BY created_at DESC'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    public function customerDashboard(string $customerId): array
    {
        return [
            'widgets' => [
                'active_rentals' => $this->scalar(
                    "SELECT COUNT(*) FROM bookings WHERE customer_id = :customer_id AND status IN ('confirmed', 'ongoing') AND deleted_at IS NULL",
                    ['customer_id' => $customerId]
                ),
                'pending_bookings' => $this->scalar(
                    "SELECT COUNT(*) FROM bookings WHERE customer_id = :customer_id AND status = 'pending' AND deleted_at IS NULL",
                    ['customer_id' => $customerId]
                ),
                'completed_rentals' => $this->scalar(
                    "SELECT COUNT(*) FROM bookings WHERE customer_id = :customer_id AND status = 'completed' AND deleted_at IS NULL",
                    ['customer_id' => $customerId]
                ),
                'total_spend' => $this->money(
                    "SELECT COALESCE(SUM(p.amount), 0)
                     FROM payments p
                     INNER JOIN bookings b ON b.id = p.booking_id
                     WHERE b.customer_id = :customer_id
                     AND p.status = 'paid'
                     AND p.deleted_at IS NULL
                     AND b.deleted_at IS NULL",
                    ['customer_id' => $customerId]
                ),
            ],
            'active_rentals' => $this->customerBookingsPaginated($customerId, ['status_group' => 'active', 'per_page' => 3])['items'],
            'recent_history' => $this->customerBookingsPaginated($customerId, ['status_group' => 'history', 'per_page' => 5])['items'],
        ];
    }

    public function customerBookingsPaginated(string $customerId, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(30, max(5, (int) ($filters['per_page'] ?? 6)));
        $offset = ($page - 1) * $perPage;
        $where = ['b.customer_id = :customer_id', 'b.deleted_at IS NULL'];
        $params = ['customer_id' => $customerId];

        if (($filters['status_group'] ?? '') === 'active') {
            $where[] = "b.status IN ('pending', 'confirmed', 'ongoing')";
        } elseif (($filters['status_group'] ?? '') === 'history') {
            $where[] = "b.status IN ('completed', 'cancelled')";
        } elseif (!empty($filters['status'])) {
            $where[] = 'b.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(b.booking_code LIKE :q OR v.store_name LIKE :q)';
            $params['q'] = '%' . trim($filters['q']) . '%';
        }

        $whereSql = implode(' AND ', $where);
        $total = $this->total("SELECT COUNT(*) FROM bookings b INNER JOIN vendors v ON v.id = b.vendor_id WHERE {$whereSql}", $params);
        $statement = $this->db->prepare(
            "SELECT b.id, b.booking_code, b.status, b.start_date, b.end_date, b.subtotal_amount, b.deposit_amount, b.total_amount, b.created_at,
                    v.store_name, v.city, v.province,
                    p.payment_code, p.method AS payment_method, p.status AS payment_status, p.paid_at,
                    (
                        SELECT COALESCE(SUM(bi.quantity), 0)
                        FROM booking_items bi
                        WHERE bi.booking_id = b.id
                        AND bi.deleted_at IS NULL
                    ) AS total_quantity
             FROM bookings b
             INNER JOIN vendors v ON v.id = b.vendor_id
             LEFT JOIN payments p ON p.booking_id = b.id AND p.deleted_at IS NULL
             WHERE {$whereSql}
             ORDER BY b.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . ltrim((string) $key, ':'), $value);
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

    public function customerBookingDetail(string $customerId, string $bookingId): ?array
    {
        $statement = $this->db->prepare(
            "SELECT b.*, v.store_name, v.address AS vendor_address, v.city, v.province,
                    u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                    p.id AS payment_id, p.payment_code, p.method AS payment_method, p.status AS payment_status, p.amount AS payment_amount, p.paid_at, p.transaction_reference
             FROM bookings b
             INNER JOIN vendors v ON v.id = b.vendor_id
             INNER JOIN users u ON u.id = b.customer_id
             LEFT JOIN payments p ON p.booking_id = b.id AND p.deleted_at IS NULL
             WHERE b.id = :id
             AND b.customer_id = :customer_id
             AND b.deleted_at IS NULL
             LIMIT 1"
        );
        $statement->execute([
            'id' => $bookingId,
            'customer_id' => $customerId,
        ]);
        $booking = $statement->fetch();

        if (!$booking) {
            return null;
        }

        $booking['items'] = $this->customerBookingItems($bookingId);

        return $booking;
    }

    public function cancelCustomerBooking(string $customerId, string $bookingId): ?array
    {
        $booking = $this->customerBookingDetail($customerId, $bookingId);

        if (!$booking) {
            return null;
        }

        if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
            throw new RuntimeException('Booking ini tidak bisa dibatalkan.');
        }

        $this->db->prepare(
            "UPDATE bookings
             SET status = 'cancelled'
             WHERE id = :id
             AND customer_id = :customer_id
             AND deleted_at IS NULL"
        )->execute([
            'id' => $bookingId,
            'customer_id' => $customerId,
        ]);

        return $this->customerBookingDetail($customerId, $bookingId);
    }

    public function customerInvoice(string $customerId, string $bookingId): ?array
    {
        $booking = $this->customerBookingDetail($customerId, $bookingId);

        if (!$booking) {
            return null;
        }

        return [
            'invoice_number' => 'INV-' . $booking['booking_code'],
            'issued_at' => date(DATE_ATOM),
            'booking' => $booking,
            'summary' => [
                'subtotal_amount' => (float) $booking['subtotal_amount'],
                'deposit_amount' => (float) $booking['deposit_amount'],
                'total_amount' => (float) $booking['total_amount'],
                'payment_status' => $booking['payment_status'],
            ],
        ];
    }

    public function vendorBookings(string $vendorId): array
    {
        $statement = $this->db->prepare(
            "SELECT
                b.id,
                b.booking_code,
                b.status,
                b.start_date,
                b.end_date,
                b.subtotal_amount,
                b.deposit_amount,
                b.total_amount,
                b.notes,
                b.created_at,
                u.name AS customer_name,
                u.email AS customer_email,
                p.status AS payment_status,
                p.method AS payment_method,
                p.paid_at,
                (
                    SELECT COUNT(*)
                    FROM booking_items bi
                    WHERE bi.booking_id = b.id
                    AND bi.deleted_at IS NULL
                ) AS item_count,
                (
                    SELECT COALESCE(SUM(bi.quantity), 0)
                    FROM booking_items bi
                    WHERE bi.booking_id = b.id
                    AND bi.deleted_at IS NULL
                ) AS total_quantity
             FROM bookings b
             INNER JOIN users u ON u.id = b.customer_id
             LEFT JOIN payments p ON p.booking_id = b.id AND p.deleted_at IS NULL
             WHERE b.vendor_id = :vendor_id
             AND b.deleted_at IS NULL
             ORDER BY b.created_at DESC"
        );
        $statement->execute(['vendor_id' => $vendorId]);

        return $statement->fetchAll();
    }

    public function vendorSummary(string $vendorId): array
    {
        return [
            'widgets' => [
                'total_products' => $this->scalar(
                    'SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id AND deleted_at IS NULL',
                    ['vendor_id' => $vendorId]
                ),
                'active_products' => $this->scalar(
                    "SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id AND status = 'active' AND deleted_at IS NULL",
                    ['vendor_id' => $vendorId]
                ),
                'pending_bookings' => $this->scalar(
                    "SELECT COUNT(*) FROM bookings WHERE vendor_id = :vendor_id AND status = 'pending' AND deleted_at IS NULL",
                    ['vendor_id' => $vendorId]
                ),
                'active_rentals' => $this->scalar(
                    "SELECT COUNT(*) FROM bookings WHERE vendor_id = :vendor_id AND status IN ('confirmed', 'ongoing') AND deleted_at IS NULL",
                    ['vendor_id' => $vendorId]
                ),
            ],
            'sales' => [
                'paid_revenue' => $this->money(
                    "SELECT COALESCE(SUM(p.amount), 0)
                     FROM payments p
                     INNER JOIN bookings b ON b.id = p.booking_id
                     WHERE b.vendor_id = :vendor_id
                     AND p.status = 'paid'
                     AND p.deleted_at IS NULL
                     AND b.deleted_at IS NULL",
                    ['vendor_id' => $vendorId]
                ),
                'this_month_revenue' => $this->money(
                    "SELECT COALESCE(SUM(p.amount), 0)
                     FROM payments p
                     INNER JOIN bookings b ON b.id = p.booking_id
                     WHERE b.vendor_id = :vendor_id
                     AND p.status = 'paid'
                     AND p.deleted_at IS NULL
                     AND b.deleted_at IS NULL
                     AND p.paid_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')",
                    ['vendor_id' => $vendorId]
                ),
                'pending_amount' => $this->money(
                    "SELECT COALESCE(SUM(total_amount), 0)
                     FROM bookings
                     WHERE vendor_id = :vendor_id
                     AND status = 'pending'
                     AND deleted_at IS NULL",
                    ['vendor_id' => $vendorId]
                ),
            ],
            'recent_bookings' => array_slice($this->vendorBookings($vendorId), 0, 5),
        ];
    }

    public function vendorFinance(string $vendorId): array
    {
        $feePercent = max(0, (float) (getenv('PLATFORM_FEE_PERCENT') ?: 10));
        $grossRevenue = $this->money(
            "SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE b.vendor_id = :vendor_id
             AND p.status = 'paid'
             AND p.deleted_at IS NULL
             AND b.deleted_at IS NULL",
            ['vendor_id' => $vendorId]
        );
        $paidOut = $this->money(
            "SELECT COALESCE(SUM(amount), 0)
             FROM vendor_payouts
             WHERE vendor_id = :vendor_id
             AND status = 'paid'
             AND deleted_at IS NULL",
            ['vendor_id' => $vendorId]
        );
        $platformFee = round($grossRevenue * ($feePercent / 100), 2);
        $netEarnings = max(0, $grossRevenue - $platformFee);

        return [
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => $platformFee,
                'net_earnings' => $netEarnings,
                'paid_out' => $paidOut,
                'available_balance' => max(0, $netEarnings - $paidOut),
            ],
            'transactions' => $this->vendorFinanceTransactions($vendorId),
            'payouts' => $this->vendorPayouts($vendorId),
        ];
    }

    public function vendorFinanceTransactions(string $vendorId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, booking_id, payment_id, type, amount, platform_fee_amount, net_amount, description, created_at
             FROM vendor_finance_transactions
             WHERE vendor_id = :vendor_id
             AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $statement->execute(['vendor_id' => $vendorId]);
        $transactions = $statement->fetchAll();

        if ($transactions !== []) {
            return $transactions;
        }

        $feePercent = max(0, (float) (getenv('PLATFORM_FEE_PERCENT') ?: 10));
        $statement = $this->db->prepare(
            "SELECT b.id AS booking_id, p.id AS payment_id, p.amount, p.created_at, b.booking_code
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE b.vendor_id = :vendor_id
             AND p.status = 'paid'
             AND p.deleted_at IS NULL
             AND b.deleted_at IS NULL
             ORDER BY p.paid_at DESC, p.created_at DESC
             LIMIT 50"
        );
        $statement->execute(['vendor_id' => $vendorId]);

        return array_map(static function (array $row) use ($feePercent): array {
            $fee = round((float) $row['amount'] * ($feePercent / 100), 2);

            return [
                'id' => null,
                'booking_id' => $row['booking_id'],
                'payment_id' => $row['payment_id'],
                'type' => 'earning',
                'amount' => (float) $row['amount'],
                'platform_fee_amount' => $fee,
                'net_amount' => max(0, (float) $row['amount'] - $fee),
                'description' => 'Pendapatan booking ' . $row['booking_code'],
                'created_at' => $row['created_at'],
            ];
        }, $statement->fetchAll());
    }

    public function vendorPayouts(string $vendorId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, payout_code, amount, status, bank_name, bank_account_name, paid_at, created_at
             FROM vendor_payouts
             WHERE vendor_id = :vendor_id
             AND deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT 50"
        );
        $statement->execute(['vendor_id' => $vendorId]);

        return $statement->fetchAll();
    }

    public function updateVendorBookingStatus(string $vendorId, string $bookingId, string $status): ?array
    {
        $allowed = ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];

        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Status booking tidak valid.');
        }

        $statement = $this->db->prepare(
            'UPDATE bookings
             SET status = :status
             WHERE id = :id
             AND vendor_id = :vendor_id
             AND deleted_at IS NULL'
        );
        $statement->execute([
            'status' => $status,
            'id' => $bookingId,
            'vendor_id' => $vendorId,
        ]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $booking = $this->db->prepare(
            'SELECT id, booking_code, status, start_date, end_date, subtotal_amount, deposit_amount, total_amount
             FROM bookings
             WHERE id = :id AND vendor_id = :vendor_id
             LIMIT 1'
        );
        $booking->execute([
            'id' => $bookingId,
            'vendor_id' => $vendorId,
        ]);

        return $booking->fetch() ?: null;
    }

    private function findProduct(string $identifier): ?array
    {
        $statement = $this->db->prepare(
            "SELECT p.*, v.store_name, v.city
             FROM products p
             INNER JOIN vendors v ON v.id = p.vendor_id
             WHERE (p.id = :identifier OR p.slug = :identifier)
             AND p.status = 'active'
             AND p.deleted_at IS NULL
             AND v.deleted_at IS NULL
             LIMIT 1"
        );
        $statement->execute(['identifier' => $identifier]);
        $product = $statement->fetch();

        return $product ?: null;
    }

    private function customerBookingItems(string $bookingId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, product_id, product_name, quantity, price_per_day, start_date, end_date, line_total
             FROM booking_items
             WHERE booking_id = :booking_id
             AND deleted_at IS NULL
             ORDER BY created_at ASC'
        );
        $statement->execute(['booking_id' => $bookingId]);

        return $statement->fetchAll();
    }

    private function total(string $sql, array $params): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function scalar(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function money(string $sql, array $params = []): float
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (float) $statement->fetchColumn();
    }

    private function availableQuantity(string $productId, string $startDate, string $endDate): int
    {
        $product = $this->findProduct($productId);

        if (!$product) {
            return 0;
        }

        $statement = $this->db->prepare(
            "SELECT COALESCE(SUM(bi.quantity), 0) AS booked_quantity
             FROM booking_items bi
             INNER JOIN bookings b ON b.id = bi.booking_id
             WHERE bi.product_id = :product_id
             AND bi.deleted_at IS NULL
             AND b.deleted_at IS NULL
             AND b.status IN ('pending', 'confirmed', 'ongoing')
             AND bi.start_date <= :end_date
             AND bi.end_date >= :start_date"
        );
        $statement->execute([
            'product_id' => $productId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $blocked = $this->scalar(
            "SELECT COALESCE(SUM(quantity_blocked), 0)
             FROM product_availability_blocks
             WHERE product_id = :product_id
             AND deleted_at IS NULL
             AND start_date <= :end_date
             AND end_date >= :start_date",
            [
                'product_id' => $productId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        return max(0, (int) $product['stock_quantity'] - (int) $statement->fetchColumn() - $blocked);
    }

    private function validDateRange(string $startDate, string $endDate): bool
    {
        if (!$startDate || !$endDate) {
            return false;
        }

        $start = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        return $start && $end && $end >= $start;
    }

    private function duration(string $startDate, string $endDate): int
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        return ((int) $start->diff($end)->days) + 1;
    }

    private function totals(array $items): array
    {
        $subtotal = array_sum(array_column($items, 'line_subtotal'));
        $deposit = array_sum(array_column($items, 'line_deposit'));

        return [
            'subtotal_amount' => $subtotal,
            'deposit_amount' => $deposit,
            'total_amount' => $subtotal + $deposit,
        ];
    }

    private function code(string $prefix): string
    {
        return sprintf('%s-%s-%04d', $prefix, date('YmdHis'), random_int(1, 9999));
    }
}
