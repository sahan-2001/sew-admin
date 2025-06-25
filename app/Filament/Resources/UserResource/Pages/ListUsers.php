<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException; 
use Illuminate\Database\UniqueConstraintViolationException; 
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'email' => ['required', 'email'],
                ])
                ->label('Import Users')
                ->modalHeading('Upload Users Excel File')
                ->modalDescription('Required fields: name, email, password')
                ->visible(fn () => auth()->user()?->can('users.import')),

            ExportAction::make()
                ->label('Export Users')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('users-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('name')->heading('Name'),
                            Column::make('email')->heading('Email'),
                            Column::make('created_by')->heading('Created By'),
                            Column::make('updated_by')->heading('Updated By'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export Users')
                ->modalDescription('Export all system user details.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('users.export')),
                
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create users')),
        ];
    }
}

class CustomUserImport extends EnhancedDefaultImport
{
    public string $model = User::class;

    protected function beforeCollection(Collection $collection): void
    {
        $requiredHeaders = ['name', 'email'];

        $firstRow = $collection->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded User Excel Headers:', $headers);
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    protected function beforeCreateRecord(array $data, $row): void
    {
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Invalid email format '{$data['email']}'",
                ])
            );
        }

        $existingUser = User::withTrashed()->where('email', $data['email'])->first();
        if ($existingUser) {
            $status = $existingUser->trashed() ? 'deleted' : 'active';
            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Email '{$data['email']}' already exists (Status: {$status}).",
                ])
            );
        }
    }

    protected function mutateBeforeValidation(array $data): array
    {
        // Set default password if not provided
        if (!isset($data['password']) || empty(trim($data['password']))) {
            $data['password'] = Hash::make('12345678');
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }


    protected function handleImportException(\Exception $e, $row): void
    {
        if ($e instanceof UniqueConstraintViolationException) {
            preg_match("/Duplicate entry '(.+?)' for key 'users_email_unique'/", $e->getMessage(), $matches);
            $duplicateEmail = $matches[1] ?? 'unknown email';

            throw new ValidationException(
                ValidationException::withMessages([
                    'email' => "Row {$row->getIndex()}: Email '{$duplicateEmail}' is a duplicate and already exists in the database.",
                ])
            );
        }
        

        if ($e instanceof ValidationException) {
            throw $e; 
        }

        parent::handleImportException($e, $row);
    }
}