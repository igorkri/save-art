<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtistBoard extends Model
{
    protected $table = 'artist_boards';

    protected $fillable = [
        'titles',
        'logo_museums',
        'descriptions',
        'data',
    ];

    protected $casts = [
        'titles' => 'array',
        'logo_museums' => 'array',
        'data' => 'array',
    ];
}
