<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SiteSettings extends Model
{
    use HasTranslations;

    protected $table = 'site_settings';

    protected $fillable = [
        'site_logo',
        // Header
        'header_brand_name',
        'header_dropdown_sites',
        'header_menu',
        'header_socials',
        'header_support_button_url',
        'header_support_button_text',
        'header_login_button_text',
        // Footer Top
        'footer_brand_name',
        'footer_slogan',
        'footer_collaboration_title',
        'footer_collaboration_text',
        'footer_collaboration_items',
        'footer_collaboration_button_text',
        // Footer Middle
        'footer_sites_menu',
        // Footer Bottom
        'footer_company_name',
        'footer_address',
        'footer_email',
        'footer_phone',
        'footer_social_links',
        'footer_copyright_year',
    ];

    /** @var array<int, string> */
    public array $translatable = [
        'header_brand_name',
        'header_support_button_text',
        'header_login_button_text',
        'footer_brand_name',
        'footer_slogan',
        'footer_collaboration_title',
        'footer_collaboration_text',
        'footer_collaboration_button_text',
        'footer_company_name',
        'footer_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_brand_name' => 'array',
            'header_dropdown_sites' => 'array',
            'header_menu' => 'array',
            'header_socials' => 'array',
            'header_support_button_text' => 'array',
            'header_login_button_text' => 'array',
            'footer_brand_name' => 'array',
            'footer_slogan' => 'array',
            'footer_collaboration_title' => 'array',
            'footer_collaboration_text' => 'array',
            'footer_collaboration_items' => 'array',
            'footer_collaboration_button_text' => 'array',
            'footer_sites_menu' => 'array',
            'footer_company_name' => 'array',
            'footer_address' => 'array',
            'footer_social_links' => 'array',
        ];
    }
}
