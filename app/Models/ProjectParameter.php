<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Значення характеристики (Parameter), обране для конкретного проєкту.
 *
 * @property int $id
 * @property int $project_id
 * @property int $parameter_id
 * @property int|null $parameter_value_id
 * @property string|null $custom_value
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\Parameter $parameter
 * @property-read \App\Models\ParameterValue|null $parameterValue
 */
class ProjectParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'parameter_id',
        'parameter_value_id',
        'custom_value',
    ];

    public function setCustomValueAttribute(mixed $value): void
    {
        $this->attributes['custom_value'] = is_array($value) ? ($value['uk'] ?? null) : $value;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }

    public function parameterValue(): BelongsTo
    {
        return $this->belongsTo(ParameterValue::class);
    }
}
