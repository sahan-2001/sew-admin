<?php

namespace App\Filament\Resources\AdditionalOrderExpenseResource\Pages;

use App\Filament\Resources\AdditionalOrderExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;

class CreateAdditionalOrderExpense extends CreateRecord
{
    protected static string $resource = AdditionalOrderExpenseResource::class;

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
            CustomerOrder::where('order_id', $orderId)->increment('remaining_balance', $amount);
        } elseif ($orderType === 'sample') {
            SampleOrder::where('order_id', $orderId)->increment('remaining_balance', $amount);
        }

        return $data;
    }
}
