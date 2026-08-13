<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_dropdown_sites' => 'array',
            'header_menu' => 'array',
            'header_socials' => 'array',
            'footer_collaboration_items' => 'array',
            'footer_sites_menu' => 'array',
            'footer_social_links' => 'array',
        ];
    }
}
