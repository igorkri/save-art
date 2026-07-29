<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * Управління власними art-ua-info-проєктами (список/деталі/видалення). Копія
 * підмножини методів App\Http\Controllers\Api\V1\MyProjectController (save-art),
 * якою реально користується art-ua-info: index, completed, show, destroy —
 * скоуп завжди forArtUaInfo() (без ?source= перемикача). Створення/редагування —
 * окремий App\Http\Controllers\Api\V1\ArtUaInfo\ProjectController.
 *
 * @OA\Tag(
 *     name="My Projects (Art-UA Info)",
 *     description="API для управління власними art-ua-info-проєктами (кабінет митця)"
 * )
 */
class MyProjectController extends Controller
{
    /**
     * Отримати список власних art-ua-info-проєктів
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/projects",
     *     operationId="artUaInfoGetMyProjects",
     *     tags={"My Projects (Art-UA Info)"},
     *     summary="Список моїх art-ua-info-проєктів",
     *     description="Повертає список art-ua-info-проєктів авторизованого користувача (всі статуси).",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу (кілька значень через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список проєктів"),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->forArtUaInfo()
            ->with(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $statuses = array_filter(explode(',', (string) $request->input('status')));
            $query->whereIn('status', $statuses);
        } else {
            $query->whereIn('status', [
                ProjectStatus::Moderation,
                ProjectStatus::Announced,
                ProjectStatus::InProgress,
                ProjectStatus::Paused,
                ProjectStatus::Completed,
                ProjectStatus::Sold,
                ProjectStatus::Rejected,
            ]);
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Отримати завершені art-ua-info-проєкти
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/projects/completed",
     *     operationId="artUaInfoGetMyCompletedProjects",
     *     tags={"My Projects (Art-UA Info)"},
     *     summary="Список моїх завершених art-ua-info-проєктів",
     *     description="Повертає завершені проєкти (completed, sold) авторизованого користувача.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список завершених проєктів"),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function completed(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->forArtUaInfo()
            ->with(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue'])
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [ProjectStatus::Completed, ProjectStatus::Sold])
            ->orderBy('created_at', 'desc');

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Деталі власного art-ua-info-проєкту
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/projects/{project}",
     *     operationId="artUaInfoGetMyProject",
     *     tags={"My Projects (Art-UA Info)"},
     *     summary="Деталі проєкту",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Деталі проєкту"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту")
     * )
     */
    public function show(Request $request, Project $project): ProjectResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new ProjectResource($project->load(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }

    /**
     * Видалити власний art-ua-info-проєкт
     *
     * @OA\Delete(
     *     path="/v1/art-ua-info/my/projects/{project}",
     *     operationId="artUaInfoDeleteMyProject",
     *     tags={"My Projects (Art-UA Info)"},
     *     summary="Видалити проєкт",
     *     description="Доступно тільки для чернеток, відхилених проєктів або проєктів у черзі на модерацію.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Проєкт видалено"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Проєкт не можна видалити у цьому статусі")
     * )
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $project->canBeDeletedByOwner()) {
            return response()->json([
                'message' => 'Можна видалити лише чернетки, відхилені проєкти або проєкти в черзі на модерацію. Для інших проєктів зверніться до модератора.',
            ], 422);
        }

        if ($project->cover) {
            Storage::disk('public')->delete($project->cover);
        }

        $project->delete();

        return response()->json(['message' => 'Проєкт видалено']);
    }
}
