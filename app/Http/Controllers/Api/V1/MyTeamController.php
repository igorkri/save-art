<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Requests\Api\V1\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * API для управління власними командами (кабінет митця)
 */
class MyTeamController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список команд, де користувач є учасником
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = $request->user()->teams()->with('members')->get();

        return TeamResource::collection($teams);
    }

    /**
     * Створити команду (поточний користувач стає власником)
     */
    public function store(StoreTeamRequest $request): TeamResource
    {
        $data = $request->validated();
        $members = $data['members'] ?? [];
        unset($data['members']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('teams', 'public');
        } elseif (! empty($data['avatar'])) {
            $data['avatar'] = $this->imageProcessor->saveBase64Image($data['avatar'], 'teams');
        }

        $data['slug'] = Str::slug($data['name']['uk']).'-'.Str::random(6);

        $team = DB::transaction(function () use ($data, $members, $request) {
            $team = Team::create($data);

            $team->teamMembers()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
                'sort_order' => 0,
            ]);

            foreach ($members as $index => $member) {
                if ((int) $member['user_id'] === $request->user()->id) {
                    continue;
                }
                $team->teamMembers()->create([
                    'user_id' => $member['user_id'],
                    'role' => 'member',
                    'sort_order' => $index + 1,
                ]);
            }

            return $team;
        });

        return new TeamResource($team->load('members'));
    }

    /**
     * Оновити команду (лише власник)
     */
    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $this->authorizeOwner($request, $team);

        $data = $request->validated();
        $members = $data['members'] ?? null;
        unset($data['members']);

        if ($request->hasFile('avatar')) {
            if ($team->avatar) {
                Storage::disk('public')->delete($team->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('teams', 'public');
        } elseif (! empty($data['avatar'])) {
            $data['avatar'] = $this->imageProcessor->processCover($data['avatar'], $team->avatar);
        }

        $team->fill($data);
        $team->save();

        if ($members !== null) {
            $ownerId = $request->user()->id;
            $team->teamMembers()->where('user_id', '!=', $ownerId)->delete();

            foreach ($members as $index => $member) {
                if ((int) $member['user_id'] === $ownerId) {
                    continue;
                }
                $team->teamMembers()->create([
                    'user_id' => $member['user_id'],
                    'role' => 'member',
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return new TeamResource($team->load('members'));
    }

    /**
     * Видалити команду (лише власник)
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        $this->authorizeOwner($request, $team);

        if ($team->avatar) {
            Storage::disk('public')->delete($team->avatar);
        }
        $team->teamMembers()->delete();
        $team->delete();

        return response()->json(['message' => 'Команду видалено']);
    }

    /**
     * Вийти з команди (для учасника, що не є власником)
     */
    public function leave(Request $request, Team $team): JsonResponse
    {
        $membership = $team->teamMembers()->where('user_id', $request->user()->id)->first();
        abort_if(! $membership, 404, 'Ви не є учасником цієї команди');
        abort_if($membership->role === 'owner', 422, 'Власник не може покинути команду, лише видалити її');

        $membership->delete();

        return response()->json(['message' => 'Ви покинули команду']);
    }

    private function authorizeOwner(Request $request, Team $team): void
    {
        abort_if(! $team->isOwnedBy($request->user()), 403, 'Ви не є власником цієї команди');
    }
}
