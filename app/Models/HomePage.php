<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class HomePage extends Model
{
    /** @use HasFactory<\Database\Factories\HomePageFactory> */
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
        'hero_video_url',
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
}
