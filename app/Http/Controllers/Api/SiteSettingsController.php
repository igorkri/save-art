<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="SiteSettings",
 *     description="API для глобальних налаштувань сайту (header, footer)"
 * )
 */
class SiteSettingsController extends Controller
{
    /**
     * Отримати всі налаштування сайту (header + footer)
     *
     * @OA\Get(
     *     path="/site/settings",
     *     operationId="getSiteSettings",
     *     tags={"SiteSettings"},
     *     summary="Всі налаштування сайту",
     *     description="Повертає повні дані для header та footer",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова контенту (uk, en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Налаштування сайту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="header", ref="#/components/schemas/SiteHeader"),
     *                 @OA\Property(property="footer", ref="#/components/schemas/SiteFooter")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Налаштування не знайдені")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $this->getLanguage($request);
        app()->setLocale($language);

        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => [
                'header' => $this->formatHeader($settings, $language),
                'footer' => $this->formatFooter($settings, $language),
            ],
        ]);
    }

    /**
     * Отримати дані хедера
     *
     * @OA\Get(
     *     path="/site/header",
     *     operationId="getSiteHeader",
     *     tags={"SiteSettings"},
     *     summary="Дані хедера сайту",
     *     description="Повертає дані для рендерингу хедера: логотип, меню, соцмережі, кнопки",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова контенту (uk, en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані хедера",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/SiteHeader")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Налаштування не знайдені")
     * )
     */
    public function header(Request $request): JsonResponse
    {
        $language = $this->getLanguage($request);
        app()->setLocale($language);

        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatHeader($settings, $language),
        ]);
    }

    /**
     * Отримати дані футера
     *
     * @OA\Get(
     *     path="/site/footer",
     *     operationId="getSiteFooter",
     *     tags={"SiteSettings"},
     *     summary="Дані футера сайту",
     *     description="Повертає дані для рендерингу футера: бренд, слоган, співпраця, меню сайтів, контакти",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова контенту (uk, en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані футера",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/SiteFooter")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Налаштування не знайдені")
     * )
     */
    public function footer(Request $request): JsonResponse
    {
        $language = $this->getLanguage($request);
        app()->setLocale($language);

        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatFooter($settings, $language),
        ]);
    }

    /**
     * Форматування даних хедера
     */
    private function formatHeader(SiteSettings $settings, string $language): array
    {
        return [
            'logo' => $settings->site_logo ? asset('storage/'.$settings->site_logo) : null,
            'brand_name' => $settings->getTranslation('header_brand_name', $language, false),
            'dropdown_sites' => $this->formatDropdownSites($settings->header_dropdown_sites, $language),
            'menu' => $this->formatMenu($settings->header_menu, $language),
            'socials' => $settings->header_socials,
            'support_button' => [
                'url' => $settings->header_support_button_url,
                'text' => $settings->getTranslation('header_support_button_text', $language, false),
            ],
            'login_button' => [
                'text' => $settings->getTranslation('header_login_button_text', $language, false),
            ],
        ];
    }

    /**
     * Форматування даних футера
     */
    private function formatFooter(SiteSettings $settings, string $language): array
    {
        return [
            'top' => [
                'brand_name' => $settings->getTranslation('footer_brand_name', $language, false),
                'slogan' => $settings->getTranslation('footer_slogan', $language, false),
                'collaboration' => [
                    'title' => $settings->getTranslation('footer_collaboration_title', $language, false),
                    'text' => $settings->getTranslation('footer_collaboration_text', $language, false),
                    'items' => $this->formatCollaborationItems($settings->footer_collaboration_items, $language),
                    'button_text' => $settings->getTranslation('footer_collaboration_button_text', $language, false),
                ],
            ],
            'middle' => [
                'sites_menu' => $this->formatSitesMenu($settings->footer_sites_menu, $language),
            ],
            'bottom' => [
                'company_name' => $settings->getTranslation('footer_company_name', $language, false),
                'address' => $settings->getTranslation('footer_address', $language, false),
                'email' => $settings->footer_email,
                'phone' => $settings->footer_phone,
                'social_links' => $settings->footer_social_links,
                'copyright_year' => $settings->footer_copyright_year,
            ],
        ];
    }

    /**
     * Форматування dropdown сайтів
     */
    private function formatDropdownSites(?array $sites, string $language): array
    {
        if (empty($sites)) {
            return [];
        }

        return array_map(fn ($site) => [
            'name' => $this->getTranslatedValue($site['name'] ?? null, $language),
            'url' => $site['url'] ?? '#',
            'is_active' => $site['is_active'] ?? false,
        ], $sites);
    }

    /**
     * Форматування меню
     */
    private function formatMenu(?array $menu, string $language): array
    {
        if (empty($menu)) {
            return [];
        }

        return array_map(fn ($item) => [
            'label' => $this->getTranslatedValue($item['label'] ?? null, $language),
            'url' => $item['url'] ?? '#',
        ], $menu);
    }

    /**
     * Форматування елементів співпраці
     */
    private function formatCollaborationItems(?array $items, string $language): array
    {
        if (empty($items)) {
            return [];
        }

        return array_map(fn ($item) => [
            'image' => ! empty($item['image']) ? asset('storage/'.$item['image']) : null,
            'text' => $this->getTranslatedValue($item['text'] ?? null, $language),
        ], $items);
    }

    /**
     * Форматування меню сайтів футера
     */
    private function formatSitesMenu(?array $sitesMenu, string $language): array
    {
        if (empty($sitesMenu)) {
            return [];
        }

        return array_map(fn ($site) => [
            'site_name' => $this->getTranslatedValue($site['site_name'] ?? null, $language),
            'site_url' => $site['site_url'] ?? '#',
            'links' => $this->formatLinksArray($site['links'] ?? [], $language),
        ], $sitesMenu);
    }

    /**
     * Форматування масиву посилань
     */
    private function formatLinksArray(array $links, string $language): array
    {
        return array_map(fn ($item) => [
            'label' => $this->getTranslatedValue($item['label'] ?? null, $language),
            'url' => $item['url'] ?? '#',
        ], $links);
    }

    /**
     * Отримати переклад з масиву або рядка
     */
    private function getTranslatedValue($value, string $language): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value[$language] ?? $value['uk'] ?? null;
        }

        return null;
    }

    /**
     * Отримати мову з запиту
     */
    private function getLanguage(Request $request): string
    {
        $language = $request->get('language', 'uk');

        if (! in_array($language, ['uk', 'en'])) {
            $language = 'uk';
        }

        return $language;
    }
}
