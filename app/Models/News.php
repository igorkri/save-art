<?php

namespace App\Models;

use App\Enums\NewsCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Клас News — новини та події для інформаційного порталу (art-ua-info).
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property NewsCategory $category
 * @property \Carbon\Carbon|null $published_at
 * @property string|null $main_image
 * @property array|null $text_blocks Список блоків виду [['paragraphs' => string[], 'image' => string|null], ...]
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'category',
        'published_at',
        'main_image',
        'text_blocks',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NewsCategory::class,
            'published_at' => 'datetime',
            'text_blocks' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot метод для автоматичного генерування slug
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (News $news) {
            if (empty($news->slug)) {
                $news->slug = self::generateSlugFromTitle((string) $news->title);
            }
        });
    }

    /**
     * Генерація унікального slug з назви
     */
    public static function generateSlugFromTitle(string $title): string
    {
        $titleText = $title !== '' ? $title : Str::random(10);
        $slug = Str::slug($titleText);

        $count = 1;
        $originalSlug = $slug;
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Scope для активних новин
     *
     * @param  \Illuminate\Database\Eloquent\Builder<News>  $query
     * @return \Illuminate\Database\Eloquent\Builder<News>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для фільтрації за категорією
     *
     * @param  \Illuminate\Database\Eloquent\Builder<News>  $query
     * @return \Illuminate\Database\Eloquent\Builder<News>
     */
    public function scopeOfCategory($query, NewsCategory|string $category)
    {
        $value = $category instanceof NewsCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
