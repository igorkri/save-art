<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProfileLegal
 *
 * @property int $id ID запису
 * @property int $user_id ID користувача
 * @property bool $is_active Чи активний юридичний профіль
 * @property string $currency Валюта за замовчуванням (ISO 4217 currency code)
 * @property string|null $logo Логотип компанії
 * @property string $name Назва компанії або ПІБ приватної особи
 * @property string|null $edrpou ЄДРПОУ
 * @property string|null $authorized_person Уповноважена особа
 * @property string|null $address Адреса
 * @property string|null $phone Телефон
 * @property string|null $email Email
 * @property \Illuminate\Support\Carbon|null $created_at Дата створення запису
 * @property \Illuminate\Support\Carbon|null $updated_at Дата останнього оновлення запису
 * @property-read \App\Models\User $user
 */
class ProfileLegal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_active',
        'currency',
        'logo',
        'name',
        'edrpou',
        'authorized_person',
        'address',
        'phone',
        'email',
    ];

    protected $casts = [
        'currency' => Currency::class,
        'is_active' => 'boolean',
    ];

    // Визначення зв'язку з користувачем
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить название валюты (локализованное).
     */
    public function getCurrencyNameAttribute(): string
    {
        return match ($this->currency) {
            Currency::UAH => 'Гривня',
            Currency::USD => 'Долар',
            Currency::EUR => 'Євро',
            default => $this->currency->value,
        };
    }

    /**
     * Получить путь к иконке валюты.
     */
    public function getCurrencyIconAttribute(): string
    {
        return $this->currency?->icon() ?? '';
    }

    /**
     * Получить строковый код валюты.
     */
    public function getCurrencyCodeAttribute(): string
    {
        return $this->currency?->value ?? '';
    }

    /**
     * Получить название компании/ФИО.
     */
    public function getNameCurrentAttribute(): ?string
    {
        return $this->name;
    }

    /**
     * Получить адрес.
     */
    public function getAddressCurrentAttribute(): ?string
    {
        return $this->address;
    }

    /**
     * Получить уполномоченное лицо.
     */
    public function getAuthorizedPersonCurrentAttribute(): ?string
    {
        return $this->authorized_person;
    }
}
