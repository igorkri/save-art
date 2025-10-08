<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProfileSocial extends Model
{
    use HasFactory;
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
