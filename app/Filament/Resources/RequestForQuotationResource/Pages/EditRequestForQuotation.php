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
            Action::make('handle')
                    ->label('Handle RFQ')
                    ->icon('heroicon-o-cog')
                    ->color('primary')
                    ->url(fn (RequestForQuotation $record) =>
                        RequestForQuotationResource::getUrl('handle', ['record' => $record])
                    ),
        ];
    }
}
