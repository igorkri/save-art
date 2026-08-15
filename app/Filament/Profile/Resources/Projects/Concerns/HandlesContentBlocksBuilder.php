<?php

namespace App\Filament\Profile\Resources\Projects\Concerns;

use Illuminate\Support\Arr;

/**
 * Filament Builder зберігає стан кожного блоку як ['type' => ..., 'data' => [...]],
 * а в БД (та API-контракті content_blocks) кожен блок — плаский масив
 * ['type' => ..., ...поля]. Ці методи конвертують між двома форматами на межі
 * форми, щоб зміна UI-компонента (Repeater -> Builder) не торкалась моделі й API.
 */
trait HandlesContentBlocksBuilder
{
    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     * @return array<int, array<string, mixed>>
     */
    protected function contentBlocksToBuilderFormat(?array $blocks): array
    {
        return collect($blocks ?? [])
            ->map(fn (array $block): array => [
                'type' => $block['type'] ?? 'paragraph',
                'data' => Arr::except($block, 'type'),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     * @return array<int, array<string, mixed>>
     */
    protected function contentBlocksFromBuilderFormat(?array $blocks): array
    {
        return collect($blocks ?? [])
            ->map(fn (array $block): array => [
                'type' => $block['type'] ?? 'paragraph',
                ...($block['data'] ?? []),
            ])
            ->values()
            ->all();
    }
}
