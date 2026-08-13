<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Тести для Filament ProjectResource - особливо для таблиці з пошуком
 */
#[Group('filament')]
class ProjectResourceListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Створюємо адміністратора для доступу до Filament
        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_can_list_projects_and_search_by_author_name(): void
    {
        // Створюємо користувачів з повними іменами
        $user1 = User::factory()->create([
            'full_name' => 'Олег Коваленко',
        ]);

        $user2 = User::factory()->create([
            'full_name' => 'Марія Петренко',
        ]);

        // Створюємо проекти для кожного користувача
        $project1 = Project::factory()->create([
            'user_id' => $user1->id,
            'title' => [
                'uk' => 'Картина "Світанок над Дніпром"',
                'en' => 'Painting "Sunrise over the Dnieper"',
            ],
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $user2->id,
            'title' => [
                'uk' => 'Скульптура "Природа"',
                'en' => 'Sculpture "Nature"',
            ],
        ]);

        $this->actingAs($this->admin);

        // Тест 1: Перегляд всіх проектів
        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords([$project1, $project2]);

        // Тест 2: Пошук за назвою проекту
        Livewire::test(ListProjects::class)
            ->searchTable('Картина')
            ->assertCanSeeTableRecords([$project1])
            ->assertCanNotSeeTableRecords([$project2]);

        // Тест 3: Пошук за іменем автора (українська мова)
        Livewire::test(ListProjects::class)
            ->searchTable('Олег')
            ->assertCanSeeTableRecords([$project1])
            ->assertCanNotSeeTableRecords([$project2]);

        // Тест 4: Пошук за іменем автора (частини імені)
        Livewire::test(ListProjects::class)
            ->searchTable('Коваленко')
            ->assertCanSeeTableRecords([$project1])
            ->assertCanNotSeeTableRecords([$project2]);

        // Тест 5: Пошук за іменем другого автора
        Livewire::test(ListProjects::class)
            ->searchTable('Марія')
            ->assertCanSeeTableRecords([$project2])
            ->assertCanNotSeeTableRecords([$project1]);
    }

    public function test_search_by_multiple_keywords(): void
    {
        $user = User::factory()->create([
            'full_name' => 'Іван Бондаренко',
        ]);

        Project::factory(3)->create(['user_id' => $user->id]);

        $this->actingAs($this->admin);

        // Пошук повинен працювати без помилок SQL
        Livewire::test(ListProjects::class)
            ->searchTable('Картина')
            ->searchTable('Іван')
            ->searchTable('Бондаренко');
    }

    public function test_table_renders_without_errors(): void
    {
        // Просто переконуємося, що таблиця завантажується без помилок SQL
        Project::factory(5)->create();

        $this->actingAs($this->admin);

        Livewire::test(ListProjects::class)
            ->assertStatus(200);
    }
}
