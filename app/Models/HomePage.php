<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class HomePage extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = [
        'hero_title',
        'donates_subtitle',
        'donates_title',
        'donates_text',
        'partners_title',
        'ad_first_title',
        'ad_first_button_text',
        'ad_second_title',
        'ad_second_button_text',
        'footer_expert_title',
        'footer_expert_text',
        'footer_expert_features',
        'footer_expert_button_text',
    ];

    protected $fillable = [
        'hero_title',
        'hero_video_poster',
        'hero_video_poster_m',
        'hero_image_poster',
        'hero_image_poster_m',
        'donates_subtitle',
        'donates_title',
        'donates_text',
        'total_collected',
        'declared_projects',
        'active_projects',
        'completed_projects',
        'sold_projects',
        'partners_title',
        'ad_first_title',
        'ad_first_button_text',
        'ad_first_image',
        'ad_second_title',
        'ad_second_button_text',
        'ad_second_image',
        'footer_expert_title',
        'footer_expert_text',
        'footer_expert_features',
        'footer_expert_button_text',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hero_title' => 'array',
            'donates_subtitle' => 'array',
            'donates_title' => 'array',
            'donates_text' => 'array',
            'partners_title' => 'array',
            'ad_first_title' => 'array',
            'ad_first_button_text' => 'array',
            'ad_second_title' => 'array',
            'ad_second_button_text' => 'array',
            'footer_expert_title' => 'array',
            'footer_expert_text' => 'array',
            'footer_expert_features' => 'array',
            'footer_expert_button_text' => 'array',
            'total_collected' => 'integer',
            'declared_projects' => 'integer',
            'active_projects' => 'integer',
            'completed_projects' => 'integer',
            'sold_projects' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Отримати активну головну сторінку
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }


    // ----------------- HERO SECTION -----------------
    /**
     * Отримання выдео постера з автоопеределением типу пристрою
     * 'desktop', 'mobile'
     *
     * @return string|null
     */
    public function getHeroPosterVideo(): ?string
    {
        $device = session('device_type', 'desktop'); // 'desktop', 'tablet' або 'mobile'

        if ($device === 'mobile' && $this->hero_video_poster_m) {
            return asset('storage/' . $this->hero_video_poster_m);
        }
        if ($this->hero_video_poster) {
            return asset('storage/' . $this->hero_video_poster);
        }
        return null;
    }

    /**
     * Отримання зображення постера з автоопеределением типу пристрою
     * 'desktop', 'mobile'
     *
     * @return string|null
     */
    public function getHeroPosterImage(): ?string
    {
        $device = session('device_type', 'desktop'); // 'desktop', 'tablet' або 'mobile'
        if ($device === 'mobile' && $this->hero_image_poster_m) {
            return asset('storage/' . $this->hero_image_poster_m);
        }
        if ($this->hero_image_poster) {
            return asset('storage/' . $this->hero_image_poster);
        }
        return null;
    }

    /**
     * Отримання hero_title з автоопеределением мови
     * 'ua', 'en'
     * @return string|null
     *
     */
    public function getHeroTitle(): ?string
    {
        $locale = app()->getLocale(); // 'ua', 'en' або інші
        return $this->getTranslation('hero_title', $locale);
        // return $this->hero_title[$locale] ?? null;
    }

    // ----------------- DONATES SECTION -----------------
    /**
     * Отримання donates_subtitle з автоопеределением мови
     * 'ua', 'en'
     * @return string|null
     *
     */
    public function getDonatesSubtitle(): ?string
    {
        $locale = app()->getLocale(); // 'ua', 'en' або інші
        return $this->getTranslation('donates_subtitle', $locale);
        // return $this->donates_subtitle[$locale] ?? null;
    }

    /**
     * Отримання donates_title з автоопеределением мови
     * 'ua', 'en'
     * @return string|null
     *
     */
    public function getDonatesTitle(): ?string
    {
        $locale = app()->getLocale(); // 'ua', 'en' або інші
        return $this->getTranslation('donates_title', $locale);
        // return $this->donates_title[$locale] ?? null;
    }


    /**
     * Отримання donates_text з автоопеределением мови
     * 'ua', 'en'
     * @return string|null
     *
     */
    public function getDonatesText(): ?string
    {
        $locale = app()->getLocale(); // 'ua', 'en' або інші
        return $this->getTranslation('donates_text', $locale);
        // return $this->donates_text[$locale] ?? null;
    }




}
