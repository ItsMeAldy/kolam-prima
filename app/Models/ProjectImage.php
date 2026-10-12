<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'image_url', 'sort_order'])]
class ProjectImage extends Model
{
    /**
     * Proyek induk.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
