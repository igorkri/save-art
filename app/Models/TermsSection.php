<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property array|null $heading
 * @property string|null $date
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TermsBlock[] $blocks
 */
class TermsSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'heading',
        'date',
        'order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'heading' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Блоки контенту цього розділу
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(TermsBlock::class)->orderBy('order');
    }

    /**
     * Scope для активних розділів
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TermsSection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TermsSection>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
