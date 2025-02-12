<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Providers\FilamentServiceProvider as BaseServiceProvider;

class FilamentServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        parent::boot();

        Filament::serving(function () {
            Filament::navigation(function ($navigation) {
                $navigation->items([
                    NavigationItem::make('Inventory Locations')
                        ->url(route('filament.resources.inventory-locations.index'))  // Adjust the URL if needed
                        ->icon('heroicon-o-folder')
                        ->active(false),
                ]);
            });
        });
    }
}
