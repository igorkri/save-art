<?php

namespace App\Http\Requests\Api\V1\Concerns;

trait NormalizesProjectUkrainianFields
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['title', 'short_description'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->ukValue($data[$field]);
            }
        }

        if (array_key_exists('tags', $data)) {
            $data['tags'] = $this->normalizeTags($data['tags']);
        }

        $this->normalizeRows($data, 'budget_items', ['name']);
        $this->normalizeRows($data, 'content_blocks', [
            'heading_text',
            'paragraph_text',
            'image_alt',
            'image_caption',
        ]);
        $this->normalizeRows($data, 'stages', ['title', 'description']);
        $this->normalizeRows($data, 'bonuses', ['title', 'description']);
        $this->normalizeRows($data, 'parameters', ['custom_value']);

        foreach (['title', 'description'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->ukValue($data[$field]);
            }
        }

        $this->replace($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     */
    private function normalizeRows(array &$data, string $key, array $fields): void
    {
        if (! is_array($data[$key] ?? null)) {
            return;
        }

        foreach ($data[$key] as &$row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $this->ukValue($row[$field]);
                }
            }
        }
        unset($row);
    }

    private function ukValue(mixed $value): mixed
    {
        if (! is_array($value) || (! array_key_exists('uk', $value) && ! array_key_exists('en', $value))) {
            return $value;
        }

        $value = $value['uk'] ?? null;

        return is_array($value) ? implode(', ', $value) : $value;
    }

    private function normalizeTags(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && (array_key_exists('uk', $value) || array_key_exists('en', $value))) {
            $value = $value['uk'] ?? [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_values(array_filter(
            array_map(static fn (mixed $tag): mixed => is_string($tag) ? trim($tag) : $tag, $value),
            static fn (mixed $tag): bool => $tag !== '',
        ));
    }
}
