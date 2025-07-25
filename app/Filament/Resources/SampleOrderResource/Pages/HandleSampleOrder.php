<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Models\SampleOrder;
use App\Models\CustomerOrder;
use App\Models\Customer;
use Filament\Notifications\Notification;
use App\Models\SampleOrderItem; 
use App\Models\SampleOrderVariation; 
use App\Models\CustomerOrderDescription; 
use App\Models\VariationItem; 

class HandleSampleOrder extends Page
{
    protected static string $resource = SampleOrderResource::class;

    protected static string $view = 'filament.resources.sample-order.handle-sample-order';

    protected static ?string $title = 'Handle Sample Order';

    public SampleOrder $record;

    public function mount(SampleOrder $record)
    {
        $this->record = $record;
    }

    // Release Sample Order
    public function releaseSampleOrder()
    {
        try {
            $this->record->update([
                'status' => 'released',
            ]);

            Notification::make()
                ->title('Sample Order Released')
                ->success()
                ->body('The sample order has been successfully released.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Releasing Sample Order')
                ->danger()
                ->body('An error occurred while releasing the sample order: ' . $e->getMessage())
                ->send();
        }
    }

    // Convert Sample Order to Customer Order (Accept Sample Order)
    public function acceptSampleOrder($confirmationMessage = null)
    {
        try {
            // Clear the rejected fields first
            $this->record->update([
                'rejected_by' => null,               
                'rejection_message' => null,         
            ]);

            // Then update the order with accepted status and confirmation message
            $this->record->update([
                'status' => 'accepted',
                'confirmation_message' => $confirmationMessage,
                'accepted_by' => auth()->id(),
            ]);

            Notification::make()
                ->title('Sample Order Accepted')
                ->success()
                ->body('The sample order has been successfully accepted. Confirmation: ' . $confirmationMessage)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Accepting Sample Order')
                ->danger()
                ->body('An error occurred while accepting the sample order: ' . $e->getMessage())
                ->send();
        }
    }

    // Reject Sample Order
    public function rejectSampleOrder($rejectionMessage = null)
    {
        try {
            // Clear the accepted fields first
            $this->record->update([
                'accepted_by' => null,               
                'confirmation_message' => null,     
            ]);

            // Then update the order with rejected status and rejection message
            $this->record->update([
                'status' => 'rejected',
                'rejected_by' => auth()->id(),  
                'rejection_message' => $rejectionMessage,
            ]);

            Notification::make()
                ->title('Sample Order Rejected')
                ->warning()
                ->body('The sample order has been rejected. Rejection message: ' . $rejectionMessage)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Rejecting Sample Order')
                ->danger()
                ->body('An error occurred while rejecting the sample order: ' . $e->getMessage())
                ->send();
        }
    }




