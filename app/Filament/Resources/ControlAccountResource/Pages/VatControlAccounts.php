<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use App\Models\VATControlAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Tables\Actions\Action;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

class VATControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected static ?string $title = 'VAT Control Accounts';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('customer')
                ->label('Customer Control Account')
                ->color('success')
                ->icon('heroicon-o-user-group')
                ->url(route('filament.admin.resources.control-accounts.customer')),

            Actions\Action::make('supplier')
                ->label('Supplier Control Account')
                ->color('warning')
                ->icon('heroicon-o-truck')
                ->url(route('filament.admin.resources.control-accounts.supplier')),

            Actions\Action::make('back')
                ->label('Back to Control Accounts')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ControlAccountResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VATControlAccount::query())
            ->headerActions([
                Action::make('create_vat')
                    ->label('Create VAT Control Account')
                    ->icon('heroicon-o-plus')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('code')
                            ->label('Account Code')
                            ->required()
                            ->unique(VATControlAccount::class, 'code'),

                        Forms\Components\TextInput::make('name')
                            ->label('Account Name')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2),

                        Forms\Components\Hidden::make('debit_total_vat')->default(0),
                        Forms\Components\Hidden::make('credit_total_vat')->default(0),
                        Forms\Components\Hidden::make('balance_vat')->default(0),
                    ])
                    ->action(function (array $data, Action $action) {
                        VATControlAccount::create($data);

                        Notification::make()
                            ->title('VAT Control Account Created')
                            ->success()
                            ->send();

                        $action->close();
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit_total_vat')
                    ->label('Debit Total (VAT)')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_total_vat')
                    ->label('Credit Total (VAT)')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_vat')
                    ->label('VAT Balance')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(isToggledHiddenByDefault: true),

                ...(
                    Auth::user()->can('view audit columns')
                        ? [
                            Tables\Columns\TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            Tables\Columns\TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                            Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        ]
                        : []
                ),
            ])
            ->actions([
                // ✅ Edit Action
                Action::make('edit_vat')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->form(fn(VATControlAccount $record) => [
                        Forms\Components\TextInput::make('code')
                            ->label('Account Code')
                            ->required()
                            ->unique(VATControlAccount::class, 'code', ignoreRecord: true)
                            ->default($record->code),

                        Forms\Components\TextInput::make('name')
                            ->label('Account Name')
                            ->required()
                            ->default($record->name),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->default($record->description),
                    ])
                    ->action(function (array $data, VATControlAccount $record, Action $action) {
                        $record->update($data);

                        Notification::make()
                            ->title('VAT Control Account Updated')
                            ->success()
                            ->send();

                        $action->close();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function ($record, $action) {
                        if ($record->status === 'active') {
                            $action->cancel();

                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete active account')
                                ->body("The account '{$record->name}' is currently active and cannot be deleted.")
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No VAT Control Accounts')
            ->emptyStateDescription('There are currently no VAT control accounts available. Please add one to get started.')
            ->emptyStateIcon('heroicon-o-exclamation-circle');
    }
}
