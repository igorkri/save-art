<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Parameters\Pages\EditParameter;
use App\Filament\Resources\Parameters\RelationManagers\ValuesRelationManager;
use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ParameterValuesReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_can_be_reordered_via_drag_and_drop(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $parameter = Parameter::factory()->create();

        $first = ParameterValue::factory()->create(['parameter_id' => $parameter->id, 'sort_order' => 0]);
        $second = ParameterValue::factory()->create(['parameter_id' => $parameter->id, 'sort_order' => 1]);

        Livewire::actingAs($admin)
            ->test(ValuesRelationManager::class, [
                'ownerRecord' => $parameter,
                'pageClass' => EditParameter::class,
            ])
            ->call('reorderTable', [$second->getKey(), $first->getKey()]);

        $this->assertSame(1, $second->refresh()->sort_order);
        $this->assertSame(2, $first->refresh()->sort_order);
    }
}
