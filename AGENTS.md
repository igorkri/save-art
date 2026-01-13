# 🤖 Інструкції для AI-агентів: SaveArt Laravel

> Цей документ містить специфічні інструкції для AI-агентів при роботі з проектом SaveArt.

---

## 📋 Огляд проекту

**SaveArt** — це краудфандингова платформа для підтримки мистецтва та митців. Платформа дозволяє:
- Митцям створювати проекти та отримувати донати
- Меценатам підтримувати мистецькі проекти
- Адміністраторам модерувати контент

### Технологічний стек

| Технологія | Версія | Призначення |
|------------|--------|-------------|
| PHP | 8.4 | Мова програмування |
| Laravel | 12.x | Backend фреймворк |
| Filament | 4.x | Адмін-панель |
| Livewire | 3.x | Reactive компоненти |
| MySQL | 8.x | База даних |
| Tailwind CSS | 3.x | Стилі (адмін-панель) |
| Alpine.js | 3.x | JavaScript (адмін-панель) |
| L5-Swagger | 9.x | API документація (OpenAPI) |

### Архітектура

> ⚠️ **Важливо:** Цей репозиторій — **тільки Backend/API**. Фронтенд знаходиться в окремому репозиторії.

---

## 🏗️ Архітектура проекту

### Структура директорій

```
app/
├── Console/          # Artisan команди
├── Enums/            # PHP Enums (статуси, типи)
├── Filament/         # Адмін-панель Filament
│   ├── Resources/    # CRUD ресурси
│   └── Widgets/      # Віджети дашборду
├── Helpers/          # Допоміжні класи
├── Http/
│   └── Controllers/
│       ├── Api/      # API контролери
│       └── SaveArt/  # Web контролери
├── Livewire/         # Livewire компоненти
├── Models/           # Eloquent моделі
├── Observers/        # Model observers
├── Policies/         # Authorization policies
├── Providers/        # Service providers
├── Rules/            # Validation rules
├── Services/         # Business logic
└── Traits/           # Reusable traits
```

### Основні моделі та зв'язки

```
User (Користувач)
├── ProfilePersonal (1:1) - Персональні дані
├── ProfileLegal (1:1) - Юридичні дані (для організацій)
├── ProfileSocial (1:1) - Соціальні мережі
├── ProfileDocument (1:n) - Документи
├── Contract (1:n) - Контракти
├── Project (1:n) - Проекти (як автор)
├── Donation (1:n) - Донати (як донор)
├── Message (1:n) - Повідомлення
└── Notification (1:n) - Сповіщення

Project (Проект)
├── User (n:1) - Автор
├── ProjectStage (1:n) - Етапи виконання
├── ProjectBonus (1:n) - Бонуси для донорів
├── ProjectLike (1:n) - Лайки
├── Donation (1:n) - Донати
└── Report (1:n) - Звіти про виконання
```

---

## 🔑 Ключові Enums

При роботі з проектом використовуй ці enum-класи:

| Enum | Опис | Приклад значень |
|------|------|-----------------|
| `ProjectStatus` | Статус проекту | Draft, Announced, Active, Completed, Sold |
| `ModerationStatus` | Статус модерації | Pending, Approved, Rejected |
| `DonationStatus` | Статус донату | Pending, Completed, Failed, Refunded |
| `ContractStatus` | Статус контракту | Draft, Pending, Signed, Expired |
| `StageStatus` | Статус етапу | Planned, InProgress, Completed |
| `UserType` | Тип користувача | Individual, LegalEntity |
| `ArtCategory` | Категорія мистецтва | Painting, Sculpture, Music, etc. |
| `Currency` | Валюта | UAH, USD, EUR |
| `SignService` | Сервіс підпису | Diia, Manual |
| `NotificationType` | Тип сповіщення | ProjectApproved, NewDonation, etc. |

---

## 🌍 Мультимовність

Проект підтримує **українську (uk)** та **англійську (en)** мови.

### Translatable поля

Моделі використовують `spatie/laravel-translatable`. Поля з типом `json` в БД зазвичай translatable:

```php
// В моделі
use Spatie\Translatable\HasTranslations;

class Project extends Model
{
    use HasTranslations;
    
    public array $translatable = ['title', 'short_description'];
}
```

### Отримання перекладу

```php
// Поточна локаль
$project->title;

// Конкретна локаль
$project->getTranslation('title', 'en');

// Всі переклади
$project->getTranslations('title');
```

---

## 📡 API

### Архітектура API

> **Цей репозиторій — Backend API для зовнішнього фронтенду (окремий репозиторій).**

### Версіонування

- Публічне API: `/api/v1/*`
- Auth API: `/api/*` (без версії)

### Документація API (Swagger)

