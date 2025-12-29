<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 */
class ProjectLike extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
    ];

    /**
     * Проєкт, який лайкнули
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Користувач, який поставив лайк
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
