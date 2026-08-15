<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property int|null $team_id
 * @property bool $is_legal
 * @property string $code
 * @property string $slug
 * @property string $status
 * @property string $status_moderation
 * @property string $title
 * @property string|null $short_description
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
 * @property Carbon|null $announced_at
 * @property Carbon|null $planned_completion_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read ArtCategory|null $artCategory
 * @property-read Collection|ProjectStage[] $stages
 * @property-read Collection|ProjectBonus[] $bonuses
 * @property-read Collection|Donation[] $donations
 * @property-read Collection|ProjectLike[] $likes
 * @property-read Collection|ProjectParameter[] $projectParameters
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_type',
        'team_id',
        'is_legal',
        'source',
        'sold_externally',
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
            'tags' => 'array',
            'budget_items' => 'array',
            'additional_info' => 'array',
            'content_blocks' => 'array',
            'final_result' => 'array',
            'budget_goal' => 'decimal:2',
            'budget_collected' => 'decimal:2',
            'is_legal' => 'boolean',
            'sold_externally' => 'boolean',
            'status' => ProjectStatus::class,
            'status_moderation' => ModerationStatus::class,
            'user_type' => UserType::class,
            'source' => ProjectSource::class,
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

    public function setTitleAttribute(mixed $value): void
    {
        $this->attributes['title'] = $this->ukValue($value) ?? '';
    }

    public function setShortDescriptionAttribute(mixed $value): void
    {
        $this->attributes['short_description'] = $this->ukValue($value);
    }

    public function setTagsAttribute(mixed $value): void
    {
        $value = $this->ukValue($value);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if ($value !== null && ! is_array($value)) {
            $value = [$value];
        }

        if (is_array($value)) {
            $value = array_values(array_filter(
                array_map(static fn (mixed $tag): string => trim((string) $tag), $value),
                static fn (string $tag): bool => $tag !== '',
            ));
        }

        $this->attributes['tags'] = $value === null
            ? null
            : json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function setBudgetItemsAttribute(mixed $value): void
    {
        $this->attributes['budget_items'] = $this->encodeNestedUkFields($value, ['name']);
    }

    public function setContentBlocksAttribute(mixed $value): void
    {
        $this->attributes['content_blocks'] = $this->encodeNestedUkFields($value, [
            'heading_text',
            'paragraph_text',
            'image_alt',
            'image_caption',
        ]);
    }

    private function ukValue(mixed $value): mixed
    {
        return is_array($value) && (array_key_exists('uk', $value) || array_key_exists('en', $value))
            ? ($value['uk'] ?? null)
            : $value;
    }

    /**
     * @param  list<string>  $keys
     */
    private function encodeNestedUkFields(mixed $value, array $keys): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach ($value as &$item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ($keys as $key) {
                if (array_key_exists($key, $item)) {
                    $item[$key] = $this->ukValue($item[$key]);
                }
            }
        }
        unset($item);

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Генерація slug з назви
     */
    public static function generateSlugFromTitle(string $title): string
    {
        $slug = Str::slug($title) ?: Str::random(10);

        $count = 1;
        $originalSlug = $slug;
        while (self::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Перегенерувати slug на основі поточної назви, замінивши
     * автогенерований при створенні плейсхолдер на змістовний. Викликається рівно один раз —
     * саме в момент переходу зі статусу New до наступного (submit на модерацію або
     * збереження в чернетку) — не при кожному збереженні.
     */
    public function regenerateSlugFromTitle(): bool
    {
        if (trim($this->title) === '') {
            return false;
        }

        $slug = Str::slug($this->title);
        $originalSlug = $slug;
        $count = 1;
        while (self::withTrashed()->where('slug', $slug)->where($this->getKeyName(), '!=', $this->getKey())->exists()) {
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
     * Команда-власник проєкту (якщо user_type = team)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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
     * Проєкти, створені через art-ua-info, не мають показуватись на save-art
     * (окремий фронтенд/флоу без бюджету, етапів тощо).
     */
    public function scopeForSaveArt(Builder $query): Builder
    {
        return $query->where('source', ProjectSource::SaveArt);
    }

    /**
     * Проєкти, створені через art-ua-info (окремий флоу без бюджету, етапів тощо).
     */
    public function scopeForArtUaInfo(Builder $query): Builder
    {
        return $query->where('source', ProjectSource::ArtUaInfo);
    }

    /**
     * Проєкти, що належать особисто користувачу, а не команді (team_id порожній).
     * Використовується на публічному профілі автора, щоб командні проєкти
     * (є окрема публічна сторінка команди) не дублювались у портфоліо творця.
     */
    public function scopeOwnedIndividually(Builder $query): Builder
    {
        return $query->whereNull('team_id');
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
