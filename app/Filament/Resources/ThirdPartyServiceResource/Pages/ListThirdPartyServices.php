<?php

namespace App\Filament\Resources\ThirdPartyServiceResource\Pages;

use App\Filament\Resources\ThirdPartyServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListThirdPartyServices extends ListRecords
{
    protected static string $resource = ThirdPartyServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export 3rd Party Services')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('third-party-services-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('supplier_id')->heading('Supplier ID'),
                            Column::make('supplier.name')->heading('Supplier Name')->getStateUsing(
                                fn ($record) => $record->supplier?->name ?? ''
                            ),
                            Column::make('name')->heading('Service Name'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with('supplier'))
                ])
                ->modalHeading('Export 3rd Party Services')
                ->modalDescription('Export all third-party services with associated supplier names.')
                ->modalButton('Export Now')
                ->visible(fn () => auth()->user()?->can('third party services.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create third party services')),
        ];
    }
}
