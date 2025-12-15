<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'address_line_1' => ['required'],
                    'email' => ['required', 'email'],
                    'phone_1' => ['required'],
                ])
                ->label('Import Suppliers')
                ->modalHeading('Upload Suppliers Excel File')
                ->modalDescription('Required fields: name, shop_name, address, email, phone_1')
                ->visible(fn () => auth()->user()?->can('suppliers.import')),

            ExportAction::make()
                ->label('Export Suppliers')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('suppliers-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('supplier_id')->heading('Supplier ID'),
                            Column::make('name')->heading('Name'),
                            Column::make('shop_name')->heading('Shop Name'),
                            Column::make('address_line_1')->heading('Address Line 1'),
                            Column::make('email')->heading('Email'),
                            Column::make('phone_1')->heading('Phone 1'),
                            Column::make('phone_2')->heading('Phone 2'),
                            Column::make('outstanding_balance')->heading('Outstanding Balance')->getStateUsing(
                                fn ($record) => number_format($record->outstanding_balance ?? 0, 2)
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with('approvedBy'))
                ])
                ->modalHeading('Export Suppliers')
                ->modalDescription('Export supplier records including approval details.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('suppliers.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create suppliers')),
        ];
    }

    protected function mutateBeforeValidation(array $data): array
    {
        // Set created_by to authenticated user if missing
        if (!isset($data['created_by']) || empty($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }

        // Set default outstanding_balance if missing
        if (!isset($data['outstanding_balance'])) {
            $data['outstanding_balance'] = 0;
        }

        return $data;
    }

    protected function beforeCollection(Collection $collection): void
    {
        $requiredHeaders = ['name', 'address_line_1', 'email', 'phone_1'];

        $firstRow = $collection->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded Supplier Excel Headers:', $headers);
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    protected function beforeCreateRecord(array $data, $row): void
    {
        // Check required fields are present and non-empty
        foreach (['name', 'address_line_1', 'city', 'zip_code', 'email', 'phone_1'] as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new ValidationException(
                    ValidationException::withMessages([
                        $field => "Row {$row->getIndex()}: {$field} is required.",
                    ])
                );
            }
        }

        // Email format validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Invalid email format '{$data['email']}'",
                ])
            );
        }

        $existingSupplier = Supplier::withTrashed()->where('email', $data['email'])->first();
        if ($existingSupplier) {
            $status = $existingSupplier->trashed() ? 'deleted' : 'active';
            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Email '{$data['email']}' already exists (Status: {$status}).",
                ])
            );
        }
    }


    protected function afterCreateRecord($record, $row): void
    {
        // Ensure SupplierControlAccount exists for the imported supplier
        \App\Models\SupplierControlAccount::firstOrCreate(
            ['supplier_id' => $record->supplier_id],
            [
                'chart_of_account_id' => 1,
                'payable_account_id' => null,
                'purchase_account_id' => null,
                'vat_input_account_id' => null,
                'purchase_discount_account_id' => null,
                'bad_debt_recovery_account_id' => null,
                'debit_total' => 0,
                'credit_total' => 0,
                'balance' => 0,
                'debit_total_vat' => 0,
                'credit_total_vat' => 0,
                'balance_vat' => 0,
                'created_by' => auth()->id() ?? 1, // fallback to admin ID if auth not available
                'updated_by' => auth()->id() ?? 1,
            ]
        );
    }

}
