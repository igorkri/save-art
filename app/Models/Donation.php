<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $user_id
 * @property int|null $project_bonus_id
 * @property string $donation_type
 * @property float $amount
 * @property string $currency
 * @property float|null $amount_usd
 * @property string $donor_type
 * @property bool $is_anonymous
 * @property bool $is_public
 * @property string|null $donor_name
 * @property string|null $donor_email
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $payment_id
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\ProjectBonus|null $bonus
 */
class Donation extends Model
{
    use HasFactory;

    public const TYPE_PROJECT = 'project';

    public const TYPE_PLATFORM = 'platform';

    protected $fillable = [
        'project_id',
        'user_id',
        'project_bonus_id',
        'donation_type',
        'amount',
        'currency',
        'amount_usd',
        'donor_type',
        'is_anonymous',
        'is_public',
        'donor_name',
        'donor_email',
        'status',
        'payment_method',
        'payment_id',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_usd' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
            'currency' => Currency::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Проєкт, на який зроблено донат
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Користувач, який зробив донат
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Бонус, обраний донатером
     */
    public function bonus(): BelongsTo
    {
        return $this->belongsTo(ProjectBonus::class, 'project_bonus_id');
    }

    /**
     * Чи оплачено донат
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Чи очікує на оплату
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Ім'я донатера для відображення
     *
     * Для анонімних донатів (в т.ч. від авторизованих користувачів) показуємо
     * псевдонім (donor_name), а не справжнє ім'я з акаунту — інакше анонімність втрачає сенс.
     */
    public function getDisplayName(): string
    {
        if ($this->is_anonymous) {
            return $this->donor_name ?: 'Анонім';
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->donor_name ?? 'Невідомий';
    }

    /**
     * Чи це донат на платформу
     */
    public function isPlatformDonation(): bool
    {
        return $this->donation_type === self::TYPE_PLATFORM;
    }

    /**
     * Чи це донат на проект
     */
    public function isProjectDonation(): bool
    {
        return $this->donation_type === self::TYPE_PROJECT;
    }
}
