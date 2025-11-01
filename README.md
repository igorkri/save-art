<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Save Art - Laravel Application

## Quick Start

### Швидкий старт після клонування

```bash
git clone https://github.com/your-username/save-art-laravel.git
cd save-art-laravel
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan make:filament-user
npm run build
php artisan serve
```

**Адмінка:** http://localhost:8000/admin

📖 **[Детальний Quick Start Guide](docs/quick-start.md)**

## Документація

- 📦 **[Deployment Guide](docs/deployment-guide.md)** - Повний гайд по переносу на сервер
- 🔧 **[Troubleshooting Admin 404](docs/troubleshooting-admin-404.md)** - Вирішення проблеми 404 на адмінці
- 📱 **[Device Detection Guide](docs/device-detect-guide.md)** - Визначення типу пристрою
- 📋 **[Forms Guide](docs/forms-guide.md)** - Робота з формами
- 🔔 **[Notification Guide](docs/notification-guide.md)** - Система сповіщень
- 🛠️ **[Artisan Commands](docs/artisan-commands-guide.md)** - Власні Artisan команди

## Технології

- **PHP:** 8.4
- **Laravel:** 12.x
- **Filament:** 4.x (Admin Panel)
- **Livewire:** 3.x
- **Tailwind CSS**
- **Alpine.js**

## Основні функції

- 🎨 Admin panel з Filament
- 🌍 Мультимовність (UK/EN)
- 📱 Визначення типу пристрою
- 🔐 Аутентифікація з Laravel Sanctum
- 🖼️ Обробка зображень з Intervention Image
- 📦 RESTful API

## Вимоги

- PHP >= 8.4
- Composer
- Node.js & NPM
- MySQL/PostgreSQL або SQLite
- Web server (Nginx/Apache)

## Встановлення

### Локальна розробка

1. **Клонування та встановлення залежностей:**
```bash
git clone https://github.com/your-username/save-art-laravel.git
cd save-art-laravel
composer install
npm install
```

2. **Налаштування середовища:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Налаштування бази даних в .env:**
```env
DB_CONNECTION=mysql
DB_DATABASE=save_art
DB_USERNAME=root
DB_PASSWORD=
```

4. **Міграції та seeding:**
```bash
php artisan migrate
php artisan db:seed
```

5. **Створення адміністратора:**
```bash
php artisan make:filament-user
```

6. **Збірка frontend:**
```bash
npm run build
# Або для розробки:
npm run dev
```

7. **Запуск сервера:**
```bash
php artisan serve
```

Відкрийте http://localhost:8000/admin

### Production сервер

Детальні інструкції в **[Deployment Guide](docs/deployment-guide.md)**

## Структура проекту

```
app/
├── Filament/          # Filament admin resources
│   └── Resources/     # CRUD ресурси
├── Http/
│   ├── Controllers/   # Контролери
│   └── Middleware/    # Middleware
├── Models/            # Eloquent моделі
├── Livewire/          # Livewire компоненти
└── Providers/         # Service providers

resources/
├── views/             # Blade шаблони
├── js/                # JavaScript
└── css/               # Стилі

routes/
├── web.php            # Web роути
├── api.php            # API роути
└── api-auth.php       # API аутентифікація

database/
├── migrations/        # Міграції
├── seeders/          # Seeders
└── factories/        # Factories
```

## Типові команди

```bash
# Очистка кешу
php artisan optimize:clear

# Кешування для продакшену
php artisan optimize

# Міграції
php artisan migrate
php artisan migrate:fresh --seed

# Tinker
php artisan tinker

# Список роутів
php artisan route:list

# Тести
php artisan test

# Code style
./vendor/bin/pint
```

## Troubleshooting

### Адмінка видає 404

```bash
php artisan optimize:clear
php artisan make:filament-user
```

Детальніше: **[Troubleshooting Admin 404](docs/troubleshooting-admin-404.md)**

### CSS/JS не завантажуються

```bash
npm run build
php artisan optimize:clear
```

### Проблеми з правами доступу

```bash
chmod -R 775 storage bootstrap/cache
```

## API Documentation

API endpoints доступні за адресою `/api/`

Документація: `/api/documentation` (якщо налаштовано)

## Тестування

```bash
# Всі тести
php artisan test

# Конкретний тест
php artisan test --filter=ExampleTest

# З coverage
php artisan test --coverage
```

## Contributing

1. Fork проекту
2. Створіть feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit зміни (`git commit -m 'Add some AmazingFeature'`)
4. Push в branch (`git push origin feature/AmazingFeature`)
5. Створіть Pull Request

## License

MIT License

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
