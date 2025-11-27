<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlAccountResource\Pages;
use App\Models\ChartOfAccount;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;

class ControlAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;
    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Control Accounts';

    /**
     * ------------------------------------------------------------
     * FORM
     * ------------------------------------------------------------
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Account Name'),

            Forms\Components\Select::make('type')
                ->label('Control Type')
                ->options([
                    'customer' => 'Customer',
                    'supplier' => 'Supplier',
                    'vat' => 'VAT',
                    'bank' => 'Bank',
                    'cash' => 'Cash',
                ])
                ->required(),

            Forms\Components\Select::make('parent_id')
                ->label('Parent Account (Optional)')
                ->relationship('parent', 'name')
                ->searchable(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
        ]);
    }

    /**
     * ------------------------------------------------------------
     * TABLE
     * ------------------------------------------------------------
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Account Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Account Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'customer',
                        'warning' => 'supplier',
                        'info' => 'vat',
                        'primary' => 'bank',
                        'secondary' => 'cash',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('Y-m-d'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * ------------------------------------------------------------
     * PAGES
     * ------------------------------------------------------------
     */
    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListControlAccounts::route('/'),
            'customer' => Pages\CustomerControlAccounts::route('/customers'),
            'supplier' => Pages\SupplierControlAccounts::route('/suppliers'),
            'vat'      => Pages\VatControlAccounts::route('/vat'),
        ];
    }
}
