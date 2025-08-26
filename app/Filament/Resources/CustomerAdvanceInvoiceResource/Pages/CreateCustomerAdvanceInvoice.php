<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Mail\CustomerAdvanceInvoiceCreatedMail;

class CreateCustomerAdvanceInvoice extends CreateRecord
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $grandTotal = (float) ($data['grand_total'] ?? 0);
        $amountPaid = (float) ($data['amount'] ?? 0);
        $data['amount'] = $amountPaid;
        $data['remaining_balance'] = $grandTotal - $amountPaid;

        $shouldClose = false;

        if ($data['order_type'] === 'customer') {
            $order = CustomerOrder::find($data['order_id']);
        } elseif ($data['order_type'] === 'sample') {
            $order = SampleOrder::find($data['order_id']);
        } else {
            $order = null;
        }

        if ($order) {
            if ((float) $order->remaining_balance === $grandTotal) {
                $order->remaining_balance = max(0, $order->remaining_balance - $amountPaid);

                if ($order->remaining_balance <= 0) {
                    $order->status = 'closed';
                    $shouldClose = true;
                }

                $order->save();
            }

            if ($shouldClose) {
                \App\Models\AdditionalOrderDiscount::where('order_type', $data['order_type'])
                    ->where('order_id', $data['order_id'])
                    ->update(['status' => 'closed']);

                \App\Models\AdditionalOrderExpense::where('order_type', $data['order_type'])
                    ->where('order_id', $data['order_id'])
                    ->update(['status' => 'closed']);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing(['customer']);

        if ($this->record->customer && $this->record->customer->email) {
            try {
                Mail::to($this->record->customer->email)
                    ->send(new CustomerAdvanceInvoiceCreatedMail($this->record));

                // Show Filament notification
                Notification::make()
                    ->success()
                    ->title('Email sent successfully')
                    ->body("Customer advance invoice email sent to {$this->record->customer->email}")
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->danger()
                    ->title('Email sending failed')
                    ->body("Failed to send email: {$e->getMessage()}")
                    ->send();
            }
        }
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
