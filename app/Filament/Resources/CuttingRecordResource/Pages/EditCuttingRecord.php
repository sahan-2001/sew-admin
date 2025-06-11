<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCuttingRecord extends EditRecord
{
    protected static string $resource = CuttingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('Print Report')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('cutting-records.print', ['cutting_record' => $this->record->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('Print Labels')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('cutting-records.print-labels', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load all related data
        $record = $this->record->load([
            'cuttingStation',
            'releaseMaterial',
            'employees',
            'qualityControls',
            'wasteRecords',
            'nonInventoryWaste',
            'byProductRecords',
            'cutPieceLabels',
            'orderItems.variations',
            'orderVariations'
        ]);

        // Transform the data for the form
        $data['cutting_station_name'] = $record->cuttingStation->name ?? null;
        $data['cutting_station_id'] = $record->cuttingStation->id ?? null;
        $data['release_material_id'] = $record->releaseMaterial->id ?? null;
        
        // Add other necessary transformations based on your form structure
        // For example, if you need to populate fetched_order_items or fetched_release_material_items
        // You would add that transformation logic here

        return $data;
    }

    protected function beforeSave(): void
    {
        // You can add any pre-save logic here
        // For example, calculate totals or validate relationships
    }

    protected function afterSave(): void
    {
        // You can add any post-save logic here
        // For example, update related models or trigger events
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cutting record updated')
            ->body('The cutting record has been saved successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}