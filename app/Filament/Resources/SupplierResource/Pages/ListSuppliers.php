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

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

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
                ->label('Import Suppliers')
                ->modalHeading('Upload Suppliers Excel File')
                ->modalDescription('Required fields: name, shop_name, address, email, phone_1')
                ->visible(fn () => auth()->user()?->can('suppliers.import')),

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
        $requiredHeaders = ['name', 'shop_name', 'address', 'email', 'phone_1'];

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
}
