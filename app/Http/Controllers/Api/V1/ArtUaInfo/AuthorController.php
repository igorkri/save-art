<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProfileType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtistResource;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\UserPhotoResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Спільна логіка для публічних профілів "авторів" (митців і організацій) art-ua-info —
 * обидва зберігаються в таблиці users, відрізняються лише profile_type.
 *
 * Копія App\Http\Controllers\Api\V1\AuthorController (save-art), єдина відмінність —
 * forArtUaInfo() замість forSaveArt() у проєктах автора.
 */
abstract class AuthorController extends Controller
{
    /**
     * Тип профілю, за яким фільтрується вибірка. `null` — без фільтра.
     */
    abstract protected function profileType(): ?ProfileType;

    protected function indexQuery(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->withCount([
                'projects' => fn ($q) => $q->forArtUaInfo()->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->whereHas('projects', fn ($q) => $q->forArtUaInfo()->whereIn('status', ProjectStatus::publicStatuses()))
            ->orderByDesc('projects_count');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(full_name, '$.uk'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(full_name, '$.en'))) LIKE ?", ["%{$search}%"]);
            });
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
                'projects' => fn ($q) => $q->forArtUaInfo()->whereIn('status', ProjectStatus::publicStatuses()),
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
            ->forArtUaInfo()
            ->with(['user.profileLegal'])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    protected function photosQuery(string $slug): AnonymousResourceCollection
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->where('slug', $slug)
            ->firstOrFail();

        return UserPhotoResource::collection($author->photos()->get());
    }
}
