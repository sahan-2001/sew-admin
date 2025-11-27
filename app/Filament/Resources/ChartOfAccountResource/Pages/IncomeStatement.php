<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Resources\Pages\Page;

class IncomeStatement extends Page
{
    protected static string $resource = ChartOfAccountResource::class;

    protected static string $view = 'filament.resources.chart-of-account-resource.pages.income-statement';
}
