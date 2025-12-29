<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtistResource;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtistController extends Controller
{
    /**
     * Отримати список митців (користувачів з проєктами)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->with(['profilePersonal'])
            ->withCount([
                'projects' => fn ($q) => $q->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->having('projects_count', '>', 0)
            ->orderBy('projects_count', 'desc');

        // Пошук по імені
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $perPage = min($request->input('per_page', 20), 50);
        $artists = $query->paginate($perPage);

        return ArtistResource::collection($artists);
    }

    /**
     * Отримати профіль митця
     */
    public function show(string $slug): ArtistResource
    {
        $artist = User::query()
            ->with(['profilePersonal', 'profileSocial'])
            ->withCount([
                'projects' => fn ($q) => $q->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ArtistResource($artist);
    }

    /**
     * Отримати проєкти митця
     */
    public function projects(string $slug, Request $request): AnonymousResourceCollection
    {
        $artist = User::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $query = $artist->projects()
            ->with('user')
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc');

        // Фільтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }
}
