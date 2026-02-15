<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'status',
        'data',
    ];

    // Статуси черновика
    const STATUS_EXPORTED = 'exported';

    const STATUS_ARCHIVED = 'archived';

    const STATUS_DELETED = 'deleted';

    const STATUS_NEW = 'new'; // default status for new drafts

    // при создании нового черновика, устанавливаем статус 'new'
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_NEW;
            }
        });
    }

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
