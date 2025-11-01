<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ProfileDocument;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_create_user_with_documents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        // Создаём тестовые файлы
        $file1 = UploadedFile::fake()->create('document1.pdf', 100);
        $file2 = UploadedFile::fake()->create('document2.pdf', 100);

        // Загружаем файлы
        $path1 = $file1->store('profile_documents', 'public');
        $path2 = $file2->store('profile_documents', 'public');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => UserRole::User->value,
                'profileDocuments' => [$path1, $path2],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Проверяем, что пользователь создан
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // Проверяем, что документы сохранены
        $this->assertCount(2, $user->profileDocuments);
        $this->assertTrue($user->profileDocuments->pluck('file_path')->contains($path1));
        $this->assertTrue($user->profileDocuments->pluck('file_path')->contains($path2));

        // Проверяем, что файлы существуют
        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);
    }

    public function test_can_edit_user_and_add_documents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $user = User::factory()->create();

        // Создаём новый документ
        $file = UploadedFile::fake()->create('new_document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'profileDocuments' => [$path],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Проверяем, что документ добавлен
        $user->refresh();
        $this->assertCount(1, $user->profileDocuments);
        $this->assertEquals($path, $user->profileDocuments->first()->file_path);
    }

    public function test_can_edit_user_and_remove_documents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $user = User::factory()->create();

        // Создаём документ для пользователя
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        $document = ProfileDocument::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'hash' => hash_file('sha256', Storage::disk('public')->path($path)),
            'sign_status' => 'unsigned',
            'service' => 'manual_upload',
        ]);

        // Проверяем, что документ создан
        $this->assertCount(1, $user->profileDocuments);

        // Редактируем пользователя и удаляем документы
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'profileDocuments' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Проверяем, что документ удалён
        $user->refresh();
        $this->assertCount(0, $user->profileDocuments);

        // Проверяем, что файл удалён с диска
        Storage::disk('public')->assertMissing($path);
    }
}
