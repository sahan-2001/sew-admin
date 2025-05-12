<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Stock;

class CreateReleaseMaterial extends CreateRecord
{
    protected static string $resource = ReleaseMaterialResource::class;

    protected function afterCreate(): void
    {
        foreach ($this->record->lines as $line) {
            $stock = Stock::where('item_id', $line->item_id)
                        ->where('location_id', $line->location_id)
                        ->first();

            if ($stock) {
                $stock->quantity -= $line->quantity;
                $stock->save();
            }
        }
    }

}
