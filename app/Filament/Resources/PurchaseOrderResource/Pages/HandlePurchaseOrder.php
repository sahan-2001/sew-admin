<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Models\PurchaseOrder;
use App\Models\SupplierAdvanceInvoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;


class HandlePurchaseOrder extends Page
{
    protected static string $resource = PurchaseOrderResource::class;
    protected static string $view = 'filament.resources.purchase-order.handle-purchase-order';
    protected static ?string $title = 'Handle Purchase Order';

    public PurchaseOrder $record;
    public $items = [];

    // ✅ Declare the supplier advance invoices property
    public Collection $supplierAdvanceInvoices;

    public function mount(PurchaseOrder $record)
    {
        $this->record = $record;
        $this->loadItems(); 
        // ✅ Load supplier advance invoices here
        $this->loadSupplierAdvanceInvoices();
    }

    protected function loadItems()
    {
        $this->items = $this->record->items()
            ->with('inventoryItem')
            ->get();
    }

    protected function loadSupplierAdvanceInvoices()
    {
        $this->supplierAdvanceInvoices = $this->record
            ->supplierAdvanceInvoices()
            ->with('supplier')
            ->latest()
            ->get();
    }



    // Release Purchase Order
    public function releasePurchaseOrder()
    {
        $this->updateStatus('released', 'Purchase Order Released', 'The purchase order has been successfully released.');
    }

    // Plan Purchase Order
    public function planOrder()
    {
        $this->updateStatus('planned', 'Order Planned Again', 'The purchase order has been set back to "planned" status.');
    }

    // Pause Purchase Order
    public function pauseOrder()
    {
        $this->updateStatus('paused', 'Purchase Order Paused', 'The purchase order has been paused.');
    }

    // Resume Purchase Order
    public function resumeOrder()
    {
        $this->updateStatus('released', 'Purchase Order Resumed', 'The purchase order has been resumed and marked as released.');
    }

    protected function updateStatus(string $status, string $notificationTitle, string $notificationBody)
    {
        try {
            $this->record->update([
                'status' => $status,
            ]);

            activity()
                ->performedOn($this->record)
                ->log("Purchase Order status changed to {$status}");

            Notification::make()
                ->title($notificationTitle)
                ->success()
                ->body($notificationBody)
                ->send();

            return redirect()->route('filament.resources.purchase-orders.handle', ['record' => $this->record->id]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Something went wrong: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            // Back to index
            Action::make('Close')
                ->label('Back to Purchase Orders')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => PurchaseOrderResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        // Status-based actions
        if ($this->record->status === 'planned') {
            $actions[] = Action::make('release_purchase_order')
                ->label('Release Purchase Order')
                ->color('info')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Release')
                ->modalDescription('Are you sure you want to release this purchase order? This action cannot be undone.')
                ->modalButton('Yes, Release Order')
                ->action(fn () => $this->releasePurchaseOrder());
        }

        if (in_array($this->record->status, ['released', 'paused'])) {
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

        if (in_array($this->record->status, ['released', 'partially arrived', 'inspected', 'invoiced'])) {
            $actions[] = Action::make('pause_order')
                ->label('Pause Order')
                ->color('danger')
                ->icon('heroicon-o-pause-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Pause')
                ->modalDescription('Are you sure you want to pause this purchase order?')
                ->modalButton('Yes, Pause Order')
                ->action(fn () => $this->pauseOrder());
        }

        if ($this->record->status === 'paused') {
            $actions[] = Action::make('resume_order')
                ->label('Resume Order')
                ->color('info')
                ->icon('heroicon-o-play-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Resume')
                ->modalDescription('Are you sure you want to resume this purchase order? It will be marked as released.')
                ->modalButton('Yes, Resume Order')
                ->action(fn () => $this->resumeOrder());
        }

        // Print PDF
        $actions[] = Action::make('printPdf')
            ->label('Print PDF')
            ->url(fn () => route('purchase-order.pdf', ['purchase_order' => $this->record->id]))
            ->icon('heroicon-s-printer')
            ->color('secondary')
            ->openUrlInNewTab(true);

        return $actions;
    }
}
