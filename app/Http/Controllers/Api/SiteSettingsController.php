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
        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => [
                'header' => $this->formatHeader($settings),
                'footer' => $this->formatFooter($settings),
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
        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatHeader($settings),
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
        $settings = SiteSettings::first();

        if (! $settings) {
            return response()->json([
                'message' => 'Налаштування сайту не знайдені',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatFooter($settings),
        ]);
    }

    /**
     * Форматування даних хедера
     */
    private function formatHeader(SiteSettings $settings): array
    {
        return [
            'logo' => $settings->site_logo ? asset('storage/'.$settings->site_logo) : null,
            'brand_name' => $settings->header_brand_name,
            'dropdown_sites' => $this->formatDropdownSites($settings->header_dropdown_sites),
            'menu' => $this->formatMenu($settings->header_menu),
            'socials' => $settings->header_socials,
            'support_button' => [
                'url' => $settings->header_support_button_url,
                'text' => $settings->header_support_button_text,
            ],
            'login_button' => [
                'text' => $settings->header_login_button_text,
            ],
        ];
    }

    /**
     * Форматування даних футера
     */
    private function formatFooter(SiteSettings $settings): array
    {
        return [
            'top' => [
                'brand_name' => $settings->footer_brand_name,
                'slogan' => $settings->footer_slogan,
                'collaboration' => [
                    'title' => $settings->footer_collaboration_title,
                    'text' => $settings->footer_collaboration_text,
                    'items' => $this->formatCollaborationItems($settings->footer_collaboration_items),
                    'button_text' => $settings->footer_collaboration_button_text,
                ],
            ],
            'middle' => [
                'sites_menu' => $this->formatSitesMenu($settings->footer_sites_menu),
            ],
            'bottom' => [
                'company_name' => $settings->footer_company_name,
                'address' => $settings->footer_address,
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
    private function formatDropdownSites(?array $sites): array
    {
        if (empty($sites)) {
            return [];
        }

        return array_map(fn ($site) => [
            'name' => $site['name'] ?? null,
            'url' => $site['url'] ?? '#',
            'is_active' => $site['is_active'] ?? false,
        ], $sites);
    }

    /**
     * Форматування меню
     */
    private function formatMenu(?array $menu): array
    {
        if (empty($menu)) {
            return [];
        }

        return array_map(fn ($item) => [
            'label' => $item['label'] ?? null,
            'url' => $item['url'] ?? '#',
        ], $menu);
    }

    /**
     * Форматування елементів співпраці
     */
    private function formatCollaborationItems(?array $items): array
    {
        if (empty($items)) {
            return [];
        }

        return array_map(fn ($item) => [
            'image' => ! empty($item['image']) ? asset('storage/'.$item['image']) : null,
            'text' => $item['text'] ?? null,
        ], $items);
    }

    /**
     * Форматування меню сайтів футера
     */
    private function formatSitesMenu(?array $sitesMenu): array
    {
        if (empty($sitesMenu)) {
            return [];
        }

        return array_map(fn ($site) => [
            'site_name' => $site['site_name'] ?? null,
            'site_url' => $site['site_url'] ?? '#',
            'links' => $this->formatLinksArray($site['links'] ?? []),
        ], $sitesMenu);
    }

    /**
     * Форматування масиву посилань
     */
    private function formatLinksArray(array $links): array
    {
        return array_map(fn ($item) => [
            'label' => $item['label'] ?? null,
            'url' => $item['url'] ?? '#',
        ], $links);
    }
}
