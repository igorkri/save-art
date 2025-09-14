<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_roles_can_access_filament_panel(): void
    {
        // Тестируем доступ для Developer
        $developer = User::factory()->developer()->create();
        $this->actingAs($developer)
            ->get('/admin')
            ->assertStatus(200);

        // Тестируем доступ для Admin
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200);

        // Тестируем доступ для Moderator
        $moderator = User::factory()->moderator()->create();
        $this->actingAs($moderator)
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_non_admin_roles_cannot_access_filament_panel(): void
    {
        // Тестируем запрет доступа для обычного пользователя
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);

        // Тестируем запрет доступа для Mecenat
        $mecenat = User::factory()->mecenat()->create();
        $this->actingAs($mecenat)
            ->get('/admin')
            ->assertStatus(403);

        // Тестируем запрет доступа для Owner
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_filament_login_page_is_accessible(): void
    {
        $this->get('/admin/login')
            ->assertStatus(200)
            ->assertSee('Войти'); // Проверяем наличие кнопки входа
    }
}
