<?php

namespace App\PaymentService\Controllers;

use App\PaymentService\Repositories\PaymentRepository;
use App\PaymentService\Services\MidtransClient;
use Shared\Http\Request;
use Shared\Http\Response;

class MidtransCallbackController
{
    public function __construct(
        private PaymentRepository $payments,
        private MidtransClient $midtrans
    ) {
    }

    public function handle(): void
    {
        $payload = Request::json();

        if (!$this->midtrans->verifySignature($payload)) {
            Response::error('Signature Midtrans tidak valid.', 403);
            return;
        }

        $payment = $this->payments->findByMidtransOrderId($payload['order_id'] ?? '');

        if (!$payment) {
            Response::error('Payment tidak ditemukan untuk order_id ini.', 404);
            return;
        }

        $result = $this->payments->updateFromNotification($payment, $payload);

        Response::success($result, 'Callback Midtrans berhasil diproses.');
    }
}
