<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateCustomerAdvanceInvoice extends CreateRecord
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Calculate paid amount
        $paidAmount = $record->payment_type === 'fixed'
            ? $record->fix_payment_amount
            : $record->percent_calculated_payment;

        // Ensure paid amount is valid
        if (!$paidAmount || $paidAmount <= 0) {
            Log::warning('Invalid paid amount in CustomerAdvanceInvoice', [
                'record_id' => $record->id,
                'payment_type' => $record->payment_type,
                'fix_payment_amount' => $record->fix_payment_amount,
                'percent_calculated_payment' => $record->percent_calculated_payment,
            ]);
            return;
        }

        DB::transaction(function () use ($record, $paidAmount) {
            // Update customer's remaining balance
            if ($record->customer_id) {
                $customer = Customer::find($record->customer_id);
                if ($customer) {
                    $oldBalance = $customer->remaining_balance ?? 0;
                    $customer->remaining_balance = max(0, $oldBalance - $paidAmount);
                    $customer->save();
                    
                    Log::info('Customer balance updated', [
                        'customer_id' => $customer->customer_id,
                        'old_balance' => $oldBalance,
                        'new_balance' => $customer->remaining_balance,
                        'paid_amount' => $paidAmount,
                    ]);
                } else {
                    Log::error('Customer not found', ['customer_id' => $record->customer_id]);
                }
            }

            // Update order's remaining balance
            $order = null;
            if ($record->order_type === 'customer' && $record->order_id) {
                $order = CustomerOrder::where('order_id', $record->order_id)->first();
            } elseif ($record->order_type === 'sample' && $record->order_id) {
                $order = SampleOrder::where('order_id', $record->order_id)->first();
            }

            if ($order) {
                $oldOrderBalance = $order->remaining_balance ?? 0;
                $order->remaining_balance = max(0, $oldOrderBalance - $paidAmount);
                $order->save();
                
                Log::info('Order balance updated', [
                    'order_id' => $order->order_id,
                    'order_type' => $record->order_type,
                    'old_balance' => $oldOrderBalance,
                    'new_balance' => $order->remaining_balance,
                    'paid_amount' => $paidAmount,
                ]);
            } else {
                Log::warning('Order not found', [
                    'order_id' => $record->order_id,
                    'order_type' => $record->order_type,
                ]);
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}