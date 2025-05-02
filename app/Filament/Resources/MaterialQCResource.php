<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialQCResource\Pages;
use App\Filament\Resources\MaterialQCResource\RelationManagers;
use App\Models\MaterialQC;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialQCResource extends Resource
{
    protected static ?string $model = MaterialQC::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('purchase_order_id')
                ->label('Purchase Order ID')
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Fetch the RegisterArrival record
                    $registerArrival = \App\Models\RegisterArrival::where('purchase_order_id', $state)->first();

                    if (!$registerArrival) {
                        $set('error_message', 'No record found for this Purchase Order ID.');
                        $set('register_arrival_details', null);
                        $set('items', []);
                        return;
                    }

                    // Set RegisterArrival details
                    $set('register_arrival_details', [
                        'id' => $registerArrival->id,
                        'purchase_order_id' => $registerArrival->purchase_order_id,
                        'arrival_date' => $registerArrival->arrival_date,
                        // Add other fields as needed
                    ]);

                    // Fetch related items
                    $items = \App\Models\RegisterArrivalItem::where('register_arrival_id', $registerArrival->id)->get();
                    $set('items', $items);
                }),

            Forms\Components\View::make('error_message_display')
                ->label('Error Message')
                ->hidden(fn ($get) => !$get('error_message')),

            Forms\Components\Card::make()
                ->label('Register Arrival Details')
                ->schema([
                    Forms\Components\TextInput::make('register_arrival_details.id')
                        ->label('ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('register_arrival_details.purchase_order_id')
                        ->label('Purchase Order ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('register_arrival_details.arrival_date')
                        ->label('Arrival Date')
                        ->disabled(),
                    // Add other fields as needed
                ])
                ->hidden(fn ($get) => empty($get('register_arrival_details'))),

            Forms\Components\Repeater::make('items')
                ->label('Items')
                ->schema([
                    Forms\Components\TextInput::make('item_name')->label('Item Name')->disabled(),
                    Forms\Components\TextInput::make('quantity')->label('Quantity')->disabled(),
                ])
                ->hidden(fn ($get) => empty($get('items'))),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialQCS::route('/'),
            'create' => Pages\CreateMaterialQC::route('/create'),
            'edit' => Pages\EditMaterialQC::route('/{record}/edit'),
        ];
    }
}
