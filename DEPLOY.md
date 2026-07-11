# Deployer (dep) — деплой на сервер

Сервер: **idart.dev2025.ingsot.com** · `95.67.62.235` · PHP 8.4 · Hestia-хостинг

> ⚠ Это общий сервер с несколькими доменами (art-ua-unfo, fidart, html, ing, ksimex и др.).
> `php8.4-fpm.service` и `nginx.service` — один процесс на все сайты этой версии PHP.
> `restart:php` / `restart:nginx` затрагивают их все, не только этот проект — вызывать вручную и с осторожностью.

## Начальная настройка

```bash
# 1. Скопировать шаблон и заполнить реальными значениями
cp deploy.local.php.example deploy.local.php

# deploy.local.php — gitignored, никогда не коммитить!
# Там: SSH-креды, пароль root-пользователя, пароль БД.

# 2. Проверить SSH-подключение
vendor/bin/dep ssh production
```

> `dep` = `vendor/bin/dep`. Чтобы не писать полный путь каждый раз:
> ```bash
> alias dep='vendor/bin/dep'
> ```
>
> Все команды ниже нужно вызывать с указанием хоста: `dep <task> production`
> (или без — Deployer спросит хост интерактивно, если он один — подставит сам).

---

## Деплой

### Полный деплой

```bash
dep deploy production
```

Что делает:
1. Чинит права доступа (`chown`, через `DEP_USER_ROOT`/sudo) — можно пропустить флагом `--skip-perms`
2. `rsync` кода на сервер (без vendor, node_modules, .env, storage и т.д.)
3. Проверяет `.env` на сервере (если нет — заливает `.env.production`)
4. Создаёт нужные директории storage + права на них
5. `composer install --no-dev --optimize-autoloader`
6. `npm ci && npm run build`
7. `artisan migrate --force`, `storage:link`, `filament:assets`
8. `config:cache`, `route:cache`, `view:cache`
9. `queue:restart` (без supervisor — просто сигнал воркерам перечитать код)
10. Проверяет наличие Laravel Scheduler в crontab

### Быстрый деплой (только код + кеш)

```bash
dep deploy:quick production
```

Что делает: rsync кода → `cache:clear` → `queue:restart`. Без composer, npm, миграций.

Использовать когда: поменяли только PHP/Blade-файлы и `composer.lock` не менялся.

---

## База данных

### Забрать БД с сервера → импорт в локальный DDEV

```bash
dep db:sync production
```

Что делает: дамп на сервере → скачивает в `dump/` → `ddev import-db`.

### Только скачать дамп (без импорта)

```bash
dep db:pull production
# Файл сохранится в dump/dump_YYYY-MM-DD_HH-mm.sql.gz
```

### Залить локальную БД на сервер

```bash
# Дамп из локального DDEV + загрузка на сервер
dep db:push production

# Загрузить конкретный файл
dep db:push production --dump=./dump/dump_2026-06-10_12-00.sql.gz
```

> Если `DEP_BACKUP_BEFORE_RESTORE=true` — перед перезаписью автоматически снимается резервная копия.

### Залить готовый дамп на сервер

```bash
dep db:import production --dump=./dump/file.sql.gz
```

### Список локальных дампов

```bash
dep db:list production
```

### Посмотреть таблицы на сервере (размер, кол-во строк)

```bash
dep db:tables production
```

### MySQL shell на сервере

```bash
dep db:shell production
```

---

## Storage (uploads, media)

```bash
dep storage:pull production   # сервер → локально
dep storage:push production   # локально → сервер
```

### Забрать БД + storage разом

```bash
dep sync:all production
```

Что делает: `db:sync` (дамп с сервера → импорт в локальный DDEV) + `storage:pull` (файлы сервер → локально).
Удобно, когда нужно, чтобы записи в БД (например, обложки проектов) совпадали с реальными файлами локально.

---

## Сервисы

> На сервере нет supervisor-программ для этого проекта — очередь просто перезапускается
> сигналом (`artisan queue:restart`), без выделенных воркер-процессов под управлением supervisor.

```bash
dep restart:queue production   # artisan queue:restart
dep restart:php production     # ⚠ перезапуск PHP-FPM — общий для всех доменов на PHP 8.4
dep restart:nginx production   # ⚠ reload Nginx — общий для всех доменов на сервере
```

### Laravel Scheduler (cron)

```bash
dep scheduler:ensure production
# Добавляет в crontab: * * * * * cd /path && php8.4 artisan schedule:run
# Если уже есть — ничего не делает
```

---

## Логи

```bash
dep logs:laravel production   # tail -f storage/logs/laravel.log
dep logs:queue production     # tail -f queue.log (или laravel.log, если queue.log нет)
```

---

## Состояние сервера

```bash
dep status production
```

Показывает: диск/память, cron Scheduler, версию PHP, последний git-коммит, TTFB и HTTP-код сайта.

---

## SSH

```bash
dep ssh production
# Интерактивная SSH-сессия под DEP_USER (developer)
```

---

## Типичные сценарии

### Ежедневный деплой после изменений в коде

```bash
git push
dep deploy production
```

### Быстро поправить баг без composer/npm

```bash
dep deploy:quick production
```

### Синхронизировать БД с сервера перед работой над новой фичей

```bash
dep db:sync production
```

### Залить локальные изменения в БД на сервер

```bash
dep db:push production
# Бэкап снимается автоматически (DEP_BACKUP_BEFORE_RESTORE=true)
```

### Проверить, что происходит после деплоя

```bash
dep status production
dep logs:laravel production
```

---

## Структура файлов

```
deploy.php                # основной файл Deployer (коммитить)
deploy.local.php          # креды (gitignored, НЕ коммитить)
deploy.local.php.example  # шаблон (коммитить)
dump/                      # локальные дампы БД (gitignored)
```

## deploy.local.php — константы

| Константа               | Описание                                                    |
|--------------------------|--------------------------------------------------------------|
| `DEP_HOST`               | IP сервера                                                    |
| `DEP_PORT`               | SSH порт (22)                                                 |
| `DEP_USER`               | SSH-пользователь для деплоя (владелец сайта, без sudo)         |
| `DEP_USER_ROOT`          | Пользователь с sudo-правами (другой юзер, не `DEP_USER`)       |
| `DEP_PASSWORD_ROOT`      | Его пароль — нужен для `chown`/`systemctl` через `sudo -S`     |
| `DEP_SSH_KEY`            | Путь к SSH-ключу для `DEP_USER` (пусто = пароль через sshpass) |
| `DEP_PASSWORD`           | Пароль `DEP_USER` (если не используется SSH-ключ)              |
| `DEP_PROJECT_PATH`       | Абсолютный путь к проекту на сервере                           |
| `DEP_STORAGE_PATH`       | Путь к `storage/app/public` на сервере                         |
| `DEP_SITE_DOMAIN`        | Домен сайта                                                    |
| `DEP_PHP_VERSION`        | Версия PHP (`8.4`)                                             |
| `DEP_DB_NAME`            | Имя БД на сервере                                              |
| `DEP_DB_USER`            | Пользователь БД                                                |
| `DEP_DB_PASSWORD`        | Пароль БД                                                      |
| `DEP_DB_HOST`            | Хост БД (`127.0.0.1` — MySQL в Docker на сервере)              |
| `DEP_DB_PORT`            | Порт БД (`13308`, нестандартный из-за Docker)                  |
| `DEP_BACKUP_BEFORE_RESTORE` | `true` — бэкап перед `db:push`/`db:import`                  |
| `DEP_VERIFY_AFTER_RESTORE`  | `true` — показать таблицы после импорта                     |
