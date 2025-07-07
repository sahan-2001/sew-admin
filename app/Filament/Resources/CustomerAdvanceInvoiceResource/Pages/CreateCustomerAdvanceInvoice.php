<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\Customer;

class CreateCustomerAdvanceInvoice extends CreateRecord
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        
        $grandTotal = (float) ($data['grand_total'] ?? 0);
        $amountPaid = (float) ($data['amount'] ?? 0);
        
        // Store the received amount in the invoice record
        $data['amount'] = $amountPaid;
        
        // Calculate remaining balance for the invoice
        $data['remaining_balance'] = $grandTotal - $amountPaid;
        
        // Update the related order's remaining balance
        if ($data['order_type'] === 'customer') {
            $order = CustomerOrder::find($data['order_id']);
            if ($order) {
                $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);
                
                // If remaining balance is 0 or less, close the order
                if ($order) {
                    $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);

                    if ($order->remaining_balance <= 0) {
                        $order->status = 'closed';

                        // Update related discounts and expenses as closed
                        \App\Models\AdditionalOrderDiscount::where('order_type', $data['order_type'])
                            ->where('order_id', $data['order_id'])
                            ->update(['status' => 'closed']);

                        \App\Models\AdditionalOrderExpense::where('order_type', $data['order_type'])
                            ->where('order_id', $data['order_id'])
                            ->update(['status' => 'closed']);
                    }

                    $order->save();
                }
                
                $order->save();
            }
        } elseif ($data['order_type'] === 'sample') {
            $order = SampleOrder::find($data['order_id']);
            if ($order) {
                $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);
                
                // If remaining balance is 0 or less, close the order
                if ($order) {
                    $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);

                    if ($order->remaining_balance <= 0) {
                        $order->status = 'closed';

                        // Update related discounts and expenses as closed
                        \App\Models\AdditionalOrderDiscount::where('order_type', $data['order_type'])
                            ->where('order_id', $data['order_id'])
                            ->update(['status' => 'closed']);

                        \App\Models\AdditionalOrderExpense::where('order_type', $data['order_type'])
                            ->where('order_id', $data['order_id'])
                            ->update(['status' => 'closed']);
                    }

                    $order->save();
                }
                $order->save();
            }
        }

        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Customer Advance Invoice has been created successfully';
    }
}