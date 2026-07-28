<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property string|null $avatar
 * @property string|null $website
 * @property array|null $country
 * @property array|null $city
 * @property array|null $description
 * @property array|null $social_links
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TeamMember> $teamMembers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
 */
class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'avatar',
        'website',
        'country',
        'city',
        'description',
        'social_links',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'country' => 'array',
            'city' => 'array',
            'description' => 'array',
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
        return $this->belongsToMany(User::class, 'team_members')->withPivot('sort_order')->withTimestamps()->orderBy('team_members.sort_order');
    }

    public function services(): MorphMany
    {
        return $this->morphMany(Service::class, 'serviceable');
    }
}
