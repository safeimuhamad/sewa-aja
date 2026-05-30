<?php

namespace App\PaymentService\Controllers;

use App\PaymentService\Middleware\PaymentAuthMiddleware;
use App\PaymentService\Repositories\PaymentRepository;
use App\PaymentService\Services\MidtransClient;
use Shared\Http\Request;
use Shared\Http\Response;

class PaymentController
{
    public function __construct(
        private PaymentRepository $payments,
        private PaymentAuthMiddleware $auth,
        private MidtransClient $midtrans
    ) {
    }

    public function createToken(): void
    {
        $user = $this->auth->requireCustomer();

        if (!$user) {
            return;
        }

        $data = Request::json();
        $paymentCode = $data['payment_code'] ?? '';

        if ($paymentCode === '') {
            Response::error('payment_code wajib dikirim.', 422, ['payment_code' => ['Field wajib diisi.']]);
            return;
        }

        $payment = $this->payments->findForCustomer($paymentCode, $user['id']);

        if (!$payment) {
            Response::error('Payment tidak ditemukan.', 404);
            return;
        }

        if ($payment['status'] === 'paid') {
            Response::error('Payment sudah lunas.', 409);
            return;
        }

        if ($payment['snap_token'] && $payment['redirect_url']) {
            Response::success([
                'payment_code' => $payment['payment_code'],
                'midtrans_order_id' => $payment['midtrans_order_id'],
                'snap_token' => $payment['snap_token'],
                'redirect_url' => $payment['redirect_url'],
            ], 'Snap token sudah tersedia.');
            return;
        }

        $orderId = $payment['payment_code'] . '-' . time();
        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round((float) $payment['amount']),
            ],
            'customer_details' => [
                'first_name' => $payment['customer_name'],
                'email' => $payment['customer_email'],
                'phone' => $payment['customer_phone'],
            ],
            'enabled_payments' => $this->enabledPayments($data['channel'] ?? 'all'),
            'callbacks' => [
                'finish' => getenv('MIDTRANS_FINISH_URL') ?: 'http://localhost/sewaaja/frontend/public/payment-status.html',
            ],
            'custom_field1' => $payment['booking_code'],
            'custom_field2' => $payment['payment_code'],
        ];

        try {
            $snap = $this->midtrans->createSnapTransaction($payload);
        } catch (\Throwable $exception) {
            Response::error('Gagal membuat token Midtrans.', 502, ['midtrans' => [$exception->getMessage()]]);
            return;
        }

        $this->payments->updateSnapData(
            $payment['id'],
            $orderId,
            $snap['token'],
            $snap['redirect_url'],
            $snap
        );

        Response::success([
            'payment_code' => $payment['payment_code'],
            'midtrans_order_id' => $orderId,
            'snap_token' => $snap['token'],
            'redirect_url' => $snap['redirect_url'],
        ], 'Snap token berhasil dibuat.');
    }

    public function history(): void
    {
        $user = $this->auth->requireCustomer();

        if (!$user) {
            return;
        }

        Response::success([
            'payments' => $this->payments->history($user['id']),
        ], 'Riwayat pembayaran berhasil diambil.');
    }

    private function enabledPayments(string $channel): array
    {
        return match ($channel) {
            'qris' => ['qris'],
            'va' => ['bank_transfer', 'bca_va', 'bni_va', 'bri_va', 'permata_va'],
            default => ['qris', 'bank_transfer', 'bca_va', 'bni_va', 'bri_va', 'permata_va'],
        };
    }
}
