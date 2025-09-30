<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Currency;

/**
 * App\Models\ProfileLegal
 *
 * @property int $id ID запису
 * @property int $user_id ID користувача
 * @property string $currency Валюта за замовчуванням (ISO 4217 currency code)
 * @property bool $is_legal Признак приватної особи або юридичної
 * @property string|null $logo Логотип компанії
 * @property array $name Назва компанії або ПІБ приватної особи
 * @property string|null $edrpou ЄДРПОУ
 * @property array|null $authorized_person Уповноважена особа
 * @property array|null $address Адреса
 * @property string|null $phone Телефон
 * @property string|null $email Email
 * @property \Illuminate\Support\Carbon|null $created_at Дата створення запису
 * @property \Illuminate\Support\Carbon|null $updated_at Дата останнього оновлення запис
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereAuthorizedPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereEdrpou($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereIsLegal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileLegal whereEmail($value)
 */
class ProfileLegal extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'is_legal',
        'logo',
        'name',
        'edrpou',
        'authorized_person',
        'address',
        'phone',
        'email',
    ];

    protected $casts = [
        'is_legal' => 'boolean',
        'name' => 'array',
        'authorized_person' => 'array',
        'address' => 'array',
        'currency' => Currency::class,
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
     * Получить название компании/ФИО на текущем языке.
     */
    public function getNameCurrentAttribute(): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');
        $name = $this->name;
        if (is_array($name)) {
            return $name[$locale] ?? $name[$fallback] ?? null;
        }
        return $name;
    }

    /**
     * Получить адрес на текущем языке.
     */
    public function getAddressCurrentAttribute(): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');
        $address = $this->address;
        if (is_array($address)) {
            return $address[$locale] ?? $address[$fallback] ?? null;
        }
        return $address;
    }

    /**
     * Получить уполномоченное лицо на текущем языке.
     */
    public function getAuthorizedPersonCurrentAttribute(): ?string
    {
        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale');
        $person = $this->authorized_person;
        if (is_array($person)) {
            return $person[$locale] ?? $person[$fallback] ?? null;
        }
        return $person;
    }
}
