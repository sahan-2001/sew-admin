<?php

namespace App\Filament\Resources\TemporaryOperationResource\Pages;

use App\Models\TemporaryOperation;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\TemporaryOperationResource;

class EditTemporaryOperation extends EditRecord
{
    protected static string $resource = TemporaryOperationResource::class;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->getRecord()->toArray();

        $data['employee_ids'] = $this->getRecord()->employees->pluck('id')->toArray();
        $data['supervisor_ids'] = $this->getRecord()->supervisors->pluck('id')->toArray();
        $data['machine_ids'] = $this->getRecord()->productionMachines->pluck('id')->toArray();
        $data['third_party_service_ids'] = $this->getRecord()->services->pluck('id')->toArray();
        $data['order_type'] = $this->getRecord()->order_type;
        $data['order_id'] = $this->getRecord()->order_id;

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        // Sync and create if not exist
        $record->employees()->syncWithoutDetaching(array_fill_keys($data['employee_ids'], ['created_by' => auth()->id(), 'updated_by' => auth()->id()]));
        $record->supervisors()->syncWithoutDetaching(array_fill_keys($data['supervisor_ids'], ['created_by' => auth()->id(), 'updated_by' => auth()->id()]));
        $record->productionMachines()->syncWithoutDetaching(array_fill_keys($data['machine_ids'], ['created_by' => auth()->id(), 'updated_by' => auth()->id()]));
        $record->services()->syncWithoutDetaching(array_fill_keys($data['third_party_service_ids'], ['created_by' => auth()->id(), 'updated_by' => auth()->id()]));

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

class ShowTemporaryOperation extends ViewRecord
{
    protected static string $resource = TemporaryOperationResource::class;
}
