<?php

namespace App\UserService\Models;

use App\BookingService\Models\Booking;
use App\ProductService\Models\Product;
use Shared\Database\Model;

class Vendor extends Model
{
    protected string $table = 'vendors';

    protected array $fillable = [
        'user_id',
        'store_name',
        'slug',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'status',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): array
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): array
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function bookings(): array
    {
        return $this->hasMany(Booking::class, 'vendor_id');
    }
}
