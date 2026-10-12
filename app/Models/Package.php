<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'tagline', 'features', 'price', 'is_popular', 'sort_order'])]
class Package extends Model
{
    /**
     * Casting atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_popular' => 'boolean',
        ];
    }

    /**
     * Urutkan berdasarkan sort_order lalu id.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Label harga untuk ditampilkan.
     */
    public function priceLabel(): string
    {
        return filled($this->price) ? $this->price : 'Hubungi kami';
    }
}