API документовано через **L5-Swagger** (OpenAPI 3.0):

```bash
# Генерація документації
php artisan l5-swagger:generate

# Перегляд документації
# http://localhost:8000/api/documentation
```

Swagger анотації в контролерах:
```php
/**
 * @OA\Get(
 *     path="/api/v1/projects",
 *     summary="Список проектів",
 *     tags={"Projects"},
 *     @OA\Response(response=200, description="Success")
 * )
 */
public function index(): JsonResponse
```

Документація: `docs/api/swagger-guide.md`

### Аутентифікація

API використовує **Laravel Sanctum**:

```php
// Захищений роут
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

### API Resources

Для API використовуй Eloquent API Resources:

```php
// app/Http/Resources/ProjectResource.php
class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            // ...
        ];
    }
}
```

---

## 🎨 Filament (Адмін-панель)

### Створення ресурсу

```bash
php artisan make:filament-resource Project --generate --no-interaction
```

### Структура ресурсу

```php
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Форма створення/редагування
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            // Колонки таблиці
        ]);
    }
}
```

### Мультимовні поля у Filament

Використовуй `filament-language-tabs`:

```php
use Pixelpeter\FilamentLanguageTabs\Forms\Components\LanguageTabs;

LanguageTabs::make('Translations')
    ->schema([
        TextInput::make('title'),
        Textarea::make('description'),
    ])
```

---

## ✅ Тестування

### Структура тестів

```
tests/
├── Feature/           # Інтеграційні тести
│   ├── Api/          # API тести
│   └── Filament/     # Адмін-панель тести
└── Unit/             # Unit тести
```

### Запуск тестів

```bash
# Всі тести
php artisan test

# Конкретний файл
php artisan test tests/Feature/Api/ProjectTest.php

# За фільтром
php artisan test --filter=testUserCanCreateProject
```

### Приклад тесту

```php
class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/v1/my/projects', [
                'title' => ['uk' => 'Тест', 'en' => 'Test'],
                'budget_goal' => 10000,
            ]);
        
        $response->assertCreated();
        $this->assertDatabaseHas('projects', ['user_id' => $user->id]);
    }
}
```

---

## 🔧 Корисні команди

```bash
# Розробка
php artisan serve                    # Запуск сервера
npm run dev                          # Фронтенд dev режим
npm run build                        # Білд фронтенду

# База даних
php artisan migrate                  # Міграції
php artisan migrate:fresh --seed     # Скидання + seed
php artisan db:seed                  # Seed даних

# Кеш
php artisan cache:clear              # Очистка кешу
php artisan config:clear             # Очистка конфігу
php artisan view:clear               # Очистка views

# Filament
php artisan make:filament-resource   # Новий ресурс
php artisan make:filament-user       # Адмін користувач

# Код
vendor/bin/pint                      # Форматування коду
php artisan test                     # Тести
```

---

## ⚠️ Важливі правила

### При створенні нових файлів

1. **Перевір сусідні файли** — дотримуйся існуючих конвенцій
2. **Використовуй Artisan** — `php artisan make:*` для генерації
3. **Додавай типізацію** — явні return types та type hints
4. **Пиши тести** — кожна фіча повинна мати тест

### При роботі з моделями

1. **Використовуй relationships** — не raw queries
2. **Eager loading** — уникай N+1 проблем
3. **Factories** — для тестових даних
4. **Observers** — для side effects

### При роботі з API

1. **Form Requests** — для валідації
2. **API Resources** — для трансформації
3. **Sanctum** — для автентифікації
4. **Версіонування** — `/api/v1/*`

### При роботі з Filament

1. **Використовуй Artisan** — для генерації ресурсів
2. **LanguageTabs** — для translatable полів
3. **Тестуй** — Livewire::test для компонентів

---

## 📁 Шаблони створення

### Новий API endpoint

1. Створи Form Request: `php artisan make:request Api/V1/StoreProjectRequest`
2. Створи Resource: `php artisan make:resource Api/V1/ProjectResource`
3. Додай метод в контролер
4. Зареєструй роут в `routes/api.php`
5. Напиши тест

### Новий Filament ресурс

1. Створи ресурс: `php artisan make:filament-resource Model --generate`
2. Налаштуй форму та таблицю
3. Додай переклади
4. Напиши тест

### Нова модель

1. Створи модель: `php artisan make:model ModelName -mfs`
2. Налаштуй міграцію
3. Додай fillable, casts, relationships
4. Створи factory
5. Напиши тест

---

## 🔗 Корисні посилання

- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Filament 4 Docs](https://filamentphp.com/docs/4.x)
- [Livewire 3 Docs](https://livewire.laravel.com/docs)
- [Проектна документація](docs/)
