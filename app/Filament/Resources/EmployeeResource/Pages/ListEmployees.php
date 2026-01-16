<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('epf_etf')
                ->label('EPF / ETF Group Settings')
                ->color('info')
                ->icon('heroicon-o-banknotes')
                ->url(fn () => EmployeeResource::getUrl('epf-etf')),
            
            Actions\CreateAction::make(),
        ];
    }
}
