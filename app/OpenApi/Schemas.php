<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * OpenAPI Schemas для Save-Art API.
 *
 * @OA\Schema(
 *     schema="LocalizedString",
 *     title="LocalizedString",
 *     description="Мультимовний текст (uk/en)",
 *     type="object",
 *
 *     @OA\Property(property="uk", type="string", example="Український текст", description="Українська версія"),
 *     @OA\Property(property="en", type="string", nullable=true, example="English text", description="Англійська версія")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="PaginationMeta",
 *     description="Метадані пагінації",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150)
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     title="PaginationLinks",
 *     description="Посилання пагінації",
 *     type="object",
 *
 *     @OA\Property(property="first", type="string", example="http://save-art.local/api/v1/projects?page=1"),
 *     @OA\Property(property="last", type="string", example="http://save-art.local/api/v1/projects?page=10"),
 *     @OA\Property(property="prev", type="string", nullable=true, example=null),
 *     @OA\Property(property="next", type="string", nullable=true, example="http://save-art.local/api/v1/projects?page=2")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="ValidationError",
 *     description="Помилка валідації (422)",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Валідацію не пройдено."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *
 *         @OA\AdditionalProperties(
 *             type="array",
 *
 *             @OA\Items(type="string", example="The title.uk field is required.")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="ErrorResponse",
 *     description="Загальна помилка API",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Ресурс не знайдено."),
 *     @OA\Property(property="error", type="string", example="Not Found")
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     title="Author",
 *     description="Автор проєкту (скорочена інформація)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true, example="http://save-art.local/storage/avatars/1.jpg")
 * )
 *
 * @OA\Schema(
 *     schema="ProjectStage",
 *     title="ProjectStage",
 *     description="Етап проєкту",
 *     type="object",
 *     required={"id", "order", "status", "title"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order", type="integer", example=1, description="Порядковий номер етапу"),
 *     @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed"}, example="pending"),
 *     @OA\Property(property="status_label", type="string", example="Очікує"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="days_planned", type="integer", nullable=true, example=30),
 *     @OA\Property(property="budget_planned", type="number", format="float", nullable=true, example=5000.00),
 *     @OA\Property(property="budget_actual", type="number", format="float", nullable=true, example=4800.00),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true, example="2025-02-01T10:00:00.000Z"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="is_completed", type="boolean", example=false),
 *     @OA\Property(property="is_in_progress", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="ProjectBonus",
 *     title="ProjectBonus",
 *     description="Бонус від автора за донат",
 *     type="object",
 *     required={"id", "order", "title", "min_donation"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="min_donation", type="number", format="float", example=500.00, description="Мінімальна сума для отримання бонусу"),
 *     @OA\Property(property="quantity", type="integer", nullable=true, example=10, description="Загальна кількість (null = безліміт)"),
 *     @OA\Property(property="quantity_claimed", type="integer", example=3, description="Скільки вже роздано"),
 *     @OA\Property(property="remaining", type="integer", nullable=true, example=7, description="Скільки залишилось"),
 *     @OA\Property(property="is_available", type="boolean", example=true),
 *     @OA\Property(property="is_unlimited", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="Project",
 *     title="Project",
 *     description="Проєкт митця (повна інформація)",
 *     type="object",
 *     required={"id", "slug", "status", "title", "currency", "budget_goal"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="miy-noviy-proekt-abc123"),
 *     @OA\Property(property="code", type="string", example="ABC12345", description="Унікальний код проєкту"),
 *     @OA\Property(property="status", type="string", enum={"draft", "moderation", "announced", "in_progress", "paused", "completed", "sold", "rejected"}, example="announced"),
 *     @OA\Property(property="status_label", type="string", example="Оголошений"),
 *     @OA\Property(property="status_moderation", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="cover_url", type="string", nullable=true, example="http://save-art.local/storage/projects/covers/1.jpg"),
 *     @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
 *     @OA\Property(property="art_category_label", type="string", example="Візуальне мистецтво"),
 *     @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
 *     @OA\Property(property="art_subcategory_label", type="string", nullable=true, example="Живопис"),
 *     @OA\Property(property="tags", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
 *     @OA\Property(property="budget_goal", type="number", format="float", example=50000.00, description="Ціль збору"),
 *     @OA\Property(property="budget_collected", type="number", format="float", example=12500.00, description="Зібрано коштів"),
 *     @OA\Property(property="progress_percentage", type="number", format="float", example=25.00, description="Відсоток від цілі"),
 *     @OA\Property(property="estimated_days", type="integer", nullable=true, example=90, description="Орієнтовний термін виконання (днів)"),
 *     @OA\Property(property="likes_count", type="integer", example=42),
 *     @OA\Property(property="donors_count", type="integer", example=15),
 *     @OA\Property(property="is_liked", type="boolean", example=false, description="Чи вподобав поточний користувач"),
 *     @OA\Property(property="announced_at", type="string", format="date-time", nullable=true, example="2025-01-15T12:00:00.000Z"),
 *     @OA\Property(property="planned_completion_at", type="string", format="date-time", nullable=true, example="2025-04-15T12:00:00.000Z"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="author", ref="#/components/schemas/Author"),
 *     @OA\Property(property="characteristics", type="array", nullable=true, description="Характеристики проєкту (розміри, матеріали тощо)",
 *
 *         @OA\Items(type="object",
 *
 *             @OA\Property(property="name", type="object", description="Назва характеристики",
 *                 @OA\Property(property="uk", type="string", example="Розмір"),
 *                 @OA\Property(property="en", type="string", example="Size")
 *             ),
 *             @OA\Property(property="value", type="object", description="Значення характеристики",
 *                 @OA\Property(property="uk", type="string", example="100x150 см"),
 *                 @OA\Property(property="en", type="string", example="100x150 cm")
 *             )
 *         )
 *     ),
 *     @OA\Property(property="budget_items", type="array", nullable=true, description="Статті бюджету",
 *
 *         @OA\Items(type="object",
 *
 *             @OA\Property(property="name", type="object", description="Назва статті бюджету",
 *                 @OA\Property(property="uk", type="string", example="Матеріали"),
 *                 @OA\Property(property="en", type="string", example="Materials")
 *             ),
 *             @OA\Property(property="amount", type="number", example=15000, description="Сума у валюті проєкту")
 *         )
 *     ),
 *     @OA\Property(property="additional_info", type="object", nullable=true),
 *     @OA\Property(property="final_result", type="object", nullable=true, description="Фінальний результат (для завершених)"),
 *     @OA\Property(property="stages", type="array", @OA\Items(ref="#/components/schemas/ProjectStage")),
 *     @OA\Property(property="bonuses", type="array", @OA\Items(ref="#/components/schemas/ProjectBonus")),
 *     @OA\Property(property="can_edit", type="boolean", example=false),
 *     @OA\Property(property="can_donate", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-10T08:30:00.000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T14:20:00.000Z")
 * )
 *
 * @OA\Schema(
 *     schema="ProjectList",
 *     title="ProjectList",
 *     description="Проєкт у списку (скорочена інформація)",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="miy-noviy-proekt-abc123"),
 *     @OA\Property(property="status", type="string", example="announced"),
 *     @OA\Property(property="status_label", type="string", example="Оголошений"),
 *     @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
 *     @OA\Property(property="cover_url", type="string", nullable=true, example="http://save-art.local/storage/projects/covers/1.jpg"),
 *     @OA\Property(property="art_category", type="string", example="visual"),
 *     @OA\Property(property="art_category_label", type="string", example="Візуальне мистецтво"),
 *     @OA\Property(property="currency", type="string", example="UAH"),
 *     @OA\Property(property="budget_goal", type="number", format="float", example=50000.00),
 *     @OA\Property(property="budget_collected", type="number", format="float", example=12500.00),
 *     @OA\Property(property="progress_percentage", type="number", format="float", example=25.00),
 *     @OA\Property(property="likes_count", type="integer", example=42),
 *     @OA\Property(property="donors_count", type="integer", example=15),
 *     @OA\Property(property="author", ref="#/components/schemas/Author"),
 *     @OA\Property(property="announced_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Користувач",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="email", type="string", format="email", example="ivan@example.com"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="role", type="string", enum={"user", "mecenat", "owner", "moderator", "admin", "developer"}, example="user"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true, example="http://save-art.local/storage/avatars/1.jpg"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2025-01-01T12:00:00.000Z"),
 *     @OA\Property(property="profile_personal", type="object", nullable=true,
 *         @OA\Property(property="first_name", type="string", example="Іван"),
 *         @OA\Property(property="last_name", type="string", example="Франко"),
 *         @OA\Property(property="phone", type="string", example="+380501234567"),
 *         @OA\Property(property="profession", type="string", example="Художник"),
 *         @OA\Property(property="bio", type="string", example="Відомий український митець..."),
 *         @OA\Property(property="city", type="string", example="Київ"),
 *         @OA\Property(property="country", type="string", example="Україна")
 *     ),
 *     @OA\Property(property="profile_social", type="object", nullable=true,
 *         @OA\Property(property="website", type="string", example="https://example.com"),
 *         @OA\Property(property="facebook", type="string", example="https://facebook.com/ivanfranko"),
 *         @OA\Property(property="instagram", type="string", example="@ivanfranko"),
 *         @OA\Property(property="youtube", type="string", nullable=true),
 *         @OA\Property(property="tiktok", type="string", nullable=true),
 *         @OA\Property(property="twitter", type="string", nullable=true),
 *         @OA\Property(property="linkedin", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="projects_count", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-15T10:00:00.000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-05T14:30:00.000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Artist",
 *     title="Artist",
 *     description="Публічний профіль митця",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true, example="http://save-art.local/storage/avatars/1.jpg"),
 *     @OA\Property(property="profession", type="string", nullable=true, example="Художник"),
 *     @OA\Property(property="bio", type="string", nullable=true, example="Відомий український митець..."),
 *     @OA\Property(property="city", type="string", nullable=true, example="Київ"),
 *     @OA\Property(property="country", type="string", nullable=true, example="Україна"),
 *     @OA\Property(property="social", type="object", nullable=true,
 *         @OA\Property(property="website", type="string", nullable=true),
 *         @OA\Property(property="facebook", type="string", nullable=true),
 *         @OA\Property(property="instagram", type="string", nullable=true),
 *         @OA\Property(property="youtube", type="string", nullable=true),
 *         @OA\Property(property="linkedin", type="string", nullable=true)
 *     ),
 *     @OA\Property(property="projects_count", type="integer", example=5),
 *     @OA\Property(property="completed_projects_count", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-15T10:00:00.000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Donation",
 *     title="Donation",
 *     description="Донат на проєкт",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="project", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="slug", type="string", example="miy-proekt"),
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="amount", type="number", format="float", example=1000.00),
 *     @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed", "refunded"}, example="paid"),
 *     @OA\Property(property="status_label", type="string", example="Оплачено"),
 *     @OA\Property(property="is_anonymous", type="boolean", example=false),
 *     @OA\Property(property="donor_name", type="string", nullable=true, example="Іван Петренко"),
 *     @OA\Property(property="bonus", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="title", ref="#/components/schemas/LocalizedString")
 *     ),
 *     @OA\Property(property="message", type="string", nullable=true, example="Успіхів у творчості!"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example="2025-01-05T15:30:00.000Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-05T15:25:00.000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Message",
 *     title="Message",
 *     description="Повідомлення в чаті з адміністрацією",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="subject", type="string", example="Питання щодо проєкту"),
 *     @OA\Property(property="body", type="string", example="Доброго дня! Хотів би уточнити..."),
 *     @OA\Property(property="is_from_admin", type="boolean", example=false),
 *     @OA\Property(property="is_read", type="boolean", example=true),
 *     @OA\Property(property="project", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="title", type="string")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-05T10:00:00.000Z")
 * )
 *
 * @OA\Schema(
 *     schema="Statistics",
 *     title="Statistics",
 *     description="Статистика платформи",
 *     type="object",
 *
 *     @OA\Property(property="total_projects", type="integer", example=150),
 *     @OA\Property(property="active_projects", type="integer", example=45),
 *     @OA\Property(property="completed_projects", type="integer", example=80),
 *     @OA\Property(property="total_artists", type="integer", example=120),
 *     @OA\Property(property="total_donations", type="number", format="float", example=1500000.00),
 *     @OA\Property(property="total_donors", type="integer", example=3500)
 * )
 */
class Schemas {}
