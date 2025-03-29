<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSampleOrders extends ListRecords
{
    protected static string $resource = SampleOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create sample orders')),
        ];
    }

}
