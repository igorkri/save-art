<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DonationResource;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class PublicUserController extends Controller
{
    /**
     * Отримати публічний профіль користувача
     *
     * @OA\Get(
     *     path="/v1/users/{id}",
     *     operationId="getPublicUser",
     *     tags={"Users"},
     *     summary="Публічний профіль користувача",
     *     description="Повертає публічну інформацію про користувача та статистику",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID користувача", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Профіль користувача",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="avatar", type="string"),
     *                     @OA\Property(property="statistics", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Користувача не знайдено")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::query()
            ->with(['profileSocial'])
            ->findOrFail($id);

        // Рахуємо статистику
        $projectsCount = $user->projects()
            ->forSaveArt()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->count();

        $totalCollected = $user->projects()
            ->forSaveArt()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->sum('budget_collected');

        $supportersCount = $user->projects()
            ->forSaveArt()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->sum('donors_count');

        $social = $user->profileSocial;

        return response()->json([
            'result' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
                    'role' => $user->role->value ?? 'user',
                    'profile_type' => $user->profile_type?->value,
                    'profession' => $user->profession,
                    'description' => $user->description,
                    'country' => $user->country,
                    'city' => $user->city,
                    'social_links' => [
                        'website' => $social?->website ?? null,
                        'instagram' => $social?->instagram ?? null,
                        'facebook' => $social?->facebook ?? null,
                        'youtube' => $social?->youtube ?? null,
                        'tiktok' => $social?->tiktok ?? null,
                    ],
                    'statistics' => [
                        'projects_count' => $projectsCount,
                        'total_collected' => (float) $totalCollected,
                        'supporters_count' => $supportersCount,
                    ],
                    'member_since' => $user->created_at->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Отримати публічні проєкти користувача
     *
     * @OA\Get(
     *     path="/v1/users/{id}/projects",
     *     operationId="getUserProjects",
     *     tags={"Users"},
     *     summary="Проекти користувача",
     *     description="Повертає публічні проекти користувача з пагінацією",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID користувача", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=10)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проектів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="projects", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Користувача не знайдено")
     * )
     */
    public function projects(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $language = $this->getLanguage($request);

        $perPage = min((int) $request->input('per_page', 10), 50);

        $projects = $user->projects()
            ->forSaveArt()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $formattedProjects = $projects->getCollection()->map(function (Project $project) use ($language) {
            return [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $this->localizeValue($project->title, $language),
                'collected' => (float) $project->budget_collected,
                'need_to_collect' => (float) $project->budget_goal,
                'people_supported' => $project->donors_count,
                'project_image' => $project->cover ? Storage::url($project->cover) : null,
                'project_status' => $language ? [
                    'key' => $project->status->value,
                    'label' => $project->status->getLabel($language),
                ] : [
                    'key' => $project->status->value,
                    'uk' => $project->status->getLabel('uk'),
                    'en' => $project->status->getLabel('en'),
                ],
                'art_form' => $project->getArtCategorySlug(),
                'art_subform' => $project->getArtSubcategorySlug(),
                'date_of_announcement' => $project->announced_at?->toDateString(),
            ];
        });

        return response()->json([
            'result' => true,
            'data' => [
                'projects' => $formattedProjects,
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                    'total_pages' => $projects->lastPage(),
                    'has_next' => $projects->hasMorePages(),
                    'has_prev' => $projects->currentPage() > 1,
                ],
            ],
        ]);
    }

    /**
     * Отримати публічні донати користувача (як мецената)
     *
     * @OA\Get(
     *     path="/v1/users/{id}/donations",
     *     operationId="getUserDonations",
     *     tags={"Users"},
     *     summary="Публічні донати користувача",
     *     description="Повертає проєкти, які користувач публічно підтримав як меценат (оплачені, не анонімні, не приховані з профілю)",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID користувача", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список публічних донатів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Donation")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Користувача не знайдено")
     * )
     */
    public function donations(Request $request, int $id): AnonymousResourceCollection
    {
        $user = User::findOrFail($id);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $donations = Donation::query()
            ->with(['project.user', 'bonus'])
            ->where('user_id', $user->id)
            ->where('donation_type', Donation::TYPE_PROJECT)
            ->where('status', 'paid')
            ->where('is_anonymous', false)
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return DonationResource::collection($donations);
    }

    /**
     * Отримати мову з запиту
     */
    private function getLanguage(Request $request): ?string
    {
        $language = $request->query('language');

        return ($language && in_array($language, ['uk', 'en'])) ? $language : null;
    }

    /**
     * Локалізувати значення поля
     */
    private function localizeValue(mixed $value, ?string $language): mixed
    {
        if ($language === null || ! is_array($value)) {
            return $value;
        }

        if (isset($value['uk']) || isset($value['en'])) {
            return $value[$language] ?? $value['uk'] ?? reset($value);
        }

        return $value;
    }
}
