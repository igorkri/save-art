<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Content
 *
 * @package App\Models
 *
 * @property int $id
 * @property array|null $page_title
 * @property array|null $title
 * @property string $slug
 * @property array|null $content
 * @property array|null $meta_title
 * @property array|null $meta_description
 * @property array|null $meta_keywords
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

class Content extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_title',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
    ];

    /**
     *
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'page_title' => 'array',
        'title' => 'array',
        'content' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
    ];


}
