<?php

namespace App\ProductService\Models;

use App\BookingService\Models\BookingItem;
use App\UserService\Models\Vendor;
use Shared\Database\Model;

class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price_per_day',
        'deposit_amount',
        'stock_quantity',
        'unit_label',
        'status',
    ];

    protected array $casts = [
        'price_per_day' => 'decimal',
        'deposit_amount' => 'decimal',
        'stock_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function vendor(): array
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function category(): array
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images(): array
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function units(): array
    {
        return $this->hasMany(ProductUnit::class, 'product_id');
    }

    public function bookingItems(): array
    {
        return $this->hasMany(BookingItem::class, 'product_id');
    }
}
