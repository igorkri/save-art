<?php

namespace App\Models;

use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $order
 * @property string $status
 * @property string $title
 * @property string|null $description
 * @property float $budget_planned
 * @property float|null $budget_actual
 * @property int|null $days_planned
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property array|null $documents
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Project $project
 */
class ProjectStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'order',
        'status',
        'title',
        'description',
        'budget_planned',
        'budget_actual',
        'days_planned',
        'started_at',
        'completed_at',
        'documents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'budget_planned' => 'decimal:2',
            'budget_actual' => 'decimal:2',
            'status' => StageStatus::class,
            'started_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function setTitleAttribute(mixed $value): void
    {
        $this->attributes['title'] = is_array($value) ? ($value['uk'] ?? '') : $value;
    }

    public function setDescriptionAttribute(mixed $value): void
    {
        $this->attributes['description'] = is_array($value) ? ($value['uk'] ?? null) : $value;
    }

    public function setDocumentsAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['documents'] = null;

            return;
        }

        foreach ($value as &$document) {
            if (is_array($document) && is_array($document['description'] ?? null)) {
                $document['description'] = $document['description']['uk'] ?? null;
            }
        }
        unset($document);

        $this->attributes['documents'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Проєкт, до якого належить етап
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Чи завершено етап
     */
    public function isCompleted(): bool
    {
        return $this->status === StageStatus::Completed;
    }

    /**
     * Чи в процесі етап
     */
    public function isInProgress(): bool
    {
        return $this->status === StageStatus::InProgress;
    }

    /**
     * Різниця між запланованим та фактичним бюджетом
     */
    public function getBudgetDifference(): ?float
    {
        if ($this->budget_actual === null) {
            return null;
        }

        return $this->budget_planned - $this->budget_actual;
    }
}
