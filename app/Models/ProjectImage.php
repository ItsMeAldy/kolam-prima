<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['project_id', 'image_url', 'path', 'sort_order'])]
class ProjectImage extends Model
{
    /**
     * Proyek induk.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * URL publik gambar. Utamakan berkas yang diunggah,
     * jatuh ke URL eksternal bila tidak ada.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }

        return $this->image_url;
    }
}
