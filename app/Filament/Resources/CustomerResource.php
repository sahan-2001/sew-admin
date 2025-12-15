<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;
use Filament\Tables\Actions\Action;


class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?int $navigationSort = 9;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view customers') ?? false;
    }
    
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Customer Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('shop_name')
                            ->maxLength(255),
                    ]),
                Section::make('Contact Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('address_line_1')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line_2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')
                            ->numeric()
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
                    ]),
                Forms\Components\Hidden::make('remaining_balance')
                    ->default(0),
                Forms\Components\Hidden::make('requested_by')
                    ->default(fn () => auth()->user()->id),
                Forms\Components\Hidden::make('approved_by'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_id')
                    ->label('Customer ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('shop_name')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('phone_1')->sortable()->searchable(),
                
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                // Filter by Customer ID
                Filter::make('customer_id')
                    ->label('Customer ID')
                    ->form([
                        TextInput::make('customer_id'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['customer_id'],
                            fn ($query, $value) => $query->where('customer_id', $value)
                        );
                    }),

                // Filter by Remaining Balance
                Filter::make('remaining_balance')
                    ->label('Remaining Balance')
                    ->form([
                        TextInput::make('remaining_balance'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['remaining_balance'],
                            fn ($query, $value) => $query->where('remaining_balance', $value)
                        );
                    }),
                
                // Filter by Created Date (single date only)
                Filter::make('created_at')
                    ->label('Created Date')
                    ->form([
                        DatePicker::make('created_date')
                            ->label('Created Date')
                            ->maxDate(Carbon::today()),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['created_date'],
                            fn ($query, $date) => $query->whereDate('created_at', $date)
                        );
                    }),
            ])
            ->actions([
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('export.customer.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Customer $record) => auth()->user()->can('edit customers')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Customer $record) => auth()->user()->can('delete customers')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete customers')),
            ])
        ->defaultSort('customer_id', 'desc') 
        ->recordUrl(null);
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}