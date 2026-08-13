<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;

class ArtCategory extends Model
{
    use HasFactory;
    use HasTreeStructure;

    protected $fillable = [
        'parent_id',
        'slug',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [];
    }

    /**
     * Повертає назву колонки для сортування в дереві.
     */
    public function getOrderKeyName(): string
    {
        return 'sort_order';
    }

    /**
     * Значення parent_id для кореневих елементів (null замість -1).
     */
    public function getParentKeyDefaultValue(): mixed
    {
        return null;
    }

    /**
     * Accessor для title — використовується filament-tree-view.
     */
    public function getTitleAttribute(): string
    {
        return $this->name ?: '';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArtCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ArtCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'art_category_id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(Parameter::class)->orderBy('sort_order');
    }

    public function getLabel(?string $language = 'uk'): string
    {
        return $this->name ?: '';
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['name'] = is_array($value) ? ($value['uk'] ?? '') : $value;
    }

    /** Slug категорії для API (корінь — свій slug, нащадок — slug батька) */
    public function getRootSlug(): string
    {
        return $this->parent_id ? ($this->parent->slug ?? '') : $this->slug;
    }

    /** Slug підкатегорії для API (null якщо це корінь) */
    public function getSubSlug(): ?string
    {
        return $this->parent_id ? $this->slug : null;
    }

    /**
     * Повернути id категорії за slug галузі та опційно підкатегорії.
     */
    public static function resolveIdFromSlugs(?string $categorySlug, ?string $subcategorySlug = null): ?int
    {
        if (! $categorySlug) {
            return null;
        }
        $root = self::whereNull('parent_id')->where('slug', $categorySlug)->first();
        if (! $root) {
            return null;
        }
        if ($subcategorySlug) {
            $child = self::where('parent_id', $root->id)->where('slug', $subcategorySlug)->first();

            return $child?->id ?? $root->id;
        }

        return $root->id;
    }
}
