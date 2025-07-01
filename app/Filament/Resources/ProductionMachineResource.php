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
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\Filter;

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
                Section::make('Basic Machine Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('description')->nullable(),
                    ]),
                
                Section::make('Purchase Details')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('purchased_date')->required()->maxDate(Carbon::now()),
                        DatePicker::make('start_working_date')->required()->maxDate(Carbon::now()),
                        TextInput::make('expected_lifetime')->label('Expected Lifetime (Years)')->numeric()->required(),
                    ]),

                    
                Section::make('Supplier Details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('purchased_cost')->numeric()->prefix('Rs.')->required(),
                        TextInput::make('additional_cost')->numeric()->prefix('Rs.')->nullable(),
                        TextInput::make('additional_cost_description')->nullable(),
                    ]),

                Section::make('Depreciation Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('depreciation_rate')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        Select::make('depreciation_calculated_from')
                            ->options([
                                'purchased_date' => 'Purchased Date',
                                'start_working_date' => 'Start Working Date',
                            ])
                            ->required(),
                    ]),
                
                        TextInput::make('total_initial_cost')
                            ->numeric()
                            ->disabled()
                            ->hidden()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $get) {
                                $purchasedCost = $get('purchased_cost') ?? 0;
                                $additionalCost = $get('additional_cost') ?? 0;
                                $component->state($purchasedCost + $additionalCost);
                            }),

                        TextInput::make('cumulative_depreciation')
                            ->numeric()
                            ->disabled()
                            ->hidden()
                            ->dehydrated(false),
                        TextInput::make('net_present_value')
                            ->numeric()
                            ->disabled()
                            ->hidden()
                            ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Machine ID'),
                TextColumn::make('name')->label('Machine Name')->searchable(),
                TextColumn::make('expected_lifetime'),
                TextColumn::make('purchased_cost')->money('LKR'),
                TextColumn::make('cumulative_depreciation')->money('LKR'),
                TextColumn::make('net_present_value')->money('LKR'),
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
                Filter::make('id')
                    ->label('Machine ID')
                    ->form([
                        TextInput::make('id')->numeric()->label('Machine ID'),
                    ])
                    ->query(fn ($query, array $data) => 
                        $data['id'] 
                            ? $query->where('id', $data['id']) 
                            : $query
                    ),

                Filter::make('expected_lifetime')
                    ->label('Expected Lifetime')
                    ->form([
                        TextInput::make('expected_lifetime')->numeric()->label('Expected Lifetime'),
                    ])
                    ->query(fn ($query, array $data) => 
                        $data['expected_lifetime'] 
                            ? $query->where('expected_lifetime', $data['expected_lifetime']) 
                            : $query
                    ),

                Filter::make('depreciation_rate')
                    ->label('Depreciation Rate')
                    ->form([
                        TextInput::make('depreciation_rate')->numeric()->label('Depreciation Rate'),
                    ])
                    ->query(fn ($query, array $data) => 
                        $data['depreciation_rate'] 
                            ? $query->where('depreciation_rate', $data['depreciation_rate']) 
                            : $query
                    ),
            ])
            ->actions([
                 ViewAction::make()
                    ->label('View')
                    ->modalHeading(fn ($record) => "Production Machine Details: {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(fn ($record) => [
                        Forms\Components\Section::make('Details')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->default($record->name)
                                    ->disabled(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->default($record->description)
                                    ->disabled()
                                    ->columnSpan(2), // textarea can take full width

                                Forms\Components\DatePicker::make('purchased_date')
                                    ->label('Purchased Date')
                                    ->default($record->purchased_date)
                                    ->disabled(),

                                Forms\Components\DatePicker::make('start_working_date')
                                    ->label('Start Working Date')
                                    ->default($record->start_working_date)
                                    ->disabled(),

                                Forms\Components\TextInput::make('expected_lifetime')
                                    ->label('Expected Lifetime (years)')
                                    ->default($record->expected_lifetime)
                                    ->disabled(),

                                Forms\Components\TextInput::make('purchased_cost')
                                    ->label('Purchased Cost')
                                    ->default(number_format($record->purchased_cost, 2))
                                    ->disabled(),

                                Forms\Components\TextInput::make('additional_cost')
                                    ->label('Additional Cost')
                                    ->default(number_format($record->additional_cost ?? 0, 2))
                                    ->disabled(),

                                Forms\Components\TextInput::make('additional_cost_description')
                                    ->label('Additional Cost Description')
                                    ->default($record->additional_cost_description)
                                    ->disabled()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('total_initial_cost')
                                    ->label('Total Initial Cost')
                                    ->default(number_format($record->total_initial_cost, 2))
                                    ->disabled(),

                                Forms\Components\TextInput::make('depreciation_rate')
                                    ->label('Depreciation Rate')
                                    ->default($record->depreciation_rate)
                                    ->disabled(),

                                Forms\Components\TextInput::make('depreciation_calculated_from')
                                    ->label('Depreciation Calculated From')
                                    ->default($record->depreciation_calculated_from)
                                    ->disabled(),

                                Forms\Components\DatePicker::make('last_depreciation_calculated_date')
                                    ->label('Last Depreciation Calculated Date')
                                    ->default($record->last_depreciation_calculated_date)
                                    ->disabled(),

                                Forms\Components\TextInput::make('depreciation_last')
                                    ->label('Last Depreciation Amount')
                                    ->default(number_format($record->depreciation_last ?? 0, 2))
                                    ->disabled(),

                                Forms\Components\TextInput::make('cumulative_depreciation')
                                    ->label('Cumulative Depreciation')
                                    ->default(number_format($record->cumulative_depreciation ?? 0, 2))
                                    ->disabled(),

                                Forms\Components\TextInput::make('net_present_value')
                                    ->label('Net Present Value')
                                    ->default(number_format($record->net_present_value ?? 0, 2))
                                    ->disabled(),
                            ]),
                        ]),
                    
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
