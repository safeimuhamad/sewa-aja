<?php

namespace App\ProductService\Models;

use Shared\Database\Model;

class ProductImage extends Model
{
    protected string $table = 'product_images';

    protected array $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected array $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function product(): array
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
