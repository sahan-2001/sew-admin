<?php

namespace App\Filament\Resources;

use App\Models\ProductionMachine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\ProductionMachineResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class ProductionMachineResource extends Resource
{
    protected static ?string $model = ProductionMachine::class;

    protected static ?string $navigationGroup = 'Assets';
    protected static ?string $navigationLabel = 'Production Machines';
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('description')->nullable(),
                    DatePicker::make('purchased_date')->required(),
                    DatePicker::make('start_working_date')->required(),
                    TextInput::make('expected_lifetime')->numeric()->required(),
                    TextInput::make('purchased_cost')->numeric()->required(),
                    TextInput::make('additional_cost')->numeric()->nullable(),
                    TextInput::make('additional_cost_description')->nullable(),
                    TextInput::make('depreciation_rate')
                        ->numeric()
                        ->suffix('%')
                        ->required()
                        ->minValue(0)
                        ->maxValue(100)
                        ->dehydrateStateUsing(fn ($state) => $state / 100),
                    Select::make('depreciation_calculated_from')
                        ->options([
                            'purchased_date' => 'Purchased Date',
                            'start_working_date' => 'Start Working Date',
                        ])
                        ->required(),
                    TextInput::make('total_initial_cost')
    ->numeric()
    ->disabled()
    ->dehydrated(false)
    ->afterStateHydrated(function ($component, $state, $get) {
        $purchasedCost = $get('purchased_cost') ?? 0;
        $additionalCost = $get('additional_cost') ?? 0;
        $component->state($purchasedCost + $additionalCost);
    }),

                    TextInput::make('cumulative_depreciation')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('net_present_value')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Machine Name'),
                TextColumn::make('purchased_date')->date(),
                TextColumn::make('start_working_date')->date(),
                TextColumn::make('expected_lifetime'),
                TextColumn::make('purchased_cost')->money('USD'),
                TextColumn::make('total_initial_cost')->money('USD'),
                TextColumn::make('cumulative_depreciation')->money('USD'),
                TextColumn::make('net_present_value')->money('USD'),

            ])
            ->filters([])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('edit production machines') 
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('delete production machines') 
                    ),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionMachines::route('/'),
            'create' => Pages\CreateProductionMachine::route('/create'),
            'edit' => Pages\EditProductionMachine::route('/{record}/edit'),
        ];
    }
}
