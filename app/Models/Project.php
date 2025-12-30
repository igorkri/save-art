<?php

namespace App\Models;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $user_type
 * @property string $code
 * @property string $slug
 * @property string $status
 * @property string $status_moderation
 * @property array $title
 * @property array|null $short_description
 * @property string|null $cover
 * @property array|null $tags
 * @property string|null $art_category
 * @property string|null $art_subcategory
 * @property string $currency
 * @property float $budget_goal
 * @property float $budget_collected
 * @property int|null $estimated_days
 * @property array|null $characteristics
 * @property array|null $budget_items
 * @property array|null $additional_info
 * @property array|null $final_result
 * @property int $likes_count
 * @property int $donors_count
 * @property \Carbon\Carbon|null $announced_at
 * @property \Carbon\Carbon|null $planned_completion_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectStage[] $stages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectBonus[] $bonuses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Donation[] $donations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectLike[] $likes
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'code',
        'slug',
        'status',
        'status_moderation',
        'rejection_reason',
        'moderation_comment',
        'title',
        'short_description',
        'cover',
        'tags',
        'art_category',
        'art_subcategory',
        'currency',
        'budget_goal',
        'budget_collected',
        'estimated_days',
        'characteristics',
        'budget_items',
        'additional_info',
        'final_result',
        'likes_count',
        'donors_count',
        'announced_at',
        'planned_completion_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'short_description' => 'array',
            'tags' => 'array',
            'characteristics' => 'array',
            'budget_items' => 'array',
            'additional_info' => 'array',
            'final_result' => 'array',
            'budget_goal' => 'decimal:2',
            'budget_collected' => 'decimal:2',
            'status' => ProjectStatus::class,
            'status_moderation' => ModerationStatus::class,
            'user_type' => UserType::class,
            'currency' => Currency::class,
            'art_category' => ArtCategory::class,
            'announced_at' => 'datetime',
            'planned_completion_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Boot метод для автоматичного генерування коду та slug
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $project) {
            if (empty($project->code)) {
                $project->code = self::generateUniqueCode();
            }
            if (empty($project->slug)) {
                $project->slug = self::generateSlugFromTitle($project->title);
            }
        });
    }

    /**
     * Генерація унікального коду проєкту
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Генерація slug з назви
     *
     * @param  array<string, string>  $title
     */
    public static function generateSlugFromTitle(array $title): string
    {
        $titleText = $title['uk'] ?? $title['en'] ?? Str::random(10);
        $slug = Str::slug($titleText);

        $count = 1;
        $originalSlug = $slug;
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Автор проєкту
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Етапи реалізації проєкту
     */
    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStage::class)->orderBy('order');
    }

    /**
     * Бонуси для меценатів
     */
    public function bonuses(): HasMany
    {
        return $this->hasMany(ProjectBonus::class)->orderBy('order');
    }

    /**
     * Донати на проєкт
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Лайки проєкту
     */
    public function likes(): HasMany
    {
        return $this->hasMany(ProjectLike::class);
    }

    /**
     * Прогрес збору коштів у відсотках
     */
    public function getProgressPercentage(): float
    {
        if ($this->budget_goal <= 0) {
            return 0;
        }

        return min(100, ($this->budget_collected / $this->budget_goal) * 100);
    }

    /**
     * Чи може проєкт приймати донати
     */
    public function canReceiveDonations(): bool
    {
        return $this->status->canReceiveDonations();
    }

    /**
     * Чи можна редагувати проєкт
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Чи є проєкт публічним
     */
    public function isPublic(): bool
    {
        return in_array($this->status, ProjectStatus::publicStatuses());
    }

    /**
     * Отримати назву категорії мистецтва
     */
    public function getArtCategoryLabel(): ?string
    {
        return $this->art_category?->getLabel();
    }

    /**
     * Отримати назву підкатегорії мистецтва
     */
    public function getArtSubcategoryLabel(): ?string
    {
        if (! $this->art_category || ! $this->art_subcategory) {
            return null;
        }

        $subcategories = $this->art_category->getSubcategories();

        return $subcategories[$this->art_subcategory] ?? null;
    }
}
