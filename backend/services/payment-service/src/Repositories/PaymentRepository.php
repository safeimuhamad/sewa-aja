<?php

namespace App\PaymentService\Repositories;

use PDO;

class PaymentRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findForCustomer(string $paymentCode, string $customerId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                p.*,
                b.booking_code,
                b.customer_id,
                b.status AS booking_status,
                u.name AS customer_name,
                u.email AS customer_email,
                u.phone AS customer_phone
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             INNER JOIN users u ON u.id = b.customer_id
             WHERE p.payment_code = :payment_code
             AND b.customer_id = :customer_id
             AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute([
            'payment_code' => $paymentCode,
            'customer_id' => $customerId,
        ]);
        $payment = $statement->fetch();

        return $payment ?: null;
    }

    public function findByMidtransOrderId(string $orderId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.*, b.id AS booking_id, b.booking_code
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE p.midtrans_order_id = :order_id
             AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['order_id' => $orderId]);
        $payment = $statement->fetch();

        return $payment ?: null;
    }

    public function history(string $customerId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                p.id,
                p.payment_code,
                p.midtrans_order_id,
                p.method,
                p.payment_type,
                p.status,
                p.transaction_status,
                p.amount,
                p.paid_at,
                p.redirect_url,
                p.created_at,
                b.booking_code,
                b.status AS booking_status
             FROM payments p
             INNER JOIN bookings b ON b.id = p.booking_id
             WHERE b.customer_id = :customer_id
             AND p.deleted_at IS NULL
             ORDER BY p.created_at DESC'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    public function updateSnapData(string $paymentId, string $orderId, string $token, string $redirectUrl, array $raw): void
    {
        $statement = $this->db->prepare(
            'UPDATE payments
             SET midtrans_order_id = :midtrans_order_id,
                 snap_token = :snap_token,
                 redirect_url = :redirect_url,
                 method = :method,
                 raw_response = :raw_response
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $paymentId,
            'midtrans_order_id' => $orderId,
            'snap_token' => $token,
            'redirect_url' => $redirectUrl,
            'method' => 'payment_gateway',
            'raw_response' => json_encode($raw, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function updateFromNotification(array $payment, array $payload): array
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        [$paymentStatus, $bookingStatus, $paidAt] = $this->mapStatus($transactionStatus, $fraudStatus);

        $this->db->beginTransaction();
        try {
            $paymentStatement = $this->db->prepare(
                'UPDATE payments
                 SET status = :status,
                     transaction_status = :transaction_status,
                     fraud_status = :fraud_status,
                     payment_type = :payment_type,
                     transaction_reference = :transaction_reference,
                     paid_at = COALESCE(:paid_at, paid_at),
                     raw_response = :raw_response
                 WHERE id = :id'
            );
            $paymentStatement->execute([
                'id' => $payment['id'],
                'status' => $paymentStatus,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $paymentType,
                'transaction_reference' => $payload['transaction_id'] ?? null,
                'paid_at' => $paidAt,
                'raw_response' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ]);

            $bookingStatement = $this->db->prepare('UPDATE bookings SET status = :status WHERE id = :id');
            $bookingStatement->execute([
                'id' => $payment['booking_id'],
                'status' => $bookingStatus,
            ]);

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return [
            'payment_status' => $paymentStatus,
            'booking_status' => $bookingStatus,
        ];
    }

    private function mapStatus(?string $transactionStatus, ?string $fraudStatus): array
    {
        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                return ['pending', 'pending', null];
            }

            return ['paid', 'confirmed', date('Y-m-d H:i:s')];
        }

        return match ($transactionStatus) {
            'settlement' => ['paid', 'confirmed', date('Y-m-d H:i:s')],
            'pending' => ['pending', 'pending', null],
            'expire' => ['expired', 'cancelled', null],
            'cancel' => ['failed', 'cancelled', null],
            'deny' => ['failed', 'cancelled', null],
            'failure' => ['failed', 'cancelled', null],
            'refund', 'partial_refund' => ['refunded', 'cancelled', null],
            default => ['pending', 'pending', null],
        };
    }
}
