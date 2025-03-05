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
    public function rejectSampleOrder()
    {
        try {
            $this->record->update([
                'status' => 'rejected',
            ]);

            Notification::make()
                ->title('Sample Order Rejected')
                ->warning()
                ->body('The sample order has been rejected.')
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
            $this->record->update([
                'status' => 'planned',
            ]);

            Notification::make()
                ->title('Order Planned Again')
                ->info()
                ->body('The sample order has been set back to "planned" status.')
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
                ->label('Close')
                ->color('primary')
                ->url(fn () => SampleOrderResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];

        // Only add "Release Sample Order" action if the status is not 'released'
        if ($this->record->status != 'released') {
            $actions[] = Action::make('release_sample_order')
                ->label('Release Sample Order')
                ->color('warning')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Confirm Release')
                ->modalDescription('Are you sure you want to release this sample order? This action cannot be undone.')
                ->modalButton('Yes, Release Order')
                ->action(fn () => $this->releaseSampleOrder())
                ->after(fn () => redirect(request()->header('Referer', SampleOrderResource::getUrl('index'))));
        }

        // Accept Sample Order (Convert)
        if ($this->record->status === 'released') {
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
                ->action(fn (array $data) => $this->acceptSampleOrder($data['confirmation_message'] ?? ''));
        }

        if (in_array($this->record->status, ['released', 'rejected', 'converted'])) {
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

        return $actions;
    }
}
