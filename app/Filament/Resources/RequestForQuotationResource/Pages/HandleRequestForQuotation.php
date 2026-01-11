<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use App\Models\RequestForQuotation;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestForQuotationMail;

class HandleRequestForQuotation extends Page
{
    protected static string $resource = RequestForQuotationResource::class;

    protected static string $view = 'filament.resources.request-for-quotation-resource.pages.handle-request-for-quotation';

    protected static ?string $title = 'Handle Request for Quotation';

    public RequestForQuotation $record;
    public $items = [];

    public function mount(RequestForQuotation $record)
    {
        $this->record = $record->load('supplier', 'user', 'items.inventoryItem', 'paymentTerm', 'deliveryTerm', 'deliveryMethod', 'currency'); 
        $this->loadItems();
    }

    protected function loadItems()
    {
        $this->items = $this->record->items()->with('inventoryItem')->get();
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

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Something went wrong: ' . $e->getMessage())
                ->send();
        }
    }

    // Send RFQ to Suppliers
    public function sendRFQ()
    {
        try {
            // Ensure supplier has email
            $supplierEmail = $this->record->supplier?->email;

            if (!$supplierEmail) {
                Notification::make()
                    ->title('Error')
                    ->danger()
                    ->body('Supplier email not found. Cannot send RFQ.')
                    ->send();
                return;
            }

            // Send RFQ email
            Mail::to($supplierEmail)->send(new RequestForQuotationMail($this->record));

            // Update status to 'sent'
            $this->updateStatus(
                'sent',
                'RFQ Sent',
                'The Request for Quotation has been sent to the supplier successfully.'
            );

        } catch (\Exception $e) {
            Notification::make()
                ->title('Email Failed')
                ->danger()
                ->body('Failed to send RFQ email: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            // Back to index
            Action::make('close')
                ->label('Back to RFQs')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => RequestForQuotationResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        switch ($this->record->status) {

            case 'draft':
                // Approve RFQ
                $actions[] = Action::make('approve_rfq')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Approval')
                    ->modalDescription('Approve this RFQ?')
                    ->modalButton('Yes, Approve')
                    ->action(fn () => $this->updateStatus('approved', 'RFQ Approved', 'The RFQ has been approved.'))
                    ->visible(auth()->user()->can('Approve Request For Quotation'));

                // Cancel RFQ
                $actions[] = Action::make('cancel_rfq')
                    ->label('Cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Cancel')
                    ->modalDescription('Cancel this RFQ?')
                    ->modalButton('Yes, Cancel')
                    ->action(fn () => $this->updateStatus('cancelled', 'RFQ Cancelled', 'The RFQ has been cancelled.'))
                    ->visible(auth()->user()->can('Cancel Request For Quotations'));
                break;

            case 'approved':
            case 'sent': 
                // Send RFQ
                $actions[] = Action::make('send_rfq')
                    ->label('Send RFQ')
                    ->color('success')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Send RFQ')
                    ->modalDescription('Send this RFQ to suppliers?')
                    ->modalButton('Yes, Send')
                    ->action(fn () => $this->sendRFQ())
                    ->visible(auth()->user()->can('Send Request For Quotations'));

                if ($this->record->status === 'approved') {
                    // Convert back to draft (only for approved)
                    $actions[] = Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->color('secondary')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Convert')
                        ->modalDescription('Convert RFQ back to draft?')
                        ->modalButton('Yes, Convert')
                        ->action(fn () => $this->updateStatus('draft', 'RFQ Draft', 'The RFQ has been reverted to draft.'))
                        ->visible(auth()->user()->can('Convert Request For Quotations to Draft'));
                }
                break;

            case 'cancelled':
                // Reopen RFQ (back to draft)
                $actions[] = Action::make('reopen_rfq')
                    ->label('Reopen')
                    ->color('warning')
                    ->icon('heroicon-o-lock-open')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Reopen')
                    ->modalDescription('Reopen this cancelled RFQ and revert to draft?')
                    ->modalButton('Yes, Reopen')
                    ->action(fn () => $this->updateStatus('draft', 'RFQ Reopened', 'The RFQ has been reopened and set to draft.'))
                    ->visible(auth()->user()->can('Reopen Request For Quotations'));
                break;
        }

        // Always allow Print RFQ
        $actions[] = Action::make('print_rfq')
            ->label('Print RFQ')
            ->icon('heroicon-s-printer')
            ->color('secondary')
            ->url(fn () => route('request-for-quotation.print', ['rfq' => $this->record->id]))
            ->openUrlInNewTab(true);

        return $actions;
    }

}
