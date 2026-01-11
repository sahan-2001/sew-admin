<?php

namespace App\Filament\Resources\PurchaseQuotationResource\Pages;

use App\Filament\Resources\PurchaseQuotationResource;
use App\Models\PurchaseQuotation;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class HandlePurchaseQuotation extends Page
{
    protected static string $resource = PurchaseQuotationResource::class;

    protected static string $view = 'filament.resources.purchase-quotation-resource.pages.handle-purchase-quotation';

    protected static ?string $title = 'Handle Purchase Quotation';

    public PurchaseQuotation $record;
    public $items = [];

    public function mount(PurchaseQuotation $record)
    {
        $this->record = $record->load([
            'supplier',
            'rfq',
            'items.inventoryItem',
            'paymentTerm',
            'deliveryTerm',
            'deliveryMethod',
            'currencyCode',
        ]);

        $this->loadItems();

        // Load supplier quotation details
        $this->supplier_quotation_number = $record->supplier_quotation_number;
        $this->received_date             = $record->received_date;
        $this->estimated_delivery_date   = $record->estimated_delivery_date;
        $this->supplier_note             = $record->supplier_note;
        $this->image_of_quotation        = $record->image_of_quotation;
    }

    protected function loadItems()
    {
        $this->items = $this->record->items()->with('inventoryItem')->get();
    }

    public function approvePQ()
    {
        // Check if any other PQ for the same RFQ is already approved
        $existingApprovedPQ = PurchaseQuotation::where('request_for_quotation_id', $this->record->request_for_quotation_id)
            ->where('status', 'approved')
            ->where('id', '!=', $this->record->id) 
            ->first();

        if ($existingApprovedPQ) {
            Notification::make()
                ->title('Cannot Approve')
                ->danger()
                ->body('Another Purchase Quotation for this Request For Quotation is already approved.')
                ->send();

            return; 
        }

        // If none approved, proceed
        $this->updateStatus('approved', 'PQ Approved', 'The Purchase Quotation has been approved.');
    }


    public function rejectPQ()
    {
        $this->updateStatus('rejected', 'PQ Rejected', 'The Purchase Quotation has been rejected.');
    }

    public function convertToPO()
    {
        // Logic to create Purchase Order
        Notification::make()
            ->title('PO Created')
            ->success()
            ->body('Purchase Order has been created from this PQ.')
            ->send();
    }

    public function backToDraft()
    {
        $this->updateStatus('draft', 'PQ Reverted', 'The Purchase Quotation has been reverted to draft.');
    }

    public function createRejectionNote()
    {
        // Logic to create Rejection Note
        Notification::make()
            ->title('Rejection Note Created')
            ->success()
            ->body('A rejection note has been created for this PQ.')
            ->send();
    }

    protected function updateStatus(string $status, string $notificationTitle = null, string $notificationBody = null)
    {
        try {
            // Update PQ status
            $this->record->update(['status' => $status]);
            $this->record->refresh();

            // RFQ status handling
            if (in_array($status, ['approved', 'rejected'])) {
                $this->record->rfq?->update([
                    'status' => 'closed',
                ]);

                activity()
                    ->performedOn($this->record->rfq)
                    ->log("RFQ #{$this->record->rfq->id} closed because PQ #{$this->record->id} was {$status}");
            }

            if ($status === 'draft') {
                $this->record->rfq?->update([
                    'status' => 'quoted',
                ]);

                activity()
                    ->performedOn($this->record->rfq)
                    ->log("RFQ #{$this->record->rfq->id} reopened because PQ #{$this->record->id} was reverted to draft");
            }

            // Log PQ activity
            activity()
                ->performedOn($this->record)
                ->log("Purchase Quotation status changed to {$status}");

            // Notification
            if ($notificationTitle && $notificationBody) {
                Notification::make()
                    ->title($notificationTitle)
                    ->success()
                    ->body($notificationBody)
                    ->send();
            }

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
            Action::make('back')
                ->label('Back to PQs')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => PurchaseQuotationResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        switch ($this->record->status) {
            case 'draft':
                $actions[] = Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Approval')
                    ->modalDescription('Approve this Purchase Quotation?')
                    ->modalButton('Yes, Approve')
                    ->action(fn () => $this->approvePQ())
                    ->visible(auth()->user()->can('Approve Purchase Quotation'));

                $actions[] = Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Rejection')
                    ->modalDescription('Reject this Purchase Quotation?')
                    ->modalButton('Yes, Reject')
                    ->action(fn () => $this->rejectPQ())
                    ->visible(auth()->user()->can('Reject Purchase Quotation'));
                break;

            case 'approved':
                $actions[] = Action::make('convert_to_po')
                    ->label('Convert to PO')
                    ->color('success')
                    ->icon('heroicon-o-shopping-cart')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Conversion')
                    ->modalDescription('Convert this PQ into a Purchase Order?')
                    ->modalButton('Yes, Convert')
                    ->action(fn () => $this->convertToPO())
                    ->visible(auth()->user()->can('Convert to Purchase Order from Purchase Quotation'));

                $actions[] = Action::make('back_to_draft')
                    ->label('Back to Draft')
                    ->color('secondary')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Revert')
                    ->modalDescription('Revert this approved PQ back to draft?')
                    ->modalButton('Yes, Revert')
                    ->action(fn () => $this->backToDraft())
                    ->visible(auth()->user()->can('Convert Back to Draft Purchase Quotation'));
                break;

            case 'rejected':
                $actions[] = Action::make('create_rejection_note')
                    ->label('Create Rejection Note')
                    ->color('warning')
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->modalHeading('Create Rejection Note')
                    ->modalDescription('Create a rejection note for this PQ?')
                    ->modalButton('Yes, Create')
                    ->action(fn () => $this->createRejectionNote())
                    ->visible(auth()->user()->can('Create Rejection Note for Purchase Quotation'));

                $actions[] = Action::make('back_to_draft')
                    ->label('Back to Draft')
                    ->color('secondary')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Revert')
                    ->modalDescription('Revert this rejected PQ back to draft?')
                    ->modalButton('Yes, Revert')
                    ->action(fn () => $this->backToDraft())
                    ->visible(auth()->user()->can('Convert Back to Draft Purchase Quotation'));
                break;
        }

        // Always-visible Print PDF
        $actions[] = Action::make('print_pdf')
            ->label('Print PDF')
            ->icon('heroicon-o-printer')
            ->color('primary')
            ->url(fn () => route('purchase-quotation.pdf', ['purchase_quotation' => $this->record->id]))
            ->openUrlInNewTab();

        return $actions;
    }
}
