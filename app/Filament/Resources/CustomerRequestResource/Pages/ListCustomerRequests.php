<?php

namespace App\Filament\Resources\CustomerRequestResource\Pages;

use App\Filament\Resources\CustomerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerRequests extends ListRecords
{
    protected static string $resource = CustomerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create customer requests')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => auth()->user()->can('edit customer requests')),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => auth()->user()->can('delete customer requests')),
            Actions\Action::make('approve')
                ->label('Approve')
                ->action(fn ($record) => $this->approveRequest($record))
                ->visible(fn ($record) => auth()->user()->can('approve customer requests')),
            Actions\Action::make('reject')
                ->label('Reject')
                ->action(fn ($record) => $this->rejectRequest($record))
                ->visible(fn ($record) => auth()->user()->can('reject customer requests')),
        ];
    }

    protected function approveRequest($record)
    {
        $record->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
        ]);

        \App\Models\Customer::create([
            'name' => $record->name,
            'shop_name' => $record->shop_name,
            'address' => $record->address,
            'email' => $record->email,
            'phone_1' => $record->phone_1,
            'phone_2' => $record->phone_2,
            'remaining_balance' => $record->remaining_balance,
            'requested_by' => $record->requested_by,
            'approved_by' => auth()->user()->id,
        ]);

        $this->notify('success', 'Customer Request Approved');
    }

    protected function rejectRequest($record)
    {
        $record->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->id,
        ]);

        $this->notify('danger', 'Customer Request Rejected');
    }
}