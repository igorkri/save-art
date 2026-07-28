<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $art_catalog_id
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\ArtCatalog $catalog
 * @property-read \App\Models\User $user
 */
class ArtCatalogLike extends Model
{
    protected $fillable = [
        'art_catalog_id',
        'user_id',
    ];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ArtCatalog::class, 'art_catalog_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
