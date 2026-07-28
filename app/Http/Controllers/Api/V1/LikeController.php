<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArtCatalog;
use App\Models\ArtCatalogLike;
use App\Models\Project;
use App\Models\ProjectLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class LikeController extends Controller
{
    /**
     * Поставити лайк проєкту
     *
     * @OA\Post(
     *     path="/v1/projects/{project}/like",
     *     operationId="likeProject",
     *     tags={"Likes"},
     *     summary="Поставити лайк",
     *     description="Додає лайк до проекту від поточного користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Лайк додано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="is_liked", type="boolean", example=true),
     *             @OA\Property(property="likes_count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Вже лайкнуто")
     * )
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        // Перевіряємо, чи вже лайкнули
        $existingLike = ProjectLike::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'message' => 'Ви вже лайкнули цей проєкт.',
                'is_liked' => true,
                'likes_count' => $project->likes_count,
            ], 422);
        }

        // Створюємо лайк
        ProjectLike::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        // Оновлюємо лічильник
        $project->increment('likes_count');

        return response()->json([
            'message' => 'Лайк додано.',
            'is_liked' => true,
            'likes_count' => $project->fresh()->likes_count,
        ]);
    }

    /**
     * Прибрати лайк з проєкту
     *
     * @OA\Delete(
     *     path="/v1/projects/{project}/like",
     *     operationId="unlikeProject",
     *     tags={"Likes"},
     *     summary="Прибрати лайк",
     *     description="Видаляє лайк з проекту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="ID проекту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Лайк прибрано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="is_liked", type="boolean", example=false),
     *             @OA\Property(property="likes_count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Не було лайка")
     * )
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        $like = ProjectLike::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $like) {
            return response()->json([
                'message' => 'Ви не лайкали цей проєкт.',
                'is_liked' => false,
                'likes_count' => $project->likes_count,
            ], 422);
        }

        $like->delete();

        // Оновлюємо лічильник
        $project->decrement('likes_count');

        return response()->json([
            'message' => 'Лайк прибрано.',
            'is_liked' => false,
            'likes_count' => $project->fresh()->likes_count,
        ]);
    }

    /**
     * Поставити лайк каталогу
     *
     * @OA\Post(
     *     path="/v1/catalogs/{artCatalog}/like",
     *     operationId="likeArtCatalog",
     *     tags={"Likes"},
     *     summary="Поставити лайк каталогу",
     *     description="Додає лайк до PDF-каталогу від поточного користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="artCatalog", in="path", required=true, description="ID каталогу", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Лайк додано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="is_liked", type="boolean", example=true),
     *             @OA\Property(property="likes_count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Вже лайкнуто")
     * )
     */
    public function likeCatalog(Request $request, ArtCatalog $artCatalog): JsonResponse
    {
        $user = $request->user();

        $existingLike = ArtCatalogLike::where('art_catalog_id', $artCatalog->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'message' => 'Ви вже лайкнули цей каталог.',
                'is_liked' => true,
                'likes_count' => $artCatalog->likes_count,
            ], 422);
        }

        ArtCatalogLike::create([
            'art_catalog_id' => $artCatalog->id,
            'user_id' => $user->id,
        ]);

        $artCatalog->increment('likes_count');

        return response()->json([
            'message' => 'Лайк додано.',
            'is_liked' => true,
            'likes_count' => $artCatalog->fresh()->likes_count,
        ]);
    }

    /**
     * Прибрати лайк з каталогу
     *
     * @OA\Delete(
     *     path="/v1/catalogs/{artCatalog}/like",
     *     operationId="unlikeArtCatalog",
     *     tags={"Likes"},
     *     summary="Прибрати лайк каталогу",
     *     description="Видаляє лайк з PDF-каталогу",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="artCatalog", in="path", required=true, description="ID каталогу", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Лайк прибрано",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="is_liked", type="boolean", example=false),
     *             @OA\Property(property="likes_count", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Не було лайка")
     * )
     */
    public function unlikeCatalog(Request $request, ArtCatalog $artCatalog): JsonResponse
    {
        $user = $request->user();

        $like = ArtCatalogLike::where('art_catalog_id', $artCatalog->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $like) {
            return response()->json([
                'message' => 'Ви не лайкали цей каталог.',
                'is_liked' => false,
                'likes_count' => $artCatalog->likes_count,
            ], 422);
        }

        $like->delete();

        $artCatalog->decrement('likes_count');

        return response()->json([
            'message' => 'Лайк прибрано.',
            'is_liked' => false,
            'likes_count' => $artCatalog->fresh()->likes_count,
        ]);
    }
}
