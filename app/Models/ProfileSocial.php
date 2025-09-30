<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileSocial extends Model
{
    protected $fillable = [
        'user_id',
        'website',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'pinterest',
        'github',
        'telegram',
        'tiktok',
        'youtube_channel',
        'whatsapp',
        'deviantart',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
