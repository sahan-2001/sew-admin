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
            // Excel Import Action
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'address_line_1' => ['required'],
                    'city' => ['required'],
                    'zip_code' => ['required'],
                    'email' => ['required', 'email'],
                    'phone_1' => ['required'],
                ])
                ->label('Import Customers')
                ->modalHeading('Upload Customers Excel File')
                ->modalDescription('Required fields: name, address_line_1, city, zip_code, email, phone_1')
                ->visible(fn () => auth()->user()?->can('customers.import')),

            // Export Action
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
                            Column::make('address_line_1')->heading('Address Line 1'),
                            Column::make('city')->heading('City'),
                            Column::make('zip_code')->heading('Zip Code'),
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

            // Create Customer Button
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create customers')),
        ];
    }

    // Default values before validation
    protected function mutateBeforeValidation(array $data): array
    {
        if (!isset($data['requested_by']) || empty($data['requested_by'])) {
            $data['requested_by'] = auth()->id();
        }

        if (!isset($data['remaining_balance'])) {
            $data['remaining_balance'] = 0;
        }

        return $data;
    }

    // Validate Excel headers before processing collection
    protected function beforeCollection(Collection $collection): void
    {
        $requiredHeaders = ['name', 'address_line_1', 'city', 'zip_code', 'email', 'phone_1'];

        $firstRow = $collection->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded Customer Excel Headers:', $headers);
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    // Validate each row before creating a record
    protected function beforeCreateRecord(array $data, $row): void
    {
        foreach (['name', 'address_line_1', 'city', 'zip_code', 'email', 'phone_1'] as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new ValidationException(
                    ValidationException::withMessages([
                        $field => "Row {$row->getIndex()}: {$field} is required.",
                    ])
                );
            }
        }

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

    // After creating a customer, ensure a control account exists
    protected function afterCreateRecord($record, $row): void
    {
        \App\Models\CustomerControlAccount::firstOrCreate(
            ['customer_id' => $record->customer_id],
            [
                'receivable_account_id' => null,
                'sales_account_id' => null,
                'vat_output_account_id' => null,
                'bad_debt_expense_account_id' => null,
                'debit_total' => 0,
                'credit_total' => 0,
                'balance' => 0,
                'debit_total_vat' => 0,
                'credit_total_vat' => 0,
                'balance_vat' => 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );
    }
}
