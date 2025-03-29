<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

class CreateRegisterArrival extends CreateRecord
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function afterCreate(): void
    {
        $registerArrival = $this->record;

        // Update PurchaseOrder and PurchaseOrderItem quantities
        RegisterArrivalResource::updatePurchaseOrderStatusAndItems($registerArrival);
    }
}