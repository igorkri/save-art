<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $faq_category_id
 * @property array $question
 * @property array $answer
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\FaqCategory $category
 */
class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question' => 'array',
            'answer' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Категорія питання
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /**
     * Scope для активних питань
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Faq>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Faq>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
