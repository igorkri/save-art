<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilePersonal extends Model
{
    protected $fillable = [
        'user_id',
        'avatar',
        'full_name',
        'profession',
        'tags',
        'country',
        'region',
        'city',
        'postal_code',
        'role',
        'description',
    ];

    protected $casts = [
        'full_name' => 'array',
        'profession' => 'array',
        'tags' => 'array',
        'country' => 'array',
        'region' => 'array',
        'city' => 'array',
        'description' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
