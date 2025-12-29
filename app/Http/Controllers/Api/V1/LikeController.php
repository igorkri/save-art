<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Поставити лайк проєкту
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
}
