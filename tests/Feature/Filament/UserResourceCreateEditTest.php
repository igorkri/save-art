<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Тести для створення та редагування користувачів через Filament UserResource
 */
#[Group('filament')]
class UserResourceCreateEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Створюємо адміністратора для доступу до Filament
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@example.com',
        ]);
        $this->actingAs($admin);
    }

    public function test_can_create_user_with_legal_profile(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'full_name' => ['uk' => 'Test User', 'en' => 'Test User'],
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => UserRole::User->value,
                'profession' => ['uk' => 'Художник', 'en' => 'Artist'],
                'profileLegal' => [
                    'currency' => 'UAH',
                    'is_legal' => false,
                    'name' => ['uk' => 'ФОП Тест', 'en' => 'PE Test'],
                    'authorized_person' => ['uk' => 'Іван Іванов', 'en' => 'Ivan Ivanov'],
                    'address' => ['uk' => 'вул. Тестова, 1', 'en' => 'Test St., 1'],
                    'phone' => '+380123456789',
                    'email' => 'legal@example.com',
                    'edrpou' => '12345678',
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        // Перевіряємо, що користувач створений
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->full_name['uk']);

        // Перевіряємо, що створено профілі
        $this->assertNotNull($user->profileLegal);

        // Перевіряємо JSON поля
        $this->assertEquals(['uk' => 'ФОП Тест', 'en' => 'PE Test'], $user->profileLegal->name);
        $this->assertEquals(['uk' => 'Іван Іванов', 'en' => 'Ivan Ivanov'], $user->profileLegal->authorized_person);
        $this->assertEquals(['uk' => 'вул. Тестова, 1', 'en' => 'Test St., 1'], $user->profileLegal->address);
    }

    public function test_can_edit_user_with_legal_profile(): void
    {
        // Створюємо користувача
        $user = User::factory()->create([
            'full_name' => ['uk' => 'Original User', 'en' => 'Original User'],
            'email' => 'original@example.com',
            'role' => UserRole::User,
        ]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'full_name' => ['uk' => 'Updated User', 'en' => 'Updated User'],
                'email' => 'updated@example.com',
                'role' => UserRole::User->value,
                'profileLegal' => [
                    'currency' => 'USD',
                    'is_legal' => true,
                    'name' => ['uk' => 'ТОВ Новий', 'en' => 'LLC New'],
                    'authorized_person' => ['uk' => 'Петро Петров', 'en' => 'Petro Petrov'],
                    'address' => ['uk' => 'вул. Нова, 2', 'en' => 'New St., 2'],
                    'phone' => '+380987654321',
                    'email' => 'new@example.com',
                    'edrpou' => '87654321',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Перевіряємо, що користувач оновлений
        $user->refresh();
        $this->assertEquals('Updated User', $user->full_name['uk']);
        $this->assertEquals('updated@example.com', $user->email);

        // Перевіряємо, що профіль створено/оновлено
        $this->assertNotNull($user->profileLegal);
        $this->assertEquals(['uk' => 'ТОВ Новий', 'en' => 'LLC New'], $user->profileLegal->name);
        $this->assertEquals(['uk' => 'Петро Петров', 'en' => 'Petro Petrov'], $user->profileLegal->authorized_person);
        $this->assertEquals(['uk' => 'вул. Нова, 2', 'en' => 'New St., 2'], $user->profileLegal->address);
    }
}