    // Plan Order (Change status back to "planned" with confirmation)
    public function planOrder()
    {
        try {
            // Clear all the relevant fields
            $this->record->update([
                'accepted_by' => null,             
                'confirmation_message' => null,      
                'rejected_by' => null,             
                'rejection_message' => null,        
            ]);

            // Then update the order with "planned" status
            $this->record->update([
                'status' => 'planned',
            ]);

            Notification::make()
                ->title('Order Planned Again')
                ->info()
                ->body('The sample order has been set back to "planned" status. All acceptance/rejection data has been cleared.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Re-planning Sample Order')
                ->danger()
                ->body('An error occurred while re-planning the sample order: ' . $e->getMessage())
                ->send();
        }
    }


    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('Close')
                ->label('Back to Sample Orders')
                ->color('primary')
                ->icon('heroicon-o-arrow-left') 
                ->url(fn () => SampleOrderResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        // Only add "Release Sample Order" action if the status is 'planned'
        if ($this->record->status === 'planned') {
            $actions[] = Action::make('release_sample_order')
                ->label('Release Sample Order')
                ->color('info')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Release')
                ->modalDescription('Are you sure you want to release this sample order? This action cannot be undone.')
                ->modalButton('Yes, Release Order')
                ->action(fn () => $this->releaseSampleOrder())
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

        // Actions for released status
        if (in_array($this->record->status, ['released', 'cut', 'started'])) {
            $actions[] = Action::make('complete_order')
                ->label('Complete Order')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Complete Sample Order')
                ->modalDescription('Are you sure you want to complete this sample order?. You can not do anything for this order')
                ->modalButton('Complete Order')
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'completed',
                    ]);
                    
                    Notification::make()
                        ->title('Sample Order Completed')
                        ->success()
                        ->body('The sample order has been marked as completed.')
                        ->send();
                })
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

            // Pause Order action
        if (in_array($this->record->status, ['released', 'started', 'cut'])) {
            $actions[] = Action::make('pause_order')
                ->label('Pause Order')
                ->color('danger')
                ->icon('heroicon-o-pause')
                ->requiresConfirmation()
                ->modalHeading('Pause Sample Order')
                ->modalDescription('Are you sure you want to pause this sample order? All Operations will be stopped')
                ->modalButton('Pause Order')
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'paused',
                    ]);
                    
                    Notification::make()
                        ->title('Sample Order Paused')
                        ->warning()
                        ->body('The sample order has been paused.')
                        ->send();
                })
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

        if ($this->record->status === 'paused'){
            $actions[] = Action::make('resume_order')
                ->label('Resume Order')
                ->color('info')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->modalHeading('Resume Sample Order')
                ->modalDescription('Are you sure you want to Resume this sample order? All Operations will be started from paused point')
                ->modalButton('Resume Order')
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'cut',
                    ]);
                    
                    Notification::make()
                        ->title('Sample Order resumed')
                        ->warning()
                        ->body('The sample order has been resumed.')
                        ->send();
                })
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }
            
        // Accept Sample Order (Convert)
        if ($this->record->status === 'delivered') {
            $actions[] = Action::make('convert_to_customer_order')
                ->label('Accept Sample Order')
                ->color('success')
                ->icon('heroicon-o-document-text')
                ->requiresConfirmation()
                ->modalHeading('Accept Sample Order')
                ->modalDescription('Please enter a confirmation message (optional) to accept this sample order.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('confirmation_message')
                        ->label('Confirmation Message')
                        ->nullable(),
                ])
                ->modalButton('Accept Order')
                ->action(fn (array $data) => $this->acceptSampleOrder($data['confirmation_message'] ?? null))
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

        // Reject Sample Order
        if ($this->record->status === 'delivered') {
            $actions[] = Action::make('reject_sample_order')
                ->label('Reject Sample Order')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Reject Sample Order')
                ->modalDescription('Please enter a rejection message (optional) for this sample order.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_message')
                        ->label('Rejection Message')
                        ->nullable(),
                ])
                ->modalButton('Reject Order')
                ->action(fn (array $data) => $this->rejectSampleOrder($data['rejection_message'] ?? null))
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }



        // Plan Order (Change status back to "planned") for 'released', 'accepted', 'rejected' statuses
        if (in_array($this->record->status, ['released', 'start'])) {
            $actions[] = Action::make('plan_order')
                ->label('Plan Order')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Confirm Re-planning')
                ->modalDescription('Are you sure you want to change the status back to "planned"?')
                ->modalButton('Yes, Plan Again')
                ->action(fn () => $this->planOrder())
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

        // Deliver Sample Order
        if ($this->record->status === 'completed') {
            $actions[] = Action::make('deliver_order')
                ->label('Deliver Sample Order')
                ->color('success')
                ->icon('heroicon-o-truck')
                ->requiresConfirmation()
                ->modalHeading('Deliver Sample Order')
                ->modalDescription('Are you sure you want to mark this sample order as delivered? This action cannot be undone.')
                ->modalButton('Yes, Deliver')
                ->action(function () {
                    $this->record->update([
                        'status' => 'delivered',
                    ]);

                    Notification::make()
                        ->title('Sample Order Delivered')
                        ->success()
                        ->body('The sample order has been successfully marked as delivered.')
                        ->send();
                })
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }


        // Show "Print PDF" action
        if (in_array($this->record->status, ['released', 'accepted', 'rejected', 'cut', 'started', 'paused', 'completed', 'delivered'])) {
            $actions[] = Action::make('printPdf')
            ->label('Print PDF')
            ->url(fn () => route('sample-orders.pdf', ['sample_order' => $this->record->order_id]))
            ->icon('heroicon-s-printer')
            ->color('secondary')
            ->openUrlInNewTab(true);
        }

        return $actions;
    }
}
