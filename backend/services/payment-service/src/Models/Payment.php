<?php

namespace App\PaymentService\Models;

use App\BookingService\Models\Booking;
use Shared\Database\Model;

class Payment extends Model
{
    protected string $table = 'payments';

    protected array $fillable = [
        'booking_id',
        'payment_code',
        'method',
        'status',
        'amount',
        'paid_at',
        'transaction_reference',
        'proof_image_url',
    ];

    protected array $casts = [
        'amount' => 'decimal',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function booking(): array
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
