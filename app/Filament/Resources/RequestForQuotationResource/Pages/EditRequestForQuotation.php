<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestForQuotation extends EditRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('handle')
                ->label('Handle Request for Quotation')
                ->url($this->getResource()::getUrl('handle', ['record' => $this->record])),
        ];
    }
}
