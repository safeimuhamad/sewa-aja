<?php

namespace App\PaymentService\Services;

use RuntimeException;

class MidtransClient
{
    public function createSnapTransaction(array $payload): array
    {
        $serverKey = getenv('MIDTRANS_SERVER_KEY') ?: '';

        if ($serverKey === '' || str_contains($serverKey, 'your-sandbox-server-key')) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $url = $this->snapUrl();
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Gagal menghubungi Midtrans: ' . $error);
        }

        $data = json_decode($response, true);

        if ($statusCode < 200 || $statusCode >= 300 || !is_array($data)) {
            throw new RuntimeException($data['error_messages'][0] ?? 'Midtrans menolak transaksi.');
        }

        return $data;
    }

    public function verifySignature(array $payload): bool
    {
        $serverKey = getenv('MIDTRANS_SERVER_KEY') ?: '';
        $signature = $payload['signature_key'] ?? '';

        if ($serverKey === '' || $signature === '') {
            return false;
        }

        $expected = hash(
            'sha512',
            ($payload['order_id'] ?? '')
            . ($payload['status_code'] ?? '')
            . ($payload['gross_amount'] ?? '')
            . $serverKey
        );

        return hash_equals($expected, $signature);
    }

    private function snapUrl(): string
    {
        $isProduction = filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: false, FILTER_VALIDATE_BOOLEAN);

        if ($isProduction) {
            return getenv('MIDTRANS_SNAP_PRODUCTION_URL') ?: 'https://app.midtrans.com/snap/v1/transactions';
        }

        return getenv('MIDTRANS_SNAP_SANDBOX_URL') ?: 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }
}
