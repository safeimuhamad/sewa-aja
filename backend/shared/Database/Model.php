<?php

namespace Shared\Database;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $casts = [];

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    protected function belongsTo(string $model, string $foreignKey, string $ownerKey = 'id'): array
    {
        return [
            'type' => 'belongsTo',
            'model' => $model,
            'foreign_key' => $foreignKey,
            'owner_key' => $ownerKey,
        ];
    }

    protected function hasOne(string $model, string $foreignKey, string $localKey = 'id'): array
    {
        return [
            'type' => 'hasOne',
            'model' => $model,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey,
        ];
    }

    protected function hasMany(string $model, string $foreignKey, string $localKey = 'id'): array
    {
        return [
            'type' => 'hasMany',
            'model' => $model,
            'foreign_key' => $foreignKey,
            'local_key' => $localKey,
        ];
    }
}
