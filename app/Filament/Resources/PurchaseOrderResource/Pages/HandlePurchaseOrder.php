<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Models\PurchaseOrder;
use Filament\Notifications\Notification;

class HandlePurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static string $view = 'filament.resources.purchase-order.handle-purchase-order';

    protected static ?string $title = 'Handle Purchase Order';

    public PurchaseOrder $record;

    public function mount(PurchaseOrder $record)
    {
        $this->record = $record;
    }

    // Release Purchase Order
    public function releasePurchaseOrder()
    {
        try {
            $this->record->update([
                'status' => 'released',
            ]);

            activity()
                ->performedOn($this->record)
                ->log('Purchase Order released');

            Notification::make()
                ->title('Purchase Order Released')
                ->success()
                ->body('The purchase order has been successfully released.')
                ->send();

            // Refresh the page
            return redirect()->route('filament.resources.purchase-orders.handle', ['record' => $this->record->id]);
        } catch (\Exception $e) {

        }
    }

    // Plan Purchase Order
    public function planOrder()
    {
        try {
            $this->record->update([
                'status' => 'planned',
            ]);

            activity()
                ->performedOn($this->record)
                ->log('Purchase Order planned again');

            Notification::make()
                ->title('Order Planned Again')
                ->info()
                ->body('The purchase order has been set back to "planned" status.')
                ->send();

            // Refresh the page
            return redirect()->route('filament.resources.purchase-orders.handle', ['record' => $this->record->id]);
        } catch (\Exception $e) {

        }
    }

    // Cancel Purchase Order
    public function cancelOrder()
    {
        try {
            $this->record->update([
                'status' => 'cancelled',
            ]);

            activity()
                ->performedOn($this->record)
                ->log('Purchase Order cancelled');

            Notification::make()
                ->title('Purchase Order Cancelled')
                ->warning()
                ->body('The purchase order has been cancelled.')
                ->send();

            // Refresh the page
            return redirect()->route('filament.resources.purchase-orders.handle', ['record' => $this->record->id]);
        } catch (\Exception $e) {

        }
    }

    // Delete Purchase Order
    public function deleteOrder()
    {
        try {
            $this->record->delete();

            activity()
                ->performedOn($this->record)
                ->log('Purchase Order deleted');

            Notification::make()
                ->title('Purchase Order Deleted')
                ->success()
                ->body('The purchase order has been deleted.')
                ->send();

            return redirect()->route('filament.resources.purchase-orders.index');
        } catch (\Exception $e) {

        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            // First row of actions
            Action::make('Close')
                ->label('Close')
                ->color('primary')
                ->url(fn () => PurchaseOrderResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];
    
        if ($this->record->status === 'arrived') {
            return $actions;
        }

        // Only add "Release Purchase Order" action if the status is 'planned'
        if ($this->record->status === 'planned') {
            $actions[] = Action::make('release_purchase_order')
                ->label('Release Purchase Order')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Release')
                ->modalDescription('Are you sure you want to release this purchase order? This action cannot be undone.')
                ->modalButton('Yes, Release Order')
                ->action(fn () => $this->releasePurchaseOrder());
        }

        // Plan Purchase Order (Change status back to "planned")
        if (in_array($this->record->status, ['released', 'cancelled'])) {
            $actions[] = Action::make('plan_order')
                ->label('Plan Order')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Confirm Reset to Planned')
                ->modalDescription('Are you sure you want to change the status back to "planned"?')
                ->modalButton('Yes, Plan Again')
                ->action(fn () => $this->planOrder());
        }

        // Cancel Purchase Order
        if ($this->record->status !== 'cancelled') {
            $actions[] = Action::make('cancel_order')
                ->label('Cancel Order')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Cancellation')
                ->modalDescription('Are you sure you want to cancel this purchase order?')
                ->modalButton('Yes, Cancel Order')
                ->action(fn () => $this->cancelOrder());
        }

        // Delete Purchase Order
        if (!in_array($this->record->status, ['cancelled', 'completed'])) {
            $actions[] = Action::make('delete_order')
                ->label('Delete Order')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Confirm Deletion')
                ->modalDescription('Are you sure you want to delete this purchase order? This action cannot be undone.')
                ->modalButton('Yes, Delete Order')
                ->action(fn () => $this->deleteOrder());
        }

        // Second row of actions
        $actions[] = Action::make('generateQrCode')
            ->label('Generate QR Code')
            ->url(fn () => route('generate.qr', ['purchase_order' => $this->record->id]))
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->openUrlInNewTab(true);

        $actions[] = Action::make('printPdf')
            ->label('Print PDF')
            ->url(fn () => route('purchase-order.pdf', ['purchase_order' => $this->record->id]))
            ->icon('heroicon-o-printer') 
            ->color('secondary')
            ->openUrlInNewTab(true);

        return $actions;
        }
}