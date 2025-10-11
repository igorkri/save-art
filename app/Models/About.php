<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    protected $table = 'about';

    protected $fillable = [
        'title',
        'feats',
        'description',
        'goals',
        'tasks',
        'implementation',
        'results',
        'id_art',
        'events',
        'project',
        'artists',
        'is_active_artist',
        'partners',
    ];

    protected $casts = [
        'title' => 'array',
        'feats' => 'array',
        'description' => 'array',
        'goals' => 'array',
        'tasks' => 'array',
        'implementation' => 'array',
        'results' => 'array',
        'id_art' => 'array',
        'events' => 'array',
        'project' => 'array',
        'artists' => 'array',
        'partners' => 'array',
        'is_active_artist' => 'boolean',
    ];


}
