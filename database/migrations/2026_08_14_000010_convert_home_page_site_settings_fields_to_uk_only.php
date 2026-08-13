<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `home_pages` — Патерн A: прості top-level мультимовні поля {"uk":..,"en":..},
     * що конвертуються зі зміною типу колонки на фінальний (string/text).
     *
     * @var array<string, array{0: string, 1: int|null}>
     */
    private array $homePageScalarColumns = [
        'hero_title' => ['string', 255],
        'donates_subtitle' => ['string', 255],
        'donates_title' => ['string', 255],
        'donates_text' => ['text', null],
        'partners_title' => ['string', 255],
        'ad_first_title' => ['string', 255],
        'ad_first_button_text' => ['string', 100],
        'ad_second_title' => ['string', 255],
        'ad_second_button_text' => ['string', 100],
        'footer_expert_title' => ['string', 255],
        'footer_expert_text' => ['text', null],
        'footer_expert_button_text' => ['string', 100],
        'platform_description_tagline' => ['string', 50],
        'platform_description_title' => ['string', 255],
        'platform_description_subtitle' => ['string', 255],
    ];

    /**
     * `home_pages` — Патерн B: json-колонки, що містять вкладені мультимовні
     * структури {"uk":..,"en":..} на різних рівнях вкладеності (як цілком
     * top-level обгорнуті масиви `platform_description_paragraphs`/
     * `platform_features`/`footer_expert_features`, так і per-item обгорнуті
     * поля репитера `partners`). Тип колонки (json) не змінюється.
     *
     * @var array<int, string>
     */
    private array $homePageNestedColumns = [
        'footer_expert_features',
        'platform_description_paragraphs',
        'platform_features',
        'partners',
    ];

    /**
     * `site_settings` — Патерн A.
     *
     * @var array<string, array{0: string, 1: int|null}>
     */
    private array $siteSettingsScalarColumns = [
        'header_brand_name' => ['string', 255],
        'header_support_button_text' => ['string', 50],
        'header_login_button_text' => ['string', 50],
        'footer_brand_name' => ['string', 255],
        'footer_slogan' => ['string', 255],
        'footer_collaboration_title' => ['string', 255],
        'footer_collaboration_text' => ['text', null],
        'footer_collaboration_button_text' => ['string', 50],
        'footer_company_name' => ['string', 255],
        'footer_address' => ['string', 255],
    ];

    /**
     * `site_settings` — Патерн B: json-колонки з репитерами, у яких окремі
     * поля елементів (`name`, `label`, `text`, `site_name`, вкладені
     * `links.*.label`) обгорнуті в {"uk":..,"en":..}. Тип колонки не змінюється.
     *
     * @var array<int, string>
     */
    private array $siteSettingsNestedColumns = [
        'header_dropdown_sites',
        'header_menu',
        'footer_collaboration_items',
        'footer_sites_menu',
    ];

    /**
     * Ключі, які на будь-якому рівні вкладеності в *leaf-wrap* колонках
     * (партнери, елементи меню/репитерів) вважаються мультимовними
     * листовими полями. Використовується лише для відновлення структури у down().
     *
     * @var array<int, string>
     */
    private array $leafMultilingualKeys = [
        'name',
        'description',
        'label',
        'site_name',
        'text',
    ];

    /**
     * Json-колонки, весь вміст яких (масив) був обгорнутий у {"uk":[...],"en":[...]}
     * (а не окремі елементи всередині нього). Використовується лише для down().
     *
     * @var array<int, string>
     */
    private array $wholeValueWrapColumns = [
        'footer_expert_features',
        'platform_description_paragraphs',
        'platform_features',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ----------------- home_pages -----------------
        foreach ($this->homePageScalarColumns as $column => [$type, $length]) {
            $this->convertScalarColumnToUkOnly('home_pages', $column, $type, $length);
        }

        DB::table('home_pages')->get()->each(function ($row) {
            $update = [];

            foreach ($this->homePageNestedColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);
                $update[$column] = json_encode($this->collapseToUkrainian($decoded));
            }

            if ($update !== []) {
                DB::table('home_pages')->where('id', $row->id)->update($update);
            }
        });

        // ----------------- site_settings -----------------
        foreach ($this->siteSettingsScalarColumns as $column => [$type, $length]) {
            $this->convertScalarColumnToUkOnly('site_settings', $column, $type, $length);
        }

        DB::table('site_settings')->get()->each(function ($row) {
            $update = [];

            foreach ($this->siteSettingsNestedColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);
                $update[$column] = json_encode($this->collapseToUkrainian($decoded));
            }

            if ($update !== []) {
                DB::table('site_settings')->where('id', $row->id)->update($update);
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
        // ----------------- site_settings -----------------
        DB::table('site_settings')->get()->each(function ($row) {
            $update = [];

            foreach ($this->siteSettingsNestedColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);
                $update[$column] = json_encode($this->restoreLeafWrapStructure($decoded));
            }

            if ($update !== []) {
                DB::table('site_settings')->where('id', $row->id)->update($update);
            }
        });

        foreach (array_reverse($this->siteSettingsScalarColumns) as $column => [$type, $length]) {
            $this->restoreScalarColumnToJson('site_settings', $column);
        }

        // ----------------- home_pages -----------------
        DB::table('home_pages')->get()->each(function ($row) {
            $update = [];

            foreach ($this->homePageNestedColumns as $column) {
                if ($row->{$column} === null) {
                    continue;
                }

                $decoded = json_decode($row->{$column}, true);

                $update[$column] = json_encode(
                    in_array($column, $this->wholeValueWrapColumns, true)
                        ? ['uk' => $decoded, 'en' => $decoded]
                        : $this->restoreLeafWrapStructure($decoded)
                );
            }

            if ($update !== []) {
                DB::table('home_pages')->where('id', $row->id)->update($update);
            }
        });

        foreach (array_reverse($this->homePageScalarColumns) as $column => [$type, $length]) {
            $this->restoreScalarColumnToJson('home_pages', $column);
        }
    }

    /**
     * Конвертує json-колонку з {"uk":..,"en":..} у просту колонку фінального
     * типу, що містить лише українське значення.
     */
    private function convertScalarColumnToUkOnly(string $table, string $column, string $type, ?int $length): void
    {
        $tmpColumn = $column.'_uk';

        Schema::table($table, function (Blueprint $blueprint) use ($type, $length, $tmpColumn, $column) {
            if ($type === 'text') {
                $blueprint->text($tmpColumn)->nullable()->after($column);
            } else {
                $blueprint->string($tmpColumn, $length)->nullable()->after($column);
            }
        });

        DB::statement("UPDATE {$table} SET {$tmpColumn} = JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.uk')) WHERE {$column} IS NOT NULL");

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($tmpColumn, $column) {
            $blueprint->renameColumn($tmpColumn, $column);
        });
    }

    /**
     * Повертає колонку назад до json з дубльованим {"uk": x, "en": x}.
     */
    private function restoreScalarColumnToJson(string $table, string $column): void
    {
        $tmpColumn = $column.'_json';

        Schema::table($table, function (Blueprint $blueprint) use ($tmpColumn, $column) {
            $blueprint->json($tmpColumn)->nullable()->after($column);
        });

        DB::table($table)->whereNotNull($column)->get()->each(function ($row) use ($table, $tmpColumn, $column) {
            DB::table($table)->where('id', $row->id)->update([
                $tmpColumn => json_encode(['uk' => $row->{$column}, 'en' => $row->{$column}]),
            ]);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($tmpColumn, $column) {
            $blueprint->renameColumn($tmpColumn, $column);
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

            if (is_array($ukValue) && array_is_list($ukValue)) {
                return $ukValue;
            }

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
     * усіх відомих мультимовних листових ключів (`$leafMultilingualKeys`).
     */
    private function restoreLeafWrapStructure(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->restoreLeafWrapStructure($item), $value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (in_array($key, $this->leafMultilingualKeys, true) && is_string($item)) {
                $result[$key] = ['uk' => $item, 'en' => $item];

                continue;
            }

            $result[$key] = $this->restoreLeafWrapStructure($item);
        }

        return $result;
    }
};
