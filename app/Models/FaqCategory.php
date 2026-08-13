<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Faq[] $faqs
 */
class FaqCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Питання в цій категорії
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('order');
    }

    /**
     * Scope для активних категорій
     *
     * @param  \Illuminate\Database\Eloquent\Builder<FaqCategory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<FaqCategory>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
