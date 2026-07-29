<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Controller;
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
     *     path="/v1/art-ua-info/projects/{project}/like",
     *     operationId="artUaInfoLikeProject",
     *     tags={"Likes"},
     *     summary="Поставити лайк",
     *     description="Додає лайк до проекту від поточного користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="Slug проекту", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Лайк додано"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Вже лайкнуто")
     * )
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

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

        ProjectLike::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

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
     *     path="/v1/art-ua-info/projects/{project}/like",
     *     operationId="artUaInfoUnlikeProject",
     *     tags={"Likes"},
     *     summary="Прибрати лайк",
     *     description="Видаляє лайк з проекту",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, description="Slug проекту", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Лайк прибрано"),
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

        $project->decrement('likes_count');

        return response()->json([
            'message' => 'Лайк прибрано.',
            'is_liked' => false,
            'likes_count' => $project->fresh()->likes_count,
        ]);
    }
}
