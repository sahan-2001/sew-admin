<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;
use EightyNine\ExcelImport\ExcelImportAction;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'shop_name' => ['required'],
                    'address' => ['required'],
                    'email' => ['required', 'email'],
                    'phone_1' => ['required'],
                ])
                ->label('Import Customers')
                ->modalHeading('Upload Customers Excel File')
                ->modalDescription('Required fields: name, shop_name, address, email, phone_1')
                ->visible(fn () => auth()->user()?->can('customers.import')),
                
            ExportAction::make()
                ->label('Export Customer List')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('customers-export-' . date('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('customer_id')->heading('ID'),
                            Column::make('name')->heading('Customer Name'),
                            Column::make('shop_name')->heading('Shop Name'),
                            Column::make('address')->heading('Address'),
                            Column::make('email')->heading('Email'),
                            Column::make('phone_1')->heading('Primary Phone'),
                            Column::make('phone_2')->heading('Secondary Phone'),
                            Column::make('remaining_balance')
                                ->heading('Balance')
                                ->getStateUsing(fn ($record) => number_format($record->remaining_balance, 2)),
                            Column::make('requested_by')
                                ->heading('Requested By')
                                ->getStateUsing(fn ($record) => $record->requestedBy?->name),
                            Column::make('approved_by')
                                ->heading('Approved By')
                                ->getStateUsing(fn ($record) => $record->approvedBy?->name),
                            Column::make('created_at')
                                ->heading('Created Date')
                                ->getStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                            Column::make('updated_at')
                                ->heading('Updated Date')
                                ->getStateUsing(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['requestedBy', 'approvedBy']))
                ])
                ->modalHeading('Export Customers')
                ->modalDescription('Select the format and columns to export')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('customers.export')),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create customers')),
        ];
    }

    protected function mutateBeforeValidation(array $data): array
    {
        // Set requested_by to currently authenticated user ID if not provided
        if (!isset($data['requested_by']) || empty($data['requested_by'])) {
            $data['requested_by'] = auth()->id();
        }

        // Set default remaining_balance if missing
        if (!isset($data['remaining_balance'])) {
            $data['remaining_balance'] = 0;
        }

        return $data;
    }


    protected function beforeCollection(Collection $collection): void
    {
        $requiredHeaders = ['name', 'shop_name', 'address', 'email', 'phone_1'];

        $firstRow = $collection->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded Customer Excel Headers:', $headers);
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    protected function beforeCreateRecord(array $data, $row): void
    {
        // Check required fields are present and non-empty
        foreach (['name', 'shop_name', 'address', 'email', 'phone_1'] as $field) {
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

        $existingCustomer = Customer::withTrashed()->where('email', $data['email'])->first();
        if ($existingCustomer) {
            $status = $existingCustomer->trashed() ? 'deleted' : 'active';
            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Email '{$data['email']}' already exists (Status: {$status}).",
                ])
            );
        }
    }
}