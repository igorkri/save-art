<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property array $title
 * @property array|null $description
 * @property float $min_donation
 * @property float|null $max_donation
 * @property int|null $quantity
 * @property int $quantity_claimed
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Donation[] $donations
 */
class ProjectBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'min_donation',
        'max_donation',
        'quantity',
        'quantity_claimed',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'min_donation' => 'decimal:2',
            'max_donation' => 'decimal:2',
        ];
    }

    /**
     * Проєкт, до якого належить бонус
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Донати, які отримали цей бонус
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Чи є бонус доступним
     */
    public function isAvailable(): bool
    {
        if ($this->quantity === null) {
            return true;
        }

        return $this->quantity_claimed < $this->quantity;
    }

    /**
     * Кількість залишкових бонусів
     */
    public function getRemaining(): ?int
    {
        if ($this->quantity === null) {
            return null;
        }

        return max(0, $this->quantity - $this->quantity_claimed);
    }
}
