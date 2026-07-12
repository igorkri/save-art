<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Concerns\LocalizesFields;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Search",
 *     description="Швидкий пошук з підказками (автори + проєкти)"
 * )
 */
class SearchController extends Controller
{
    use LocalizesFields;

    private const SUGGESTIONS_LIMIT = 5;

    /**
     * Підказки пошуку: одночасно проєкти та автори за введеним текстом.
     *
     * @OA\Get(
     *     path="/v1/search/suggest",
     *     operationId="searchSuggest",
     *     tags={"Search"},
     *     summary="Підказки пошуку (автори + проєкти)",
     *     description="Повертає до 5 проєктів і до 5 митців, що відповідають запиту",
     *
     *     @OA\Parameter(name="query", in="query", required=true, description="Пошуковий запит (мінімум 2 символи)", @OA\Schema(type="string"), example="Іван"),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Підказки",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="projects", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="artists", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function suggest(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->input('query'));
        $language = $this->getLanguage($request);

        if (mb_strlen($rawQuery) < 2) {
            return response()->json(['projects' => [], 'artists' => []]);
        }

        $query = mb_strtolower($rawQuery);

        $projects = Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where(function ($q) use ($query) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk'))) LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))) LIKE ?", ["%{$query}%"]);
            })
            ->limit(self::SUGGESTIONS_LIMIT)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $this->localizeField($project->title, $language),
                'cover_url' => $project->cover ? Storage::url($project->cover) : null,
            ]);

        $artists = User::query()
            ->whereHas('projects', fn ($q) => $q->whereIn('status', ProjectStatus::publicStatuses()))
            ->where(function ($q) use ($query) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(full_name, '$.uk'))) LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(full_name, '$.en'))) LIKE ?", ["%{$query}%"]);
            })
            ->limit(self::SUGGESTIONS_LIMIT)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'slug' => $user->slug,
                'name' => $this->localizeField($user->full_name, $language),
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            ]);

        return response()->json([
            'projects' => $projects,
            'artists' => $artists,
        ]);
    }
}
