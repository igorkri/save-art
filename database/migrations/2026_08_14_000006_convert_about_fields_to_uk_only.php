<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * JSON-колонки `about`, що містять вкладені мультимовні структури
     * {"uk": ..., "en": ...} на різних рівнях вкладеності (Repeater'и,
     * вкладені об'єкти тощо). Колонка `title` сюди не входить, бо вона
     * є простою top-level мультимовною структурою (Патерн A) і
     * конвертується зі зміною типу колонки.
     *
     * @var array<int, string>
     */
    private array $nestedMultilingualColumns = [
        'feats',
        'description',
        'goals',
        'tasks',
        'implementation',
        'results',
        'id_art',
        'events',
        'project',
    ];

    /**
     * Ключі, які на будь-якому рівні вкладеності вважаються мультимовними
     * листовими полями (використовується лише для відновлення структури у down()).
     *
     * @var array<int, string>
     */
    private array $multilingualLeafKeys = [
        'title',
        'description',
        'name',
        'task',
        'title_date',
        'description_date',
        'text',
        'h2',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Патерн A: `title` — проста top-level мультимовна структура {"uk":..,"en":..}.
        Schema::table('about', function (Blueprint $table) {
            $table->string('title_uk')->nullable()->after('title');
        });

        DB::statement("UPDATE about SET title_uk = JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk')) WHERE title IS NOT NULL");

        Schema::table('about', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('about', function (Blueprint $table) {
            $table->renameColumn('title_uk', 'title');
        });

        // Патерн B: вкладені мультимовні структури всередині більших json-колонок.
        // `about` — таблиця-синглтон, тож `get()->each()` є достатнім і найнадійнішим підходом.
        DB::table('about')->get()->each(function ($row) {
            $update = [];

            foreach ($this->nestedMultilingualColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);
                $update[$column] = json_encode($this->collapseToUkrainian($decoded));
            }

            if ($update !== []) {
                DB::table('about')->where('id', $row->id)->update($update);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * УВАГА: англійський контент (en) безповоротно втрачений після up().
     * down() відновлює лише СТРУКТУРУ {"uk": ..., "en": ...}, дублюючи
     * українське значення в обидва ключі.
     */
    public function down(): void
    {
        DB::table('about')->get()->each(function ($row) {
            $update = [];

            foreach ($this->nestedMultilingualColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);
                $update[$column] = json_encode($this->restoreLanguageTabsStructure($decoded));
            }

            if ($update !== []) {
                DB::table('about')->where('id', $row->id)->update($update);
            }
        });

        Schema::table('about', function (Blueprint $table) {
            $table->json('title_json')->nullable()->after('title');
        });

        DB::table('about')->whereNotNull('title')->get()->each(function ($row) {
            DB::table('about')->where('id', $row->id)->update([
                'title_json' => json_encode(['uk' => $row->title, 'en' => $row->title]),
            ]);
        });

        Schema::table('about', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('about', function (Blueprint $table) {
            $table->renameColumn('title_json', 'title');
        });
    }

    /**
     * Рекурсивно "згортає" всі зустрінуті мультимовні структури {"uk": x, "en": y}
     * до їх українського значення `x`, незалежно від глибини вкладеності.
     */
    private function collapseToUkrainian(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->collapseToUkrainian($item), $value);
        }

        $hasUk = array_key_exists('uk', $value);
        $hasEn = array_key_exists('en', $value);

        if ($hasUk && $hasEn) {
            $ukValue = $this->collapseToUkrainian($value['uk']);

            $siblings = $value;
            unset($siblings['uk'], $siblings['en']);
            $siblings = $this->collapseToUkrainian($siblings);

            if ($siblings === []) {
                return $ukValue;
            }

            // Масив-список (напр. Repeater, обгорнутий цілком у LanguageTabs, як `feats`/`tasks`)
            // важливіший за побічні ключі (напр. `feat_image`) — вони, на жаль, губляться.
            if (is_array($ukValue) && array_is_list($ukValue)) {
                return $ukValue;
            }

            // Скалярне значення "uk" поряд із багатшою структурою сусідніх ключів
            // (напр. колізія поля `description` між різними секціями форми `About`)
            // вважається сміттям від такої колізії — залишаємо багатшу структуру.
            if (! is_array($ukValue)) {
                return $siblings;
            }

            return array_merge($siblings, $ukValue);
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = $this->collapseToUkrainian($item);
        }

        return $result;
    }

    /**
     * Рекурсивно відновлює мультимовну структуру {"uk": x, "en": x} для
     * усіх відомих мультимовних листових ключів.
     */
    private function restoreLanguageTabsStructure(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->restoreLanguageTabsStructure($item), $value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (in_array($key, $this->multilingualLeafKeys, true) && is_string($item)) {
                $result[$key] = ['uk' => $item, 'en' => $item];

                continue;
            }

            $result[$key] = $this->restoreLanguageTabsStructure($item);
        }

        return $result;
    }
};
