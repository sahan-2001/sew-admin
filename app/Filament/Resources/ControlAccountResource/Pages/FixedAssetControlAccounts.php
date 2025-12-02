<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use Filament\Resources\Pages\Page;

class FixedAssetControlAccounts extends Page
{
    protected static string $resource = ControlAccountResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ControlAccountResource\Widgets\ControlAccountButtons::class,
        ];
    }
    
    protected static string $view = 'filament.resources.control-account-resource.pages.fixed-asset-control-accounts';

}
