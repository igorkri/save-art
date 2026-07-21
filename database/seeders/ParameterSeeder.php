<?php

namespace Database\Seeders;

use App\Enums\ParameterType;
use App\Models\ArtCategory;
use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Каскадно видаляє parameter_values та project_parameters (foreign keys cascadeOnDelete).
        Parameter::query()->delete();

        $this->seedCategory('painting', [
            $this->listParam('Матеріал', 'Material', [
                ['Полотно', 'Canvas'],
                ['Дерево', 'Wood'],
                ['Папір', 'Paper'],
                ['Метал', 'Metal'],
            ]),
            $this->listParam('Техніка', 'Technique', [
                ['Олія', 'Oil'],
                ['Акварель', 'Watercolor'],
                ['Гуаш', 'Gouache'],
                ['Акрил', 'Acrylic'],
                ['Темпера', 'Tempera'],
            ]),
            $this->customParam('Розмір', 'Size'),
        ]);

        $this->seedCategory('sculpture', [
            $this->listParam('Матеріал', 'Material', [
                ['Бронза', 'Bronze'],
                ['Мармур', 'Marble'],
                ['Дерево', 'Wood'],
                ['Метал', 'Metal'],
                ['Глина', 'Clay'],
            ]),
            $this->customParam('Висота', 'Height'),
            $this->customParam('Вага', 'Weight'),
        ]);

        $this->seedCategory('digital', [
            $this->listParam('Формат', 'Format', [
                ['Цифровий друк', 'Digital print'],
                ['NFT', 'NFT'],
                ['Digital Art', 'Digital Art'],
            ]),
            $this->customParam('Роздільна здатність', 'Resolution'),
        ]);

        $this->seedCategory('photography', [
            $this->listParam('Формат друку', 'Print format', [
                ['Глянцевий', 'Glossy'],
                ['Матовий', 'Matte'],
                ['Полотно', 'Canvas'],
            ]),
            $this->customParam('Розмір', 'Size'),
        ]);

        $this->seedCategory('video', [
            $this->listParam('Формат відео', 'Video format', [
                ['4K', '4K'],
                ['Full HD', 'Full HD'],
                ['HD', 'HD'],
            ]),
            $this->customParam('Тривалість', 'Duration'),
        ]);

        $this->seedCategory('cinema', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Документальний', 'Documentary'],
                ['Трилер', 'Thriller'],
            ]),
            $this->customParam('Хронометраж', 'Runtime'),
        ]);

        $this->seedCategory('ar', [
            $this->listParam('Платформа', 'Platform', [
                ['iOS', 'iOS'],
                ['Android', 'Android'],
                ['Web', 'Web'],
            ]),
        ]);

        $this->seedCategory('directing', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Трагедія', 'Tragedy'],
                ['Мюзикл', 'Musical'],
            ]),
            $this->customParam('Тривалість вистави', 'Performance duration'),
        ]);

        $this->seedCategory('acting', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Трагедія', 'Tragedy'],
                ['Мюзикл', 'Musical'],
            ]),
            $this->customParam('Кількість акторів', 'Number of actors'),
        ]);

        $this->seedCategory('choreography', [
            $this->listParam('Стиль танцю', 'Dance style', [
                ['Балет', 'Ballet'],
                ['Сучасний', 'Contemporary'],
                ['Народний', 'Folk'],
                ['Хіп-хоп', 'Hip-hop'],
            ]),
        ]);

        $this->seedCategory('original_genre', [
            $this->customParam('Формат', 'Format'),
        ]);

        $this->seedCategory('poetry', [
            $this->listParam('Мова', 'Language', [
                ['Українська', 'Ukrainian'],
                ['Англійська', 'English'],
                ['Білінгва', 'Bilingual'],
            ]),
            $this->customParam('Кількість творів', 'Number of works'),
        ]);

        $this->seedCategory('prose', [
            $this->listParam('Жанр', 'Genre', [
                ['Роман', 'Novel'],
                ['Повість', 'Novella'],
                ['Оповідання', 'Short story'],
                ['Есе', 'Essay'],
            ]),
            $this->customParam('Обсяг (сторінок)', 'Volume (pages)'),
        ]);

        $this->seedCategory('music', [
            $this->listParam('Жанр', 'Genre', [
                ['Класична', 'Classical'],
                ['Джаз', 'Jazz'],
                ['Рок', 'Rock'],
                ['Поп', 'Pop'],
                ['Електронна', 'Electronic'],
                ['Народна', 'Folk'],
            ]),
            $this->customParam('Тривалість', 'Duration'),
        ]);

        $this->seedCategory('other', [
            $this->customParam('Формат', 'Format'),
        ]);
    }

    /**
     * @param  list<array{name: array{uk: string, en: string}, type: ParameterType, values: list<array{uk: string, en: string}>}>  $parameters
     */
    private function seedCategory(string $categorySlug, array $parameters): void
    {
        $category = ArtCategory::query()->where('slug', $categorySlug)->first();

        if (! $category) {
            return;
        }

        foreach ($parameters as $index => $definition) {
            $parameter = Parameter::query()->create([
                'art_category_id' => $category->id,
                'name' => $definition['name'],
                'type' => $definition['type'],
                'sort_order' => $index,
            ]);

            foreach ($definition['values'] as $valueIndex => $value) {
                $parameter->values()->create([
                    'value' => $value,
                    'sort_order' => $valueIndex,
                ]);
            }
        }
    }

    /**
     * @return array{name: array{uk: string, en: string}, type: ParameterType, values: list<array{uk: string, en: string}>}
     */
    private function listParam(string $nameUk, string $nameEn, array $values): array
    {
        return [
            'name' => ['uk' => $nameUk, 'en' => $nameEn],
            'type' => ParameterType::List,
            'values' => array_map(fn (array $pair) => ['uk' => $pair[0], 'en' => $pair[1]], $values),
        ];
    }

    /**
     * @return array{name: array{uk: string, en: string}, type: ParameterType, values: list<array{uk: string, en: string}>}
     */
    private function customParam(string $nameUk, string $nameEn): array
    {
        return [
            'name' => ['uk' => $nameUk, 'en' => $nameEn],
            'type' => ParameterType::Custom,
            'values' => [],
        ];
    }
}
