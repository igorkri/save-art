<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property array $title
 * @property array|null $description
 * @property string|null $cover
 * @property array|null $images
 * @property array|null $attachments
 * @property float $collected_amount
 * @property float $spent_amount
 * @property \Carbon\Carbon $report_date
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'description',
        'cover',
        'images',
        'attachments',
        'collected_amount',
        'spent_amount',
        'report_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'images' => 'array',
            'attachments' => 'array',
            'collected_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'report_date' => 'date',
        ];
    }

    /**
     * Проєкт, до якого відноситься звіт
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Автор звіту
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope для публічних звітів
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Report>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Report>
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
