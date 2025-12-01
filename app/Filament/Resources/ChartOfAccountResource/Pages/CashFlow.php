<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Resources\Pages\Page;

class CashFlow extends Page
{
    protected static string $resource = ChartOfAccountResource::class;

    protected static string $view = 'filament.resources.chart-of-account-resource.pages.cash-flow';
}
