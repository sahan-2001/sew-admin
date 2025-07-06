<?php

namespace App\Filament\Resources\AdditionalOrderDiscountResource\Pages;

use App\Filament\Resources\AdditionalOrderDiscountResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;

class CreateAdditionalOrderDiscount extends CreateRecord
{
    protected static string $resource = AdditionalOrderDiscountResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $orderType = $data['order_type'];
        $orderId = $data['order_id'];
        $amount = (float) $data['amount'];

        if ($orderType === 'customer') {
            CustomerOrder::where('order_id', $orderId)->decrement('remaining_balance', $amount);
        } elseif ($orderType === 'sample') {
            SampleOrder::where('order_id', $orderId)->decrement('remaining_balance', $amount);
        }

        return $data;
    }
}
