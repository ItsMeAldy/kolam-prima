<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'location', 'property_type', 'description', 'sort_order', 'is_published'])]
class Project extends Model
{
    /**
     * Casting atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Gambar-gambar proyek.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Hanya proyek yang dipublikasikan.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Urutkan berdasarkan sort_order lalu id.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * URL gambar utama proyek. Jatuh ke placeholder bila belum ada gambar.
     */
    public function coverUrl(): string
    {
        return $this->images->first()?->url
            ?? 'https://picsum.photos/seed/kolam-'.$this->getKey().'/1200/800';
    }

    /**
     * Label jenis properti untuk tampilan.
     */
    public function propertyTypeLabel(): string
    {
        return match ($this->property_type) {
            'rumah' => 'Rumah',
            'villa' => 'Villa',
            'hotel' => 'Hotel',
            default => ucfirst((string) $this->property_type),
        };
    }
}
