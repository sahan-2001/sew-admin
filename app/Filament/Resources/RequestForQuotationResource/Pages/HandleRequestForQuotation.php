<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use App\Models\RequestForQuotation;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class HandleRequestForQuotation extends Page
{
    protected static string $resource = RequestForQuotationResource::class;

    protected static string $view = 'filament.resources.request-for-quotation-resource.pages.handle-request-for-quotation';

    protected static ?string $title = 'Handle Request for Quotation';

    public RequestForQuotation $record;
    public $items = [];

    public function mount(RequestForQuotation $record)
    {
        $this->record = $record->load('supplier', 'user', 'items.inventoryItem'); 
        $this->loadItems();
    }

    protected function loadItems()
    {
        $this->items = $this->record->items()->with('inventoryItem')->get();
    }

    // Send RFQ to Suppliers
    public function sendRFQ()
    {
        $this->updateStatus('sent', 'RFQ Sent', 'The Request for Quotation has been sent to suppliers.');
    }

    // Close RFQ
    public function closeRFQ()
    {
        $this->updateStatus('closed', 'RFQ Closed', 'The Request for Quotation has been closed.');
    }

    // Reopen RFQ
    public function reopenRFQ()
    {
        $this->updateStatus('sent', 'RFQ Reopened', 'The Request for Quotation has been reopened.');
    }

    // Convert to Draft
    public function convertToDraft()
    {
        $this->updateStatus('draft', 'RFQ Converted to Draft', 'The Request for Quotation has been converted back to draft status.');
    }

    // Cancel RFQ
    public function cancelRFQ()
    {
        $this->updateStatus('cancelled', 'RFQ Cancelled', 'The Request for Quotation has been cancelled.');
    }

    protected function updateStatus(string $status, string $notificationTitle, string $notificationBody)
    {
        try {
            $this->record->update([
                'status' => $status,
            ]);

            activity()
                ->performedOn($this->record)
                ->log("Request for Quotation status changed to {$status}");

            Notification::make()
                ->title($notificationTitle)
                ->success()
                ->body($notificationBody)
                ->send();

            return redirect()->route('filament.resources.request-for-quotations.handle', ['record' => $this->record->id]);
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
                ->label('Back to RFQs')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => RequestForQuotationResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        // Status-based actions
        if ($this->record->status === 'draft') {
            $actions[] = Action::make('send_rfq')
                ->label('Send RFQ')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Confirm Send RFQ')
                ->modalDescription('Are you sure you want to send this Request for Quotation to suppliers?')
                ->modalButton('Yes, Send RFQ')
                ->action(fn () => $this->sendRFQ());
        }

        if (in_array($this->record->status, ['sent', 'under_review'])) {
            $actions[] = Action::make('close_rfq')
                ->label('Close RFQ')
                ->color('gray')
                ->icon('heroicon-o-lock-closed')
                ->requiresConfirmation()
                ->modalHeading('Confirm Close RFQ')
                ->modalDescription('Are you sure you want to close this Request for Quotation?')
                ->modalButton('Yes, Close RFQ')
                ->action(fn () => $this->closeRFQ());
        }

        if ($this->record->status === 'closed') {
            $actions[] = Action::make('reopen_rfq')
                ->label('Reopen RFQ')
                ->color('warning')
                ->icon('heroicon-o-lock-open')
                ->requiresConfirmation()
                ->modalHeading('Confirm Reopen RFQ')
                ->modalDescription('Are you sure you want to reopen this Request for Quotation?')
                ->modalButton('Yes, Reopen RFQ')
                ->action(fn () => $this->reopenRFQ());
        }

        if (in_array($this->record->status, ['sent', 'under_review'])) {
            $actions[] = Action::make('convert_to_draft')
                ->label('Convert to Draft')
                ->color('secondary')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Confirm Convert to Draft')
                ->modalDescription('Are you sure you want to convert this RFQ back to draft status?')
                ->modalButton('Yes, Convert to Draft')
                ->action(fn () => $this->convertToDraft());
        }

        if (!in_array($this->record->status, ['closed', 'cancelled'])) {
            $actions[] = Action::make('cancel_rfq')
                ->label('Cancel RFQ')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Cancel RFQ')
                ->modalDescription('Are you sure you want to cancel this Request for Quotation? This action cannot be undone.')
                ->modalButton('Yes, Cancel RFQ')
                ->action(fn () => $this->cancelRFQ());
        }

        // Print PDF
        $actions[] = Action::make('print_rfq')
            ->label('Print RFQ')
            ->icon('heroicon-s-printer')
            ->color('secondary')
            ->url(fn () => route('request-for-quotation.print', ['rfq' => $this->record->id]))
            ->openUrlInNewTab(true);

        // Generate Purchase Order (if applicable)
        if ($this->record->status === 'closed') {
            $actions[] = Action::make('generate_po')
                ->label('Generate PO')
                ->color('primary')
                ->icon('heroicon-o-document-plus')
                ->url(fn () => route('purchase-order.create-from-rfq', ['rfq' => $this->record->id]))
                ->openUrlInNewTab(false);
        }

        return $actions;
    }
}
