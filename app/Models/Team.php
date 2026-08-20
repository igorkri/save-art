<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $avatar
 * @property string|null $website
 * @property string|null $country
 * @property string|null $city
 * @property string|null $region
 * @property string|null $zip
 * @property string|null $description
 * @property string|null $specialization
 * @property array|null $social_links
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TeamMember> $teamMembers
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Service> $services
 */
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'avatar',
        'website',
        'country',
        'city',
        'region',
        'zip',
        'description',
        'specialization',
        'social_links',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->orderBy('sort_order');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')->withPivot('role', 'sort_order')->withTimestamps()->orderBy('team_members.sort_order');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->teamMembers()->where('user_id', $user->id)->where('role', 'owner')->exists();
    }

    public function services(): MorphMany
    {
        return $this->morphMany(Service::class, 'serviceable');
    }
}
