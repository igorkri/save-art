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
     *
     * Ідемпотентний: параметри/значення оновлюються (upsert) за ключем
     * "категорія + uk-назва", а не видаляються й перестворюються — тому
     * сидер можна безпечно повторно запускати на продакшені (наприклад,
     * щоб додати нові параметри/значення), не втрачаючи project_parameters
     * вже існуючих проєктів (delete() на Parameter каскадно зносив би їх —
     * FK cascadeOnDelete). Обмеження: параметри/значення, які прибрали з
     * визначень нижче, самі по собі не видаляться — приберіть їх вручну,
     * якщо потрібно.
     */
    public function run(): void
    {
        $this->seedCategory('painting', [
            $this->listParam('Матеріал', 'Material', [
                ['Полотно', 'Canvas'],
                ['Дерево', 'Wood'],
                ['Папір', 'Paper'],
                ['Метал', 'Metal'],
                ['Картон', 'Cardboard'],
                ['Скло', 'Glass'],
                ['Штукатурка (фреска)', 'Plaster (fresco)'],
            ]),
            $this->listParam('Техніка', 'Technique', [
                ['Олія', 'Oil'],
                ['Акварель', 'Watercolor'],
                ['Гуаш', 'Gouache'],
                ['Акрил', 'Acrylic'],
                ['Темпера', 'Tempera'],
                ['Пастель', 'Pastel'],
                ['Туш/чорнило', 'Ink'],
                ['Змішана техніка', 'Mixed media'],
            ]),
            $this->listParam('Стиль', 'Style', [
                ['Реалізм', 'Realism'],
                ['Абстракціонізм', 'Abstract'],
                ['Імпресіонізм', 'Impressionism'],
                ['Експресіонізм', 'Expressionism'],
                ['Сюрреалізм', 'Surrealism'],
                ['Поп-арт', 'Pop art'],
                ['Мінімалізм', 'Minimalism'],
                ['Стріт-арт', 'Street art'],
                ['Наївне мистецтво', 'Naive art'],
            ]),
            $this->listParam('Орієнтація', 'Orientation', [
                ['Портретна', 'Portrait'],
                ['Альбомна', 'Landscape'],
                ['Квадратна', 'Square'],
            ]),
            $this->listParam('Наявність рами', 'Framed', [
                ['У рамі', 'Framed'],
                ['Без рами', 'Unframed'],
            ]),
            $this->customParam('Розмір', 'Size'),
            $this->customParam('Рік створення', 'Year created'),
        ]);

        $this->seedCategory('sculpture', [
            $this->listParam('Матеріал', 'Material', [
                ['Бронза', 'Bronze'],
                ['Мармур', 'Marble'],
                ['Дерево', 'Wood'],
                ['Метал', 'Metal'],
                ['Глина', 'Clay'],
                ['Камінь', 'Stone'],
                ['Гіпс', 'Plaster'],
                ['Скло', 'Glass'],
                ['Полімерна смола', 'Resin'],
            ]),
            $this->listParam('Техніка виконання', 'Technique', [
                ['Литво', 'Casting'],
                ['Різьблення', 'Carving'],
                ['Зварювання', 'Welding'],
                ['Ліплення', 'Modeling'],
                ['Кування', 'Forging'],
            ]),
            $this->listParam('Наявність постаменту', 'Pedestal included', [
                ['Із постаментом', 'With pedestal'],
                ['Без постаменту', 'Without pedestal'],
            ]),
            $this->customParam('Висота', 'Height'),
            $this->customParam('Вага', 'Weight'),
            $this->customParam('Рік створення', 'Year created'),
        ]);

        $this->seedCategory('digital', [
            $this->listParam('Формат', 'Format', [
                ['Цифровий друк', 'Digital print'],
                ['NFT', 'NFT'],
                ['Digital Art', 'Digital Art'],
                ['3D-модель', '3D model'],
                ['Генеративне мистецтво', 'Generative art'],
                ['AI-арт', 'AI art'],
            ]),
            $this->listParam('Файловий формат', 'File format', [
                ['PNG', 'PNG'],
                ['JPEG', 'JPEG'],
                ['TIFF', 'TIFF'],
                ['PSD', 'PSD'],
                ['SVG', 'SVG'],
                ['GLB/GLTF (3D)', 'GLB/GLTF (3D)'],
            ]),
            $this->listParam('Тип ліцензії', 'License type', [
                ['Ексклюзивна', 'Exclusive'],
                ['Невиключна', 'Non-exclusive'],
                ['Відкритий тираж', 'Open edition'],
            ]),
            $this->customParam('Роздільна здатність', 'Resolution'),
        ]);

        $this->seedCategory('photography', [
            $this->listParam('Формат друку', 'Print format', [
                ['Глянцевий', 'Glossy'],
                ['Матовий', 'Matte'],
                ['Полотно', 'Canvas'],
                ['Металевий друк', 'Metallic print'],
                ['Фотопапір Fine Art', 'Fine art paper'],
            ]),
            $this->listParam('Жанр', 'Genre', [
                ['Портрет', 'Portrait'],
                ['Пейзаж', 'Landscape'],
                ['Вулична фотографія', 'Street photography'],
                ['Документальна', 'Documentary'],
                ['Макрофотографія', 'Macro'],
                ['Концептуальна', 'Conceptual'],
                ['Чорно-біла', 'Black & white'],
            ]),
            $this->listParam('Колірне рішення', 'Color mode', [
                ['Кольорова', 'Color'],
                ['Чорно-біла', 'Black & white'],
                ['Сепія', 'Sepia'],
            ]),
            $this->customParam('Розмір', 'Size'),
            $this->customParam('Наклад (тираж)', 'Edition size'),
        ]);

        $this->seedCategory('video', [
            $this->listParam('Формат відео', 'Video format', [
                ['8K', '8K'],
                ['4K', '4K'],
                ['Full HD', 'Full HD'],
                ['HD', 'HD'],
            ]),
            $this->listParam('Жанр', 'Genre', [
                ['Документальний', 'Documentary'],
                ['Арт-хаус', 'Art-house'],
                ['Експериментальний', 'Experimental'],
                ['Музичне відео', 'Music video'],
                ['Анімація', 'Animation'],
                ['Рекламний ролик', 'Commercial'],
            ]),
            $this->listParam('Мова', 'Language', [
                ['Українська', 'Ukrainian'],
                ['Англійська', 'English'],
                ['Без діалогів', 'No dialogue'],
                ['Багатомовний', 'Multilingual'],
            ]),
            $this->customParam('Тривалість', 'Duration'),
        ]);

        $this->seedCategory('cinema', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Документальний', 'Documentary'],
                ['Трилер', 'Thriller'],
                ['Жахи', 'Horror'],
                ['Фантастика', 'Sci-fi'],
                ['Історичний', 'Historical'],
                ['Драмеді', 'Dramedy'],
            ]),
            $this->listParam('Мова оригіналу', 'Original language', [
                ['Українська', 'Ukrainian'],
                ['Англійська', 'English'],
                ['Без діалогів', 'No dialogue'],
                ['Багатомовний', 'Multilingual'],
            ]),
            $this->listParam('Віковий рейтинг', 'Age rating', [
                ['0+', '0+'],
                ['6+', '6+'],
                ['12+', '12+'],
                ['16+', '16+'],
                ['18+', '18+'],
            ]),
            $this->customParam('Хронометраж', 'Runtime'),
        ]);

        $this->seedCategory('ar', [
            $this->listParam('Платформа', 'Platform', [
                ['iOS', 'iOS'],
                ['Android', 'Android'],
                ['Web', 'Web'],
                ['VR-гарнітура', 'VR headset'],
            ]),
            $this->listParam('Технологія AR', 'AR technology', [
                ['Безмаркерна (markerless)', 'Markerless'],
                ['Маркерна', 'Marker-based'],
                ['WebAR', 'WebAR'],
                ['На основі геолокації', 'Location-based'],
            ]),
            $this->customParam('Необхідне обладнання', 'Required equipment'),
        ]);

        $this->seedCategory('directing', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Трагедія', 'Tragedy'],
                ['Мюзикл', 'Musical'],
                ['Трагікомедія', 'Tragicomedy'],
                ['Театр абсурду', 'Theatre of the absurd'],
            ]),
            $this->listParam('Формат сцени', 'Stage format', [
                ['Класична сцена', 'Classical stage'],
                ['Іммерсивний театр', 'Immersive theatre'],
                ['Вуличний театр', 'Street theatre'],
                ['Камерна вистава', 'Chamber play'],
            ]),
            $this->customParam('Тривалість вистави', 'Performance duration'),
            $this->customParam('Кількість акторів', 'Number of actors'),
        ]);

        $this->seedCategory('acting', [
            $this->listParam('Жанр', 'Genre', [
                ['Драма', 'Drama'],
                ['Комедія', 'Comedy'],
                ['Трагедія', 'Tragedy'],
                ['Мюзикл', 'Musical'],
            ]),
            $this->listParam('Формат вистави', 'Performance format', [
                ['Моновистава', 'Monodrama'],
                ['Ансамблева вистава', 'Ensemble play'],
            ]),
            $this->listParam('Вікова категорія глядачів', 'Audience age rating', [
                ['0+', '0+'],
                ['6+', '6+'],
                ['12+', '12+'],
                ['16+', '16+'],
                ['18+', '18+'],
            ]),
            $this->customParam('Кількість акторів', 'Number of actors'),
        ]);

        $this->seedCategory('choreography', [
            $this->listParam('Стиль танцю', 'Dance style', [
                ['Балет', 'Ballet'],
                ['Сучасний', 'Contemporary'],
                ['Народний', 'Folk'],
                ['Хіп-хоп', 'Hip-hop'],
                ['Джаз-модерн', 'Jazz-modern'],
                ['Латиноамериканські танці', 'Latin dance'],
                ['Вуличні танці', 'Street dance'],
            ]),
            $this->customParam('Кількість танцівників', 'Number of dancers'),
            $this->customParam('Тривалість номера', 'Performance duration'),
        ]);

        $this->seedCategory('original_genre', [
            $this->customParam('Формат', 'Format'),
            $this->customParam('Кількість учасників', 'Number of participants'),
            $this->customParam('Тривалість', 'Duration'),
        ]);

        $this->seedCategory('poetry', [
            $this->listParam('Мова', 'Language', [
                ['Українська', 'Ukrainian'],
                ['Англійська', 'English'],
                ['Білінгва', 'Bilingual'],
                ['Інша', 'Other'],
            ]),
            $this->listParam('Формат видання', 'Publication format', [
                ['Друкована книга', 'Paper book'],
                ['Електронна книга', 'E-book'],
                ['Аудіокнига', 'Audiobook'],
            ]),
            $this->customParam('Кількість творів', 'Number of works'),
            $this->customParam('Наклад', 'Print run'),
        ]);

        $this->seedCategory('prose', [
            $this->listParam('Жанр', 'Genre', [
                ['Роман', 'Novel'],
                ['Повість', 'Novella'],
                ['Оповідання', 'Short story'],
                ['Есе', 'Essay'],
                ['Мемуари', 'Memoir'],
                ['Фентезі', 'Fantasy'],
                ['Детектив', 'Detective'],
                ['Історичний роман', 'Historical novel'],
            ]),
            $this->listParam('Формат видання', 'Publication format', [
                ['Друкована книга', 'Paper book'],
                ['Електронна книга', 'E-book'],
                ['Аудіокнига', 'Audiobook'],
            ]),
            $this->customParam('Обсяг (сторінок)', 'Volume (pages)'),
            $this->customParam('Наклад', 'Print run'),
        ]);

        $this->seedCategory('music', [
            $this->listParam('Жанр', 'Genre', [
                ['Класична', 'Classical'],
                ['Джаз', 'Jazz'],
                ['Рок', 'Rock'],
                ['Поп', 'Pop'],
                ['Електронна', 'Electronic'],
                ['Народна', 'Folk'],
                ['Хіп-хоп', 'Hip-hop'],
                ['Блюз', 'Blues'],
                ['Метал', 'Metal'],
                ['Інді', 'Indie'],
                ['Ембієнт', 'Ambient'],
            ]),
            $this->listParam('Формат випуску', 'Release format', [
                ['Альбом', 'Album'],
                ['EP', 'EP'],
                ['Сингл', 'Single'],
                ['Саундтрек', 'Soundtrack'],
            ]),
            $this->listParam('Мова текстів', 'Lyrics language', [
                ['Українська', 'Ukrainian'],
                ['Англійська', 'English'],
                ['Інструментал (без тексту)', 'Instrumental'],
                ['Декілька мов', 'Multiple languages'],
            ]),
            $this->customParam('Тривалість', 'Duration'),
        ]);

        $this->seedCategory('other', [
            $this->customParam('Формат', 'Format'),
            $this->customParam('Тип проєкту', 'Project type'),
            $this->customParam('Додаткові деталі', 'Additional details'),
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
            $parameter = Parameter::query()
                ->where('art_category_id', $category->id)
                ->where('name->uk', $definition['name']['uk'])
                ->first();

            $parameterAttributes = [
                'art_category_id' => $category->id,
                'name' => $definition['name'],
                'type' => $definition['type'],
                'sort_order' => $index,
            ];

            if ($parameter) {
                $parameter->update($parameterAttributes);
            } else {
                $parameter = Parameter::query()->create($parameterAttributes);
            }

            foreach ($definition['values'] as $valueIndex => $value) {
                $parameterValue = $parameter->values()
                    ->where('value->uk', $value['uk'])
                    ->first();

                $valueAttributes = [
                    'value' => $value,
                    'sort_order' => $valueIndex,
                ];

                if ($parameterValue) {
                    $parameterValue->update($valueAttributes);
                } else {
                    $parameter->values()->create($valueAttributes);
                }
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
