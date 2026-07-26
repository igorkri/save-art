<?php

namespace App\Models;

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
 * @property bool $is_legal
 * @property string $code
 * @property string $slug
 * @property string $status
 * @property string $status_moderation
 * @property array $title
 * @property array|null $short_description
 * @property string|null $cover
 * @property array|null $tags
 * @property int|null $art_category_id
 * @property string $currency
 * @property float $budget_goal
 * @property float $budget_collected
 * @property int|null $estimated_days
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
 * @property-read \App\Models\ArtCategory|null $artCategory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectStage[] $stages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectBonus[] $bonuses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Donation[] $donations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectLike[] $likes
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectParameter[] $projectParameters
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'is_legal',
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
        'art_category_id',
        'currency',
        'budget_goal',
        'budget_collected',
        'estimated_days',
        'budget_items',
        'additional_info',
        'content_blocks',
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
            'budget_items' => 'array',
            'additional_info' => 'array',
            'content_blocks' => 'array',
            'final_result' => 'array',
            'budget_goal' => 'decimal:2',
            'budget_collected' => 'decimal:2',
            'is_legal' => 'boolean',
            'status' => ProjectStatus::class,
            'status_moderation' => ModerationStatus::class,
            'user_type' => UserType::class,
            'currency' => Currency::class,
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
     * Перегенерувати slug на основі поточної назви (title.uk/title.en), замінивши
     * автогенерований при створенні плейсхолдер на змістовний. Викликається рівно один раз —
     * саме в момент переходу зі статусу New до наступного (submit на модерацію або
     * збереження в чернетку) — не при кожному збереженні.
     */
    public function regenerateSlugFromTitle(): bool
    {
        $title = is_array($this->title) ? $this->title : [];
        $titleText = $title['uk'] ?? $title['en'] ?? null;

        if (! $titleText || trim($titleText) === '') {
            return false;
        }

        $slug = Str::slug($titleText);
        $originalSlug = $slug;
        $count = 1;
        while (self::where('slug', $slug)->where($this->getKeyName(), '!=', $this->getKey())->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        $this->slug = $slug;

        return true;
    }

    /**
     * Автор проєкту
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Категорія мистецтва (з БД, корінь або підкатегорія)
     */
    public function artCategory(): BelongsTo
    {
        return $this->belongsTo(ArtCategory::class, 'art_category_id');
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
     * Звіти по проєкту
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Обрані значення характеристик (Parameter) проєкту
     */
    public function projectParameters(): HasMany
    {
        return $this->hasMany(ProjectParameter::class);
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
     * Чи можна редагувати проєкт повністю
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Чи можна редагувати проєкт частково (опубліковані)
     */
    public function isPartiallyEditable(): bool
    {
        return $this->status->isPartiallyEditable();
    }

    /**
     * Завершені/продані проєкти: назву, категорію та бюджет змінювати вже не можна, але
     * додаткову інформацію (content_blocks, additional_info) та обкладинку — можна, щоб
     * митець міг, наприклад, додати відео чи посилання з результатом уже після завершення.
     */
    public function canEditAdditionalContentOnly(): bool
    {
        return in_array($this->status, [ProjectStatus::Completed, ProjectStatus::Sold], true);
    }

    /**
     * Чи може власник самостійно видалити проєкт: чернетки, відхилені,
     * а також проєкти в черзі на модерацію (поки їх не взяв в обробку модератор).
     * Проєкт, що вже проходить модерацію (status_moderation = processing) або
     * опублікований (announced/in_progress/paused/completed/sold), видаляється
     * лише через модераторів.
     */
    public function canBeDeletedByOwner(): bool
    {
        if (in_array($this->status, [ProjectStatus::Draft, ProjectStatus::Rejected])) {
            return true;
        }

        return $this->status === ProjectStatus::Moderation && $this->status_moderation === ModerationStatus::Pending;
    }

    /**
     * Чи є проєкт публічним
     */
    public function isPublic(): bool
    {
        return in_array($this->status, ProjectStatus::publicStatuses());
    }

    /**
     * Отримати назву батьківської (кореневої) категорії мистецтва.
     * Якщо обрано підкатегорію — повертає назву кореня; інакше — назву самої категорії.
     */
    public function getArtCategoryLabel(?string $language = 'uk'): ?string
    {
        if (! $this->artCategory) {
            return null;
        }

        $root = $this->artCategory->parent_id ? $this->artCategory->parent : $this->artCategory;

        return $root->getLabel($language);
    }

    /**
     * Slug галузі для API (корінь)
     */
    public function getArtCategorySlug(): ?string
    {
        return $this->artCategory?->getRootSlug() ?: null;
    }

    /**
     * Slug підкатегорії для API (null якщо обрано тільки корінь)
     */
    public function getArtSubcategorySlug(): ?string
    {
        return $this->artCategory?->getSubSlug();
    }

    /**
     * Отримати назву підкатегорії мистецтва (з підтримкою мультимовності)
     */
    public function getArtSubcategoryLabel(?string $language = 'uk'): ?string
    {
        if (! $this->artCategory || ! $this->artCategory->getSubSlug()) {
            return null;
        }

        return $this->artCategory->getLabel($language);
    }

    /**
     * Отримати кількість днів до запланованого завершення
     */
    public function getDaysLeft(): ?int
    {
        if (! $this->planned_completion_at) {
            return null;
        }

        // Якщо проєкт вже завершений - повертаємо 0
        if ($this->completed_at || in_array($this->status, [ProjectStatus::Completed, ProjectStatus::Sold])) {
            return 0;
        }

        $now = now()->startOfDay();
        $plannedDate = $this->planned_completion_at->startOfDay();

        $daysLeft = $now->diffInDays($plannedDate, false);

        // Якщо дата вже пройшла - повертаємо 0
        return max(0, (int) $daysLeft);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
