<?php

namespace App\Filament\Resources\ArtCategories\Pages;

use App\Filament\Resources\ArtCategories\ArtCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Openplain\FilamentTreeView\Resources\Pages\TreePage;

class ListArtCategories extends TreePage
{
    protected static string $resource = ArtCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Переопределяємо для показу notification після збереження.
     */
    public function reorderTree(array $moves): void
    {
        if (empty($moves)) {
            return;
        }

        foreach ($moves as $moveData) {
            $this->processSingleMove($moveData);
        }

        Notification::make()
            ->title('Зміни збережено')
            ->success()
            ->send();

        $this->dispatch('tree-reordered');
    }

    /**
     * Переопределяємо для коректної обробки parent_id.
     * Frontend надсилає -1 або '-1' для кореневих елементів.
     */
    protected function processSingleMove(array $data): void
    {
        $nodeId = $data['nodeId'];
        $newParentId = $data['newParentId'] ?? -1;
        $position = $data['position'] ?? 'after';
        $referenceId = $data['referenceId'] ?? null;

        $node = (clone $this->getTree()->getQuery())->find($nodeId);

        if (! $node) {
            return;
        }

        $parentKeyName = $node->getParentKeyName();
        $oldParentId = $node->{$parentKeyName};

        // Конвертуємо -1 або '-1' в null для кореневих елементів
        // Використовуємо loose comparison (==) замість strict (===)
        $rootValue = $node->getParentKeyDefaultValue();
        $node->{$parentKeyName} = ($newParentId == -1 || $newParentId === '-1') ? $rootValue : $newParentId;
        $node->save();

        if ($oldParentId !== $newParentId) {
            $this->reorderSiblings($oldParentId);
        }

        $this->reorderSiblingsWithInsert($newParentId, $nodeId, $position, $referenceId);
    }
}
