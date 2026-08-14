<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    public function test_filament_uses_uploaded_user_avatar(): void
    {
        Storage::fake('public');

        $user = new User(['avatar' => 'avatars/artist.jpg']);

        $this->assertStringEndsWith('/storage/avatars/artist.jpg', $user->getFilamentAvatarUrl());
    }

    public function test_filament_keeps_external_avatar_url(): void
    {
        $user = new User(['avatar' => 'https://example.com/artist.jpg']);

        $this->assertSame('https://example.com/artist.jpg', $user->getFilamentAvatarUrl());
    }

    public function test_filament_falls_back_to_initials_without_avatar(): void
    {
        $user = new User;

        $this->assertNull($user->getFilamentAvatarUrl());
    }
}
