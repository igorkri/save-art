<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Api\V1\Auth\SocialAuthController as BaseSocialAuthController;

/**
 * Google OAuth для art-ua-info — той самий флоу (find-or-create за email,
 * profile_type=Artist за замовчуванням, завантаження аватара), що й у save-art
 * (App\Http\Controllers\Api\V1\Auth\SocialAuthController). Відрізняється лише
 * redirect_uri, тому лише перевизначає googleRedirectUri() замість дублювання
 * всієї логіки.
 */
class SocialAuthController extends BaseSocialAuthController
{
    protected function googleRedirectUri(): ?string
    {
        return config('services.google.redirect_art_ua_info');
    }
}
