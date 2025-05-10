<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionLineOperationResource\Pages;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\Operation;
use App\Models\User;
use App\Models\ThirdPartyService;
use App\Models\ProductionMachine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Role;

class ProductionLineOperationResource extends Resource
{
    protected static ?string $model = Workstation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Production Line Operations';
    protected static ?string $navigationGroup = 'Production Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\Select::make('production_line_id')
                                    ->label('Production Line')
                                    ->options(ProductionLine::all()->pluck('name', 'id'))
                                    ->required()
                                    ->columnSpan(6), 
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->columnSpan(6), 

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->nullable()
                                    ->columnSpan(12),
                            ]),
                    ]),


                    Forms\Components\Repeater::make('operations')
                        ->relationship()
                        ->schema([
                            Forms\Components\Grid::make(12) 
                                ->schema([                                 
                                    Forms\Components\TextInput::make('sequence')
                                        ->label('Sequence')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1), 

                                    Forms\Components\Textarea::make('description')
                                        ->label('Description')
                                        ->required()
                                        ->columnSpan(4), 
                                        
                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'active' => 'Active',
                                            'inactive' => 'Inactive',
                                        ])
                                        ->default('active')
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('employee_id')
                                        ->label('Employee')
                                        ->options(function () {
                                            return User::role('employee')->pluck('name', 'id'); 
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('supervisor_id')
                                        ->label('Supervisor')
                                        ->options(function () {
                                            return User::role('supervisor')->pluck('name', 'id');
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('third_party_service_id')
                                        ->label('Third Party Service')
                                        ->options(ThirdPartyService::all()->pluck('id', 'id'))
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('machine_id')
                                        ->label('Machine')
                                        ->options(ProductionMachine::all()->pluck('name', 'id'))
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('setup_time')
                                        ->label('Setup Time')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('run_time')
                                        ->label('Run Time')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->minItems(1)
                        ->label('Operation Lines')
                        ->required()
                        ->columnSpan(12),
            ]);
    }

   

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionLineOperations::route('/'),
            'create' => Pages\CreateProductionLineOperation::route('/create'),
            'edit' => Pages\EditProductionLineOperation::route('/{record}/edit'),
        ];
    }
}
