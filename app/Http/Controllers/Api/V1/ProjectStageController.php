<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateStageRequest;
use App\Http\Requests\Api\V1\UpdateStageRequest;
use App\Http\Resources\Api\V1\ProjectStageResource;
use App\Models\Project;
use App\Models\ProjectStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectStageController extends Controller
{
    /**
     * Отримати список етапів проєкту
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $stages = $project->stages()->orderBy('order')->get();

        return ProjectStageResource::collection($stages);
    }

    /**
     * Створити новий етап
     */
    public function store(CreateStageRequest $request, Project $project): ProjectStageResource|JsonResponse
    {
        if (! $project->isEditable()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        $data = $request->validated();

        $maxOrder = $project->stages()->max('order') ?? 0;

        $stage = $project->stages()->create([
            ...$data,
            'order' => $maxOrder + 1,
            'status' => StageStatus::Planned,
        ]);

        return new ProjectStageResource($stage);
    }

    /**
     * Оновити етап
     */
    public function update(UpdateStageRequest $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validated();

        $stage->update($data);

        return new ProjectStageResource($stage);
    }

    /**
     * Видалити етап
     */
    public function destroy(Request $request, Project $project, ProjectStage $stage): JsonResponse
    {
        // Перевіряємо доступ
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (! $project->isEditable()) {
            return response()->json(['message' => 'Проєкт не можна редагувати'], 422);
        }

        $stage->delete();

        return response()->json(['message' => 'Етап видалено']);
    }

    /**
     * Почати виконання етапу
     */
    public function start(Request $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $stage->update([
            'status' => StageStatus::InProgress,
            'started_at' => now(),
        ]);

        return new ProjectStageResource($stage);
    }

    /**
     * Завершити етап
     */
    public function complete(Request $request, Project $project, ProjectStage $stage): ProjectStageResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($stage->project_id !== $project->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $stage->update([
            'status' => StageStatus::Completed,
            'completed_at' => now(),
        ]);

        return new ProjectStageResource($stage);
    }
}
