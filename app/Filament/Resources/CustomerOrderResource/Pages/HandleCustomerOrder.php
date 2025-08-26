<?php

namespace App\Filament\Resources\CustomerOrderResource\Pages;

use App\Filament\Resources\CustomerOrderResource;
use App\Models\CustomerOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class HandleCustomerOrder extends Page
{
    protected static string $resource = CustomerOrderResource::class;
    protected static string $view = 'filament.resources.customer-order.handle-customer-order';
    protected static ?string $title = 'Handle Customer Order';

    public CustomerOrder $record;

    public function mount(CustomerOrder $record): void
    {
        $this->record = $record;
    }

    public function planOrder()
    {
        $this->updateStatus('planned', 'Order Planned', 'The customer order has been planned.');
    }

    public function releaseOrder()
    {
        $this->updateStatus('released', 'Order Released', 'The customer order has been released.');
    }

    public function completeOrder()
    {
        $this->updateStatus('completed', 'Order Completed', 'The customer order has been completed.');
    }

    public function deliverOrder()
    {
        $this->updateStatus('delivered', 'Order Delivered', 'The customer order has been delivered.');
    }

    public function cancelOrder($cancellationReason = null)
    {
        try {
            $this->record->update([
                'status' => 'cancelled',
            ]);

            Notification::make()
                ->title('Order Cancelled')
                ->warning()
                ->body('The customer order has been cancelled. Reason: ')
                ->send();
        } catch (\Exception $e) {
            $this->notifyError('Error Cancelling Order', $e);
        }
    }

    public function putOnHold($holdReason = null)
    {
        try {
            $this->record->update([
                'status' => 'paused',
            ]);

            Notification::make()
                ->title('Order Put On Hold')
                ->warning()
                ->body('The customer order has been put on hold. Reason: ')
                ->send();
        } catch (\Exception $e) {
            $this->notifyError('Error Putting On Hold', $e);
        }
    }

    public function resumeFromHold()
    {
        try {
            $this->record->update([
                'status' => 'cut', 
            ]);

            Notification::make()
                ->title('Order Resumed')
                ->success()
                ->body('The customer order has been resumed from hold.')
                ->send();
        } catch (\Exception $e) {
            $this->notifyError('Error Resuming Order', $e);
        }
    }

    protected function updateStatus(string $status, string $title, string $message)
    {
        try {
            $this->record->update(['status' => $status]);

            Notification::make()
                ->title($title)
                ->success()
                ->body($message)
                ->send();
        } catch (\Exception $e) {
            $this->notifyError("Error Updating Status to {$status}", $e);
        }
    }

    protected function notifyError(string $title, \Exception $e)
    {
        Notification::make()
            ->title($title)
            ->danger()
            ->body('An error occurred: ' . $e->getMessage())
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('back')
                ->label('Back to Customer Orders')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => CustomerOrderResource::getUrl('index')),
        ];

        if ($this->record->status === 'planned') {
            $actions[] = Action::make('release_order')
                ->label('Release Customer Order')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Release Order')
                ->modalDescription('Are you sure you want to release this order?')
                ->modalButton('Yes, Release')
                ->action(fn () => $this->releaseOrder());
        }

        if (in_array($this->record->status, ['cut', 'started', 'released'])) {
            $actions[] = Action::make('complete_order')
                ->label('Complete Order')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Complete Order')
                ->modalDescription('Are you sure you want to mark this order as completed?')
                ->modalButton('Yes, Complete')
                ->action(fn () => $this->completeOrder());
        }

        if (in_array($this->record->status, ['final_qc', 'completed'])) {
            $actions[] = Action::make('deliver_order')
                ->label('Mark as Delivered')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Deliver Order')
                ->modalDescription('Are you sure you want to mark this order as delivered?')
                ->modalButton('Yes, Deliver')
                ->action(fn () => $this->deliverOrder());
        }

        if ($this->record->status === 'pending') {
            $actions[] = Action::make('cancel_order')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Provide a reason for cancellation (optional)')
                ->modalButton('Yes, Cancel')
                ->action(fn (array $data) => $this->cancelOrder($data['cancellation_reason'] ?? null));
        }

        if (in_array($this->record->status, ['released'])) {
            $actions[] = Action::make('put_on_hold')
                ->label('Pause Order')
                ->icon('heroicon-o-pause-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Put Order On Hold')
                ->modalDescription('Provide a reason for putting this order on hold (optional)')
                ->modalButton('Yes, Hold')
                ->action(fn (array $data) => $this->putOnHold($data['hold_reason'] ?? null));
        }

        if ($this->record->status === 'paused') {
            $actions[] = Action::make('resume_from_hold')
                ->label('Resume Order')
                ->icon('heroicon-o-play')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Resume Order')
                ->modalDescription('Are you sure you want to resume this order?')
                ->modalButton('Yes, Resume')
                ->action(fn () => $this->resumeFromHold());
        }

        if ($this->record->status === 'released') {
            $actions[] = Action::make('plan_order')
                ->label('Plan Order')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Plan Order')
                ->modalDescription('Are you sure you want to plan this order?')
                ->modalButton('Yes, Plan')
                ->action(fn () => $this->planOrder());
        }

        $actions[] = Action::make('print_pdf')
            ->label('Print PDF')
            ->icon('heroicon-o-printer')
            ->color('secondary')
            ->url(fn () => route('customer-orders.pdf', ['customer_order' => $this->record->order_id]))
            ->openUrlInNewTab();

        return $actions;
    }
}
