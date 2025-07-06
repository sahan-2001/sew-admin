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
                // Update order's remaining balance
                $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);
                $order->save();

                // Update customer's balance if customer_id exists
                if ($order->customer_id) {
                    $customer = Customer::find($order->customer_id);
                    if ($customer) {
                        // Reduce customer's remaining balance by the amount paid
                        $customer->remaining_balance -= $amountPaid;
                        $customer->save();
                    }
                }
            }
        } elseif ($data['order_type'] === 'sample') {
            $order = SampleOrder::find($data['order_id']);
            if ($order) {
                // Update order's remaining balance
                $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);
                $order->save();

                // Update customer's balance if customer_id exists
                if ($order->customer_id) {
                    $customer = Customer::find($order->customer_id);
                    if ($customer) {
                        // Reduce customer's remaining balance by the amount paid
                        $customer->remaining_balance -= $amountPaid;
                        $customer->save();
                    }
                }
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