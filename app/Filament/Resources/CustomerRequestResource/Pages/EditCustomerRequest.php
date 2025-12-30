<?php

namespace App\Filament\Resources\CustomerRequestResource\Pages;

use App\Filament\Resources\CustomerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerRequest extends EditRecord
{
    protected static string $resource = CustomerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('delete customer requests')),
            Actions\Action::make('approve')
                ->label('Approve')
                ->action('approveRequest')
                ->visible(fn () => auth()->user()->can('approve customer requests')),
            Actions\Action::make('reject')
                ->label('Reject')
                ->action('rejectRequest')
                ->visible(fn () => auth()->user()->can('reject customer requests')),
        ];
    }

    public function approveRequest()
    {
        $this->record->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
        ]);

        \App\Models\Customer::create([
            'name' => $this->record->name,
            'shop_name' => $this->record->shop_name,
            'address' => $this->record->address,
            'email' => $this->record->email,
            'phone_1' => $this->record->phone_1,
            'phone_2' => $this->record->phone_2,
            'remaining_balance' => $this->record->remaining_balance,
            'requested_by' => $this->record->requested_by,
            'approved_by' => auth()->user()->id,
        ]);

        $this->notify('success', 'Customer Request Approved');
    }

    public function rejectRequest()
    {
        $this->record->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->id,
        ]);

        $this->notify('danger', 'Customer Request Rejected');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}