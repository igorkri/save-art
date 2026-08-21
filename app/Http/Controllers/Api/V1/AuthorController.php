<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProfileType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtistResource;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Спільна логіка для публічних профілів "авторів" (митців і організацій) —
 * обидва зберігаються в таблиці users, відрізняються лише profile_type.
 */
abstract class AuthorController extends Controller
{
    /**
     * Тип профілю, за яким фільтрується вибірка. `null` — без фільтра
     * (як у "Artists", де для сумісності зі старою поведінкою тип не звужується).
     */
    abstract protected function profileType(): ?ProfileType;

    protected function indexQuery(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->withCount([
                'projects' => fn ($q) => $q->forSaveArt()->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->whereHas('projects', fn ($q) => $q->forSaveArt()->whereIn('status', ProjectStatus::publicStatuses()))
            ->orderByDesc('projects_count');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"]);
        }

        $perPage = min($request->input('per_page', 20), 50);
        $authors = $query->paginate($perPage);

        return ArtistResource::collection($authors);
    }

    protected function showQuery(string $slug): ArtistResource
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->with(['profileSocial'])
            ->withCount([
                'projects' => fn ($q) => $q->forSaveArt()->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ArtistResource($author);
    }

    protected function projectsQuery(string $slug, Request $request): AnonymousResourceCollection
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->where('slug', $slug)
            ->firstOrFail();

        $query = $author->projects()
            ->forSaveArt()
            ->with(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue'])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }
}
