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
            ->set('data.full_name', 'Test User')
            ->set('data.email', 'test@example.com')
            ->set('data.password', 'password123')
            ->set('data.role', UserRole::User->value)
            ->set('data.profession', 'Художник')
            ->set('data.profileLegal.currency', 'UAH')
            ->set('data.profileLegal.is_legal', false)
            ->set('data.profileLegal.name', 'ФОП Тест')
            ->set('data.profileLegal.authorized_person', 'Іван Іванов')
            ->set('data.profileLegal.address', 'вул. Тестова, 1')
            ->set('data.profileLegal.phone', '+380123456789')
            ->set('data.profileLegal.email', 'legal@example.com')
            ->set('data.profileLegal.edrpou', '12345678')
            ->call('create')
            ->assertHasNoErrors();

        // Перевіряємо, що користувач створений
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->full_name);

        // Перевіряємо, що створено профілі
        $this->assertNotNull($user->profileLegal);

        // Перевіряємо поля юридичного профілю
        $this->assertEquals('ФОП Тест', $user->profileLegal->name);
        $this->assertEquals('Іван Іванов', $user->profileLegal->authorized_person);
        $this->assertEquals('вул. Тестова, 1', $user->profileLegal->address);
    }

    public function test_can_edit_user_with_legal_profile(): void
    {
        // Створюємо користувача
        $user = User::factory()->create([
            'full_name' => 'Original User',
            'email' => 'original@example.com',
            'role' => UserRole::User,
        ]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->set('data.full_name', 'Updated User')
            ->set('data.email', 'updated@example.com')
            ->set('data.role', UserRole::User->value)
            ->set('data.profileLegal.currency', 'USD')
            ->set('data.profileLegal.is_legal', true)
            ->set('data.profileLegal.name', 'ТОВ Новий')
            ->set('data.profileLegal.authorized_person', 'Петро Петров')
            ->set('data.profileLegal.address', 'вул. Нова, 2')
            ->set('data.profileLegal.phone', '+380987654321')
            ->set('data.profileLegal.email', 'new@example.com')
            ->set('data.profileLegal.edrpou', '87654321')
            ->call('save')
            ->assertHasNoErrors();

        // Перевіряємо, що користувач оновлений
        $user->refresh();
        $this->assertEquals('Updated User', $user->full_name);
        $this->assertEquals('updated@example.com', $user->email);

        // Перевіряємо, що профіль створено/оновлено
        $this->assertNotNull($user->profileLegal);
        $this->assertEquals('ТОВ Новий', $user->profileLegal->name);
        $this->assertEquals('Петро Петров', $user->profileLegal->authorized_person);
        $this->assertEquals('вул. Нова, 2', $user->profileLegal->address);
    }
}
