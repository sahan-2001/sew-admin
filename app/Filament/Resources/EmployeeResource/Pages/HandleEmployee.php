<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class HandleEmployee extends Page
{
    protected static string $resource = EmployeeResource::class;
    protected static string $view = 'filament.resources.employee-resource.pages.handle-employee-page';
    protected static ?string $title = 'Handle Employee';

    public Employee $record;

    // Load the Employee record from the route
    public function mount(Employee $record): void
    {
        $this->record = $record;
    }

    // Example actions
    public function activateEmployee()
    {
        $this->updateStatus(true, 'Employee Activated', 'The employee has been activated.');
    }

    public function deactivateEmployee()
    {
        $this->updateStatus(false, 'Employee Deactivated', 'The employee has been deactivated.');
    }

    protected function updateStatus(bool $isActive, string $title, string $message)
    {
        try {
            $this->record->update(['is_active' => $isActive]);

            Notification::make()
                ->title($title)
                ->success()
                ->body($message)
                ->send();
        } catch (\Exception $e) {
            $this->notifyError($title, $e);
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

    // Header actions (like buttons on top)
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Employees')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(fn () => EmployeeResource::getUrl('index')),

            Action::make('activate')
                ->label('Activate Employee')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->activateEmployee())
                ->visible(fn () => !$this->record->is_active),

            Action::make('deactivate')
                ->label('Deactivate Employee')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->deactivateEmployee())
                ->visible(fn () => $this->record->is_active),

            Action::make('offer_letter')
                ->label('Generate Offer Letter')
                ->icon('heroicon-o-document-text')
                ->color('secondary')
                ->url(fn () => route('employees.offer-letter', ['employee' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record]);
    }
}
