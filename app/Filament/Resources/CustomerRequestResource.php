<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerRequestResource\Pages;
use App\Models\CustomerRequest;
use App\Models\Customer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class CustomerRequestResource extends Resource
{
    protected static ?string $model = CustomerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?int $navigationSort = 10;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('shop_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_1')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_2')
                    ->maxLength(255),
                Forms\Components\Hidden::make('remaining_balance')
                    ->default(0),
                Forms\Components\Hidden::make('requested_by')
                    ->default(fn () => auth()->user()->id),
                Forms\Components\Hidden::make('approved_by'),
                Forms\Components\Hidden::make('status')
                    ->default('pending'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('shop_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('address')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('phone_1')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('phone_2')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('remaining_balance')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('requestedBy.email')->label('Requested By Email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('approvedBy.name')->label('Approved By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable()->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                // Define your filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (CustomerRequest $record) => auth()->user()->can('edit customer requests')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (CustomerRequest $record) => auth()->user()->can('delete customer requests')),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(fn (CustomerRequest $record) => static::approveRequest($record))
                    ->visible(fn (CustomerRequest $record) => auth()->user()->can('approve customer requests') && $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->action(fn (CustomerRequest $record) => static::rejectRequest($record))
                    ->visible(fn (CustomerRequest $record) => auth()->user()->can('reject customer requests') && $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete customer requests')),
            ])
            ->recordUrl(null);
    }

    protected static function approveRequest(CustomerRequest $record)
    {
        $record->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
        ]);

        Customer::create([
            'name' => $record->name,
            'shop_name' => $record->shop_name,
            'address' => $record->address,
            'email' => $record->email,
            'phone_1' => $record->phone_1,
            'phone_2' => $record->phone_2,
            'remaining_balance' => $record->remaining_balance,
            'requested_by' => $record->requested_by,
            'approved_by' => auth()->user()->id,
        ]);

        Notification::make()
            ->title('Customer Request Approved')
            ->success()
            ->send();
    }

    protected static function rejectRequest(CustomerRequest $record)
    {
        $record->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->id,
        ]);

        Notification::make()
            ->title('Customer Request Rejected')
            ->danger()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            // Define any related models or relations
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerRequests::route('/'),
            'create' => Pages\CreateCustomerRequest::route('/create'),
            'edit' => Pages\EditCustomerRequest::route('/{record}/edit'),
        ];
    }
}