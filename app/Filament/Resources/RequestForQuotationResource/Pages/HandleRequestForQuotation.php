<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Resources\Pages\Page;
use App\Models\RequestForQuotation;
use Filament\Actions;

class HandleRequestForQuotation extends Page
{
    protected static string $resource = RequestForQuotationResource::class;
    protected static string $view = 'request-for-purchase-quotations.handle-purchase-quotation';
    protected static ?string $title = 'Handle Request for Quotation';

    public RequestForQuotation $record;

    // Add this method to load relationships
    public function mount(): void
    {
        $this->record = RequestForQuotation::with([
            'supplier',
            'supplierVatGroup',
            'user',
            'items.inventoryItem'
        ])->findOrFail(request('record'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Request for Quotations')
                ->url($this->getResource()::getUrl('index')),
            
            // Consider adding action buttons for handling the RFQ
            Actions\Action::make('approve')
                ->label('Approve RFQ')
                ->color('success')
                ->action(function() {
                    $this->record->update(['status' => 'accepted']);
                    // You might want to trigger an event or create a purchase order here
                })
                ->visible($this->record->status === 'sent'),
                
            Actions\Action::make('reject')
                ->label('Reject RFQ')
                ->color('danger')
                ->action(function() {
                    $this->record->update(['status' => 'rejected']);
                })
                ->visible($this->record->status === 'sent'),
        ];
    }
}