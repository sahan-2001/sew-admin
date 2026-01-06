<?php

namespace App\Filament\Resources\PurchaseQuotationResource\Pages;

use App\Filament\Resources\PurchaseQuotationResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;

class HandlePurchaseQuotation extends Page
{
    protected static string $resource = PurchaseQuotationResource::class;

    protected static string $view = 'purchase-quotations.handle-purchase-quotation';

    protected static ?string $title = 'Handle Purchase Quotation';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Puechase Quotations')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
