<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlAccountResource\Pages;
use App\Models\ChartOfAccount;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;



class ControlAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;
    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Control Accounts';

    protected static ?string $modelLabel = 'Control Account';
    protected static ?string $pluralModelLabel = 'Control Accounts';


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

                Tables\Columns\TextColumn::make('debit_total')
                    ->label('Debit without VAT')
                    ->money('LKR', true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_total')
                    ->label('Credit without VAT')
                    ->money('LKR', true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance without VAT')
                    ->money('LKR', true)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('debit_total_vat')
                    ->label('Debit with VAT')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_total_vat')
                    ->label('Credit with VAT')
                    ->money('LKR', true)
                    ->sortable(),


                Tables\Columns\TextColumn::make('balance_vat')
                    ->label('Balance with VAT')
                    ->money('LKR', true)
                    ->sortable(),

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
            ->filters([])
            ->actions([
            ])
            ->bulkActions([
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
