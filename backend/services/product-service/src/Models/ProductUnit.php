<?php

namespace App\ProductService\Models;

use App\BookingService\Models\BookingItem;
use Shared\Database\Model;

class ProductUnit extends Model
{
    protected string $table = 'product_units';

    protected array $fillable = [
        'product_id',
        'sku',
        'name',
        'serial_number',
        'condition_status',
        'availability_status',
        'notes',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function product(): array
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function bookingItems(): array
    {
        return $this->hasMany(BookingItem::class, 'product_unit_id');
    }
}
