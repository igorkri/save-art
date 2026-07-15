<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="SaveArt API",
 *     description="API для платформи save-art.in.ua — краудфандингова платформа для митців та меценатів",
 *
 *     @OA\Contact(
 *         email="support@save-art.in.ua",
 *         name="Save-Art Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://save-art.ddev.site/api",
 *     description="Local Development"
 * )
 * @OA\Server(
 *     url="https://idart.dev2025.ingsot.com/api",
 *     description="Production Server (temporary)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Token"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-Api-Key",
 *     description="API ключ для доступу до API"
 * )
 *
 * @OA\Schema(
 *     schema="LocalizedString",
 *     title="LocalizedString",
 *     description="Мультимовний текст (uk/en)",
 *     type="object",
 *
 *     @OA\Property(property="uk", type="string", example="Український текст"),
 *     @OA\Property(property="en", type="string", nullable=true, example="English text")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="PaginationMeta",
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
 *     type="object",
 *
 *     @OA\Property(property="first", type="string"),
 *     @OA\Property(property="last", type="string"),
 *     @OA\Property(property="prev", type="string", nullable=true),
 *     @OA\Property(property="next", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="ValidationError",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Валідацію не пройдено."),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Ресурс не знайдено."),
 *     @OA\Property(property="error", type="string", example="Not Found")
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     title="Author",
 *     description="Автор проєкту",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true)
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
 *
 * @OA\Schema(
 *     schema="SiteHeader",
 *     title="SiteHeader",
 *     description="Дані хедера сайту",
 *     type="object",
 *
 *     @OA\Property(property="logo", type="string", nullable=true, example="https://example.com/storage/site-settings/logos/logo.svg"),
 *     @OA\Property(property="brand_name", type="string", example="save-art.in.ua"),
 *     @OA\Property(property="dropdown_sites", type="array",
 *
 *         @OA\Items(type="object",
 *
 *             @OA\Property(property="name", type="string", example="save-art.in.ua"),
 *             @OA\Property(property="url", type="string", example="https://save-art.in.ua"),
 *             @OA\Property(property="is_active", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Property(property="menu", type="array",
 *
 *         @OA\Items(type="object",
 *
 *             @OA\Property(property="label", type="string", example="Проєкти"),
 *             @OA\Property(property="url", type="string", example="/projects")
 *         )
 *     ),
 *     @OA\Property(property="socials", type="object",
 *         @OA\Property(property="instagram", type="string", example="https://instagram.com/"),
 *         @OA\Property(property="facebook", type="string", example="https://facebook.com/"),
 *         @OA\Property(property="youtube", type="string", example="https://youtube.com/")
 *     ),
 *     @OA\Property(property="support_button", type="object",
 *         @OA\Property(property="url", type="string", example="/support-platform"),
 *         @OA\Property(property="text", type="string", example="Підтримати")
 *     ),
 *     @OA\Property(property="login_button", type="object",
 *         @OA\Property(property="text", type="string", example="Увійти")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SiteFooter",
 *     title="SiteFooter",
 *     description="Дані футера сайту",
 *     type="object",
 *
 *     @OA\Property(property="top", type="object",
 *         @OA\Property(property="brand_name", type="string", example="save-art.in.ua"),
 *         @OA\Property(property="slogan", type="string", example="Мистецтво допомоги — найсучасніше з мистецтв"),
 *         @OA\Property(property="collaboration", type="object",
 *             @OA\Property(property="title", type="string", example="Запрошуємо експертів до співпраці"),
 *             @OA\Property(property="text", type="string", example="Благодійний фонд ID_Art UA відкритий до співпраці..."),
 *             @OA\Property(property="items", type="array",
 *
 *                 @OA\Items(type="object",
 *
 *                     @OA\Property(property="image", type="string", nullable=true),
 *                     @OA\Property(property="text", type="string", example="Створення сучасного українського мистецтва")
 *                 )
 *             ),
 *             @OA\Property(property="button_text", type="string", example="Відправити заявку")
 *         )
 *     ),
 *     @OA\Property(property="middle", type="object",
 *         @OA\Property(property="sites_menu", type="array",
 *
 *             @OA\Items(type="object",
 *
 *                 @OA\Property(property="site_name", type="string", example="save-art.in.ua"),
 *                 @OA\Property(property="site_url", type="string", example="/"),
 *                 @OA\Property(property="links", type="array",
 *
 *                     @OA\Items(type="object",
 *
 *                         @OA\Property(property="label", type="string", example="Проєкти"),
 *                         @OA\Property(property="url", type="string", example="/projects")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Property(property="bottom", type="object",
 *         @OA\Property(property="company_name", type="string", example="БЛАГОДІЙНИЙ ФОНД ID_Art UA"),
 *         @OA\Property(property="address", type="string", example="м. Івано-Франківськ, Україна"),
 *         @OA\Property(property="email", type="string", example="idartua.bo@gmail.com"),
 *         @OA\Property(property="phone", type="string", example="+380 67 734 5938"),
 *         @OA\Property(property="social_links", type="array",
 *
 *             @OA\Items(type="object",
 *
 *                 @OA\Property(property="type", type="string", example="instagram"),
 *                 @OA\Property(property="url", type="string", example="https://instagram.com/"),
 *                 @OA\Property(property="label", type="string", nullable=true, example="@id_artUA")
 *             )
 *         ),
 *         @OA\Property(property="copyright_year", type="string", example="2025")
 *     )
 * )
 */
class OpenApiInfo
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
