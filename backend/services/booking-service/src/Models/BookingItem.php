<?php

namespace App\BookingService\Models;

use App\ProductService\Models\Product;
use App\ProductService\Models\ProductUnit;
use Shared\Database\Model;

class BookingItem extends Model
{
    protected string $table = 'booking_items';

    protected array $fillable = [
        'booking_id',
        'product_id',
        'product_unit_id',
        'product_name',
        'quantity',
        'price_per_day',
        'start_date',
        'end_date',
        'line_total',
    ];

    protected array $casts = [
        'quantity' => 'integer',
        'price_per_day' => 'decimal',
        'start_date' => 'date',
        'end_date' => 'date',
        'line_total' => 'decimal',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function booking(): array
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function product(): array
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productUnit(): array
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
