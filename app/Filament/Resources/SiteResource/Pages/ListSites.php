<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;


class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('siteUsers')
                ->label('Sites for Users')
                ->icon('heroicon-o-users')
                ->color('info')
                ->url($this->getResource()::getUrl('site-users')),

                Actions\CreateAction::make(),
        ];
    }
}
