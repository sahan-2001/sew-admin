<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('balance_sheet')
                ->label('Balance Sheet')
                ->url(route('filament.admin.resources.chart-of-accounts.balance-sheet'))
                ->color('success'),

            Actions\Action::make('income_statement')
                ->label('Income Statement')
                ->url(route('filament.admin.resources.chart-of-accounts.income-statement'))
                ->color('success'),

            Actions\CreateAction::make(),
        ];
    }
}
