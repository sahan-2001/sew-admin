<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierRequestResource\Pages;
use App\Models\SupplierRequest;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class SupplierRequestResource extends Resource
{
    protected static ?string $model = SupplierRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';
    protected static ?string $navigationGroup = 'Supplier Management';
    protected static ?int $navigationSort = 12;

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
                Forms\Components\Textarea::make('note'),
                Forms\Components\Hidden::make('outstanding_balance')
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
                Tables\Columns\TextColumn::make('outstanding_balance')->sortable()->searchable(),
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
                    ->visible(fn (SupplierRequest $record) => auth()->user()->can('edit supplier requests')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (SupplierRequest $record) => auth()->user()->can('delete supplier requests')),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(fn (SupplierRequest $record) => static::approveRequest($record))
                    ->visible(fn (SupplierRequest $record) => auth()->user()->can('approve supplier requests') && $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->action(fn (SupplierRequest $record) => static::rejectRequest($record))
                    ->visible(fn (SupplierRequest $record) => auth()->user()->can('reject supplier requests') && $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete supplier requests')),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
    }

    protected static function approveRequest(SupplierRequest $record)
    {
        $record->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
        ]);

        Supplier::create([
            'name' => $record->name,
            'shop_name' => $record->shop_name,
            'address' => $record->address,
            'email' => $record->email,
            'phone_1' => $record->phone_1,
            'phone_2' => $record->phone_2,
            'outstanding_balance' => $record->outstanding_balance,
            'added_by' => $record->requested_by,
            'approved_by' => auth()->user()->id,
        ]);

        Notification::make()
            ->title('Supplier Request Approved')
            ->success()
            ->send();
    }

    protected static function rejectRequest(SupplierRequest $record)
    {
        $record->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->id,
        ]);

        Notification::make()
            ->title('Supplier Request Rejected')
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
            'index' => Pages\ListSupplierRequests::route('/'),
            'create' => Pages\CreateSupplierRequest::route('/create'),
            'edit' => Pages\EditSupplierRequest::route('/{record}/edit'),
        ];
    }
}