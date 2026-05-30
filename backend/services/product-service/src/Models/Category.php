<?php

namespace App\ProductService\Models;

use Shared\Database\Model;

class Category extends Model
{
    protected string $table = 'categories';

    protected array $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function parent(): array
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): array
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): array
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
