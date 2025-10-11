<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'feats' => $this->formatFeats(),
            'description' => $this->formatDescription(),
            'goals' => $this->goals,
            'tasks' => $this->tasks,
            'implementation' => $this->implementation,
            'results' => $this->results,
            'id_art' => $this->id_art,
            'events' => $this->events,
            'project' => $this->project,
            'artists' => $this->artists,
            'is_active_artist' => $this->is_active_artist,
            'partners' => $this->partners,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return $this->formatAllImages($data);
    }

    /**
     * Format feats data with proper image URLs
     */
    private function formatFeats(): ?array
    {
        if (! $this->feats) {
            return null;
        }

        $feats = $this->feats;

        // Add full URL for feat_image if exists
        if (isset($feats['feat_image'])) {
            $feats['feat_image'] = $feats['feat_image'] ? asset('storage/'.$feats['feat_image']) : null;
        }

        return $feats;
    }

    /**
     * Format description data with proper image URLs
     */
    private function formatDescription(): ?array
    {
        if (! $this->description) {
            return null;
        }

        $description = $this->description;

        // Add full URLs for images if they exist
        if (isset($description['icon'])) {
            $description['icon'] = $description['icon'] ? asset('storage/'.$description['icon']) : null;
        }

        if (isset($description['image'])) {
            $description['image'] = $description['image'] ? asset('storage/'.$description['image']) : null;
        }

        return $description;
    }

    /**
     * Format all data with proper image URLs
     */
    private function formatAllImages(array $data): array
    {
        // Format goals images
        if (isset($data['goals']['image'])) {
            $data['goals']['image'] = $data['goals']['image'] ? asset('storage/'.$data['goals']['image']) : null;
        }

        // Format implementation images
        if (isset($data['implementation']['image'])) {
            $data['implementation']['image'] = $data['implementation']['image'] ? asset('storage/'.$data['implementation']['image']) : null;
        }

        // Format results images
        if (isset($data['results']['image'])) {
            $data['results']['image'] = $data['results']['image'] ? asset('storage/'.$data['results']['image']) : null;
        }

        // Format id_art images
        if (isset($data['id_art']['image'])) {
            $data['id_art']['image'] = $data['id_art']['image'] ? asset('storage/'.$data['id_art']['image']) : null;
        }

        // Format project images
        if (isset($data['project']['image'])) {
            $data['project']['image'] = $data['project']['image'] ? asset('storage/'.$data['project']['image']) : null;
        }
        if (isset($data['project']['image_bg'])) {
            $data['project']['image_bg'] = $data['project']['image_bg'] ? asset('storage/'.$data['project']['image_bg']) : null;
        }

        return $data;
    }
}
