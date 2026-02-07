<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Додаємо нові поля в таблицю users
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('slug')->comment('Аватар користувача');
            $table->json('full_name')->nullable()->after('avatar')->comment('Повне ім\'я (мультимовне)');
            $table->json('profession')->nullable()->after('full_name')->comment('Професія (мультимовне)');
            $table->json('tags')->nullable()->after('profession')->comment('Теги (мультимовне)');
            $table->json('country')->nullable()->after('tags')->comment('Країна (мультимовне)');
            $table->json('region')->nullable()->after('country')->comment('Область/регіон (мультимовне)');
            $table->json('city')->nullable()->after('region')->comment('Місто (мультимовне)');
            $table->string('postal_code')->nullable()->after('city')->comment('Поштовий індекс');
            $table->string('profile_type')->nullable()->after('postal_code')->comment('Тип профілю: artist або patron');
            $table->json('description')->nullable()->after('profile_type')->comment('Опис/біографія (мультимовне)');
        });

        // 2. Переносимо дані з profile_personals в users (сумісно з SQLite та MySQL)
        if (Schema::hasTable('profile_personals')) {
            $profiles = DB::table('profile_personals')->get();

            foreach ($profiles as $profile) {
                $profileType = match ($profile->role ?? null) {
                    'owner' => 'artist',
                    'mecenat' => 'patron',
                    default => $profile->role,
                };

                DB::table('users')
                    ->where('id', $profile->user_id)
                    ->update([
                        'avatar' => $profile->avatar,
                        'full_name' => $profile->full_name,
                        'profession' => $profile->profession,
                        'tags' => $profile->tags,
                        'country' => $profile->country,
                        'region' => $profile->region,
                        'city' => $profile->city,
                        'postal_code' => $profile->postal_code,
                        'profile_type' => $profileType,
                        'description' => $profile->description,
                    ]);
            }

            // 3. Конвертуємо role: owner/mecenat → user
            DB::table('users')
                ->whereIn('role', ['owner', 'mecenat'])
                ->update(['role' => 'user']);

            // 4. Видаляємо таблицю profile_personals
            Schema::dropIfExists('profile_personals');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Відновлюємо таблицю profile_personals
        Schema::create('profile_personals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID користувача');
            $table->string('avatar')->nullable()->comment('Аватар користувача');
            $table->json('full_name')->nullable()->comment('Ім\'я та прізвище користувача');
            $table->json('profession')->nullable()->comment('Професія користувача');
            $table->json('tags')->nullable()->comment('Теги користувача');
            $table->json('country')->nullable()->comment('Країна користувача');
            $table->json('region')->nullable()->comment('Область користувача');
            $table->json('city')->nullable()->comment('Місто користувача');
            $table->string('postal_code')->nullable()->comment('Поштовий індекс користувача');
            $table->string('role')->nullable()->comment('Перевага ролі користувача (меценат або творець)');
            $table->json('description')->nullable()->comment('Опис (біо) користувача');
            $table->timestamps();
        });

        // 2. Переносимо дані назад в profile_personals
        $users = DB::table('users')
            ->whereNotNull('avatar')
            ->orWhereNotNull('full_name')
            ->orWhereNotNull('profession')
            ->orWhereNotNull('profile_type')
            ->get();

        foreach ($users as $user) {
            $role = match ($user->profile_type ?? null) {
                'artist' => 'owner',
                'patron' => 'mecenat',
                default => $user->profile_type,
            };

            DB::table('profile_personals')->insert([
                'user_id' => $user->id,
                'avatar' => $user->avatar,
                'full_name' => $user->full_name,
                'profession' => $user->profession,
                'tags' => $user->tags,
                'country' => $user->country,
                'region' => $user->region,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'role' => $role,
                'description' => $user->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Видаляємо поля з users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'full_name',
                'profession',
                'tags',
                'country',
                'region',
                'city',
                'postal_code',
                'profile_type',
                'description',
            ]);
        });
    }
};
