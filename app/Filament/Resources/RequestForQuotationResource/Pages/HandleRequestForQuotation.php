<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Models\RequestForQuotation;
use Filament\Notifications\Notification;

class HandleRequestForQuotation extends Page
{
    protected static string $resource = RequestForQuotationResource::class;

    protected static string $view = 'request-for-purchase-quotations.handle-request-for-quotation';

    protected static ?string $title = 'Handle Request for Quotation';

    public RequestForQuotation $record;
    public $items = [];

    public function mount(RequestForQuotation $record)
    {
        $this->record = $record;
        $this->loadItems();
    }

    protected function loadItems()
    {
        $this->items = $this->record->items()->with('inventoryItem')->get();
    }

    public function approveRFQ()
    {
        $this->updateStatus('accepted', 'RFQ Approved', 'The request for quotation has been approved.');
    }

    public function rejectRFQ()
    {
        $this->updateStatus('rejected', 'RFQ Rejected', 'The request for quotation has been rejected.');
    }

    protected function updateStatus(string $status, string $notificationTitle, string $notificationBody)
    {
        try {
            $this->record->update(['status' => $status]);

            activity()
                ->performedOn($this->record)
                ->log("RFQ status changed to {$status}");

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
            Action::make('Close')
                ->label('Back to RFQs')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => RequestForQuotationResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        if ($this->record->status === 'sent') {
            $actions[] = Action::make('approve_rfq')
                ->label('Approve RFQ')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Approval')
                ->modalDescription('Are you sure you want to approve this RFQ?')
                ->modalButton('Yes, Approve')
                ->action(fn () => $this->approveRFQ());

            $actions[] = Action::make('reject_rfq')
                ->label('Reject RFQ')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Rejection')
                ->modalDescription('Are you sure you want to reject this RFQ?')
                ->modalButton('Yes, Reject')
                ->action(fn () => $this->rejectRFQ());
        }

        $actions[] = Action::make('printPdf')
            ->label('Print PDF')
            ->url(fn () => route('rfq.pdf', ['request_for_quotation' => $this->record->id]))
            ->icon('heroicon-s-printer')
            ->color('secondary')
            ->openUrlInNewTab(true);

        return $actions;
    }
}
