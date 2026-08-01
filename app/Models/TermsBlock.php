<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $terms_section_id
 * @property array|null $heading
 * @property array|null $paragraphs
 * @property string|null $list_type
 * @property array|null $list_items
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\TermsSection $section
 */
class TermsBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'terms_section_id',
        'heading',
        'paragraphs',
        'list_type',
        'list_items',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'heading' => 'array',
            'paragraphs' => 'array',
            'list_items' => 'array',
        ];
    }

    /**
     * Розділ, якому належить блок
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(TermsSection::class, 'terms_section_id');
    }
}
