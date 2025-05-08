<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseMaterialsResource\Pages;
use App\Filament\Resources\ReleaseMaterialsResource\RelationManagers;
use App\Models\ReleaseMaterials;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Button;
use Filament\Forms\Components\Actions\Action;

class ReleaseMaterialsResource extends Resource
{
    protected static ?string $model = ReleaseMaterials::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
{
    return $form->schema([
        Select::make('order_type')
        ->label('Order Type')
        ->options([
            'customer_order' => 'Customer Order',
            'sample_order' => 'Sample Order',
        ])
        ->reactive()
        ->required()
        ->afterStateUpdated(fn ($state, Set $set) => $set('order_id', null)),

        Select::make('order_id')
            ->label('Order ID')
            ->options(function (Get $get) {
                $type = $get('order_type');
                return match ($type) {
                    'customer_order' => CustomerOrder::pluck('name', 'order_id')->toArray(),
                    'sample_order' => SampleOrder::pluck('name', 'order_id')->toArray(),
                    default => [],
                };
            })
            ->required()
            ->reactive(),

        TextInput::make('order_name')
            ->label('Order Name')
            ->required()
            ->reactive()
            ->disabled()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                $orderType = $get('order_type');
                $orderId = $get('order_id');

                if ($orderType && $orderId) {
                    $order = match ($orderType) {
                        'customer_order' => CustomerOrder::find($orderId),
                        'sample_order' => SampleOrder::find($orderId),
                        default => null,
                    };

                    $set('order_name', $order?->name ?? '');
                }
            }),

       
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
            'index' => Pages\ListReleaseMaterials::route('/'),
            'create' => Pages\CreateReleaseMaterials::route('/create'),
            'edit' => Pages\EditReleaseMaterials::route('/{record}/edit'),
        ];
    }
}
