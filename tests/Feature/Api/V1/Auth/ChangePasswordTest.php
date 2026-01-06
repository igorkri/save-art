<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Пароль успішно змінено.',
            ]);

        // Перевіряємо, що новий пароль працює
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456', $user->password));
    }

    public function test_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'WrongPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Поточний пароль невірний.',
                'errors' => [
                    'current_password' => ['Поточний пароль невірний.'],
                ],
            ]);

        // Перевіряємо, що старий пароль все ще працює
        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123', $user->password));
    }

    public function test_cannot_change_password_without_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword456',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_cannot_change_password_with_weak_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'OldPassword123',
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_unauthenticated_user_cannot_change_password(): void
    {
        $response = $this->putJson('/api/v1/auth/change-password', [
            'current_password' => 'OldPassword123',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $response->assertUnauthorized();
    }
}
