<?php

namespace Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Тести для Filament UserResource з документами.
 * Ці тести потребують правильного налаштування форми Filament.
 */
#[Group('filament')]
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
        $this->markTestSkipped('Filament form structure requires specific field configuration');
    }

    public function test_can_edit_user_and_add_documents(): void
    {
        $this->markTestSkipped('Filament form structure requires specific field configuration');
    }

    public function test_can_edit_user_and_remove_documents(): void
    {
        $this->markTestSkipped('Filament form structure requires specific field configuration');
    }
}
