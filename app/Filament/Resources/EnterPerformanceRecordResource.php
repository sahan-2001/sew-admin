<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnterPerformanceRecordResource\Pages;
use App\Filament\Resources\EnterPerformanceRecordResource\RelationManagers;
use App\Models\EnterPerformanceRecord;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\AssignDailyOperation;
use App\Models\AssignDailyOperationLine;
use App\Models\TemporaryOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput, Modal};
use Filament\Forms\Components\Actions\Action;

class EnterPerformanceRecordResource extends Resource
{
    protected static ?string $model = EnterPerformanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Enter Daily Operation Performance';
    protected static ?string $navigationGroup = 'Daily Production';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('order_type')
                                    ->label('Order Type')
                                    ->options([
                                        'customer_order' => 'Customer Order',
                                        'sample_order' => 'Sample Order',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn ($get, $record) => $record !== null)
                                    ->dehydrated()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('order_id', null);
                                        $set('customer_id', null);
                                        $set('wanted_date', null);
                                    }),

                                Select::make('order_id')
                                    ->label('Order')
                                    ->required()
                                    ->options(function ($get) {
                                        if ($get('order_type') === 'customer_order') {
                                            return CustomerOrder::pluck('name', 'order_id');
                                        } elseif ($get('order_type') === 'sample_order') {
                                            return SampleOrder::pluck('name', 'order_id');
                                        }
                                        return [];
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $set('customer_id', null);
                                        $set('wanted_date', null);

                                        $orderType = $get('order_type');
                                        if ($orderType === 'customer_order') {
                                            $order = CustomerOrder::find($state);
                                        } elseif ($orderType === 'sample_order') {
                                            $order = SampleOrder::find($state);
                                        }

                                        if ($order) {
                                            $set('customer_id', $order->customer_id ?? 'N/A');
                                            $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                        } else {
                                            $set('customer_id', 'N/A');
                                            $set('wanted_date', 'N/A');
                                        }
                                    })
                                    ->disabled(fn ($get, $record) => $record !== null)
                                    ->dehydrated(),
                            ]),

                        TextInput::make('customer_id')
                            ->label('Customer ID')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('wanted_date')
                            ->label('Wanted Date')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                
                Section::make('Performance Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                
                            ]),       
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_type')
                    ->label('Order Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'customer_order' => 'Customer Order',
                        'sample_order' => 'Sample Order',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('assignDailyOperationLine.operation.name')
                    ->label('Operation'),
                Tables\Columns\TextColumn::make('actual_quantity')
                    ->label('Quantity'),
                Tables\Columns\TextColumn::make('actual_time')
                    ->label('Time (min)'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListEnterPerformanceRecords::route('/'),
            'create' => Pages\CreateEnterPerformanceRecord::route('/create'),
            'edit' => Pages\EditEnterPerformanceRecord::route('/{record}/edit'),
        ];
    }
}