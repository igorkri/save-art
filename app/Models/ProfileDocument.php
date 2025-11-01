<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ProfileDocument
 *
 * @property int $id
 * @property int $user_id
 * @property string $file_path
 * @property string $hash
 * @property string|null $signed_file_path
 * @property string $sign_status
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string $service
 * @property string|null $signature_base64
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereSignedFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereSignStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereSignatureBase64($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileDocument whereUserId($value)
 * @mixin \Eloquent
 *
 * The ProfileDocument model represents documents associated with user profiles that can be signed electronically.
 * It includes fields for tracking the file paths, signing status, and related metadata.
 *
 * Relationships:
 * - user: Belongs to a User model, indicating the owner of the document.
 *
 */
class ProfileDocument extends Model
{
    protected $table = 'profile_documents';

    protected $fillable = [
        'user_id',
        'file_path',
        'hash',
        'signed_file_path',
        'sign_status',
        'signed_at',
        'service',
        'signature_base64',
    ];

    // cast signed_at to a datetime object
    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
