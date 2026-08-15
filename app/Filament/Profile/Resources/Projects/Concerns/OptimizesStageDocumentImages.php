<?php

namespace App\Filament\Profile\Resources\Projects\Concerns;

use App\Jobs\GenerateStageDocumentPdfThumbnail;
use App\Jobs\OptimizeStageDocumentImage;
use App\Models\Project;

/**
 * Диспатчить у чергу обробку нових документів етапу (project_stages.documents):
 * стиснення фото та генерацію мініатюри для PDF — щоб важка обробка не блокувала
 * відповідь на save().
 */
trait OptimizesStageDocumentImages
{
    protected function dispatchOptimizationForAllStageDocuments(Project $project): void
    {
        foreach ($project->stages as $stage) {
            $this->dispatchDocumentProcessingJobs($stage->documents ?? []);
        }
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $previousDocumentsByStageId
     */
    protected function dispatchOptimizationForNewStageDocuments(Project $project, array $previousDocumentsByStageId): void
    {
        foreach ($project->stages as $stage) {
            $previousPaths = collect($previousDocumentsByStageId[$stage->id] ?? [])
                ->pluck('file')
                ->filter()
                ->all();

            $newDocuments = collect($stage->documents ?? [])
                ->reject(fn (array $document): bool => in_array($document['file'] ?? null, $previousPaths, true))
                ->all();

            $this->dispatchDocumentProcessingJobs($newDocuments);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function dispatchDocumentProcessingJobs(array $documents): void
    {
        foreach ($documents as $document) {
            $path = $document['file'] ?? null;

            if (blank($path)) {
                continue;
            }

            if (($document['type'] ?? null) === 'photo') {
                OptimizeStageDocumentImage::dispatch($path);
            } elseif (($document['type'] ?? null) === 'document') {
                GenerateStageDocumentPdfThumbnail::dispatch($path);
            }
        }
    }
}
