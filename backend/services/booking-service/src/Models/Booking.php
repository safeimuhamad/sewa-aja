<?php

namespace App\BookingService\Models;

use App\PaymentService\Models\Payment;
use App\UserService\Models\User;
use App\UserService\Models\Vendor;
use Shared\Database\Model;

class Booking extends Model
{
    protected string $table = 'bookings';

    protected array $fillable = [
        'customer_id',
        'vendor_id',
        'booking_code',
        'status',
        'start_date',
        'end_date',
        'subtotal_amount',
        'deposit_amount',
        'total_amount',
        'notes',
    ];

    protected array $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'subtotal_amount' => 'decimal',
        'deposit_amount' => 'decimal',
        'total_amount' => 'decimal',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer(): array
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor(): array
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items(): array
    {
        return $this->hasMany(BookingItem::class, 'booking_id');
    }

    public function payments(): array
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }
}
