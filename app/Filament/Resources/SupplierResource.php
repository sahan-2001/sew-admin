<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\URL;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Supplier Management';
    protected static ?int $navigationSort = 11;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view suppliers') ?? false;
    }
    
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Supplier Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('shop_name')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Contact Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('address_line_1')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line_2')
                            ->nullable()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('zip_code')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(Supplier::class, 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('phone_1')
                            ->required()
                            ->maxLength(255)
                            ->unique(Supplier::class, 'phone_1', ignoreRecord: true),
                        Forms\Components\TextInput::make('phone_2')
                            ->maxLength(255),
                    ]),
                Forms\Components\Hidden::make('outstanding_balance')
                    ->default(0),
                Forms\Components\Hidden::make('added_by')
                    ->default(fn () => auth()->user()->id),
                Forms\Components\Hidden::make('approved_by'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_id')->sortable()->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('shop_name')->sortable()->searchable(),
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
                // Filter by Supplier ID
                Filter::make('supplier_id')
                    ->label('Supplier ID')
                    ->form([
                        TextInput::make('supplier_id'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['supplier_id'],
                            fn ($query, $value) => $query->where('supplier_id', $value)
                        );
                    }),

                // Filter by Outstanding Balance
                Filter::make('outstanding_balance')
                    ->label('Outstanding Balance')
                    ->form([
                        TextInput::make('outstanding_balance'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['outstanding_balance'],
                            fn ($query, $value) => $query->where('outstanding_balance', $value)
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
                ViewAction::make()
                    ->label('View')
                    ->modalHeading('Supplier Details')
                    ->modalSubmitAction(false) // disables the "Submit" button
                    ->modalCancelActionLabel('Close')
                    ->form(fn (Supplier $record) => [
                        Forms\Components\TextInput::make('supplier_id')->label('Supplier ID')->disabled()->default($record->supplier_id),
                        Forms\Components\TextInput::make('name')->label('Name')->disabled()->default($record->name),
                        Forms\Components\TextInput::make('shop_name')->label('Shop Name')->disabled()->default($record->shop_name),
                        Forms\Components\TextInput::make('phone_1')->label('Phone 1')->disabled()->default($record->phone_1),
                        Forms\Components\TextInput::make('phone_2')->label('Phone 2')->disabled()->default($record->phone_2),
                    ]),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Supplier $record) => route('supplier.export.pdf', $record)) 
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Supplier $record) => auth()->user()->can('edit suppliers')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Supplier $record) => auth()->user()->can('delete suppliers')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete suppliers')),
            ])
        ->defaultSort('supplier_id', 'desc') 
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}