<?php

namespace Tests\Feature\Api\V1\ArtUaInfo;

use App\Enums\ProfileType;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class RegisterControllerTest extends ApiTestCase
{
    public function test_registration_defaults_to_artist_profile_type(): void
    {
        $response = $this->apiPost('/api/v1/art-ua-info/auth/register', [
            'name' => 'Художник Тест',
            'email' => 'art-ua-info-artist@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'art-ua-info-artist@example.com')->firstOrFail();

        // art-ua-info — платформа лише для митців, і власного кроку вибору ролі
        // тут немає, тож profile_type проставляється одразу при реєстрації.
        $this->assertSame(ProfileType::Artist, $user->profile_type);
        $this->assertSame('Художник Тест', $user->full_name);
    }

    public function test_save_art_registration_also_defaults_to_artist_profile_type(): void
    {
        $response = $this->apiPost('/api/v1/auth/register', [
            'name' => 'Save Art User',
            'email' => 'save-art-user@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'save-art-user@example.com')->firstOrFail();

        // Api\V1\Auth\RegisterController (окремий контролер від art-ua-info) теж
        // проставляє Artist за замовчуванням — без цього доступ до Filament-панелі
        // "profile" (User::canAccessPanel, лише isArtist()) впирався в 403 ще до
        // того, як користувач встигав обрати роль на /choose-role. Хто хоче бути
        // меценатом — міняє тип профілю через /choose-role (updateUserType).
        $this->assertSame(ProfileType::Artist, $user->profile_type);
    }
}
