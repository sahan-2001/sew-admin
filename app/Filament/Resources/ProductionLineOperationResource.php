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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;


class ProductionLineOperationResource extends Resource
{
    protected static ?string $model = Workstation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Production Line Operations';
    protected static ?string $navigationGroup = 'Production Management';
    protected static ?int $navigationSort = 18;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view workstations') ?? false;
    }
    
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


                Forms\Components\Section::make('Operations')
                    ->schema([
                    Forms\Components\Repeater::make('operations')
                        ->relationship()
                        ->schema([
                            Forms\Components\Grid::make(12) 
                                ->schema([                                 
                                    Forms\Components\Textarea::make('description')
                                        ->label('Description')
                                        ->required()
                                        ->columnSpan(4), 
                                        
                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->hidden()
                                        ->options([
                                            'active' => 'Active',
                                            'inactive' => 'Inactive',
                                        ])
                                        ->default('active')
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('employee_id')
                                        ->label('Default Employee')
                                        ->options(function () {
                                            return User::role('employee')->pluck('name', 'id'); 
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('supervisor_id')
                                        ->label('Default Supervisor')
                                        ->options(function () {
                                            return User::role('supervisor')->pluck('name', 'id');
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('third_party_service_id')
                                        ->label('Default Third Party Service')
                                        ->options(ThirdPartyService::all()->pluck('id', 'id'))
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('machine_id')
                                        ->label('Default Production Machine')
                                        ->options(ProductionMachine::all()->pluck('name', 'id'))
                                        ->columnSpan(3),

                                    Forms\Components\TextInput::make('machine_setup_time')
                                        ->label('Machine Setup Time')
                                        ->numeric()
                                        ->default(0)
                                        ->maxValue(24)
                                        ->placeholder('Enter machine setup time in minitues')
                                        ->columnSpan(2),
                                    
                                    Forms\Components\TextInput::make('machine_run_time')
                                        ->label('Machine Run Time')
                                        ->numeric()
                                        ->default(0)
                                        ->maxValue(24)
                                        ->placeholder('Enter machine run time in minitues')
                                        ->columnSpan(2),
                                    
                                    Forms\Components\TextInput::make('labor_setup_time')
                                        ->label('Labor Setup Time')
                                        ->numeric()
                                        ->default(0)
                                        ->maxValue(24)
                                        ->placeholder('Enter labor setup time in minitues')
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('labor_run_time')
                                        ->label('Labor Run Time')
                                        ->numeric()
                                        ->default(0)
                                        ->maxValue(24)
                                        ->placeholder('Enter labor run time in minitues')
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->minItems(1)
                        ->label('Operation Lines')
                        ->required()
                        ->columnSpan(12),
                    ]),
            ]);
    }

   public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Workstation ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('name')
                    ->label('Workstation Name')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('production_line_id')
                    ->label('Production Line ID')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
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
                    ->label('Workstation ID')
                    ->form([
                        Forms\Components\TextInput::make('Workstation ID')
                            ->label('Workstation ID')
                            ->placeholder('Enter ID'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('id', 'like', '%' . $data['value'] . '%');
                        }
                    }),

                Filter::make('production_line_id')
                    ->label('Production Line')
                    ->form([
                        Forms\Components\Select::make('Production Line ID')
                            ->options(\App\Models\ProductionLine::pluck('name', 'id'))
                            ->placeholder('Select Production Line'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('production_line_id', $data['value']);
                        }
                    }),

                Filter::make('status')
                    ->label('Status')
                    ->form([
                        Forms\Components\Select::make('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->placeholder('Select Status'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('status', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn ($record) => $record->status === 'active' ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => auth()->user()->can('edit workstations'))
                    ->action(function ($record) {
                        $record->status = $record->status === 'active' ? 'inactive' : 'active';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Workstation Status Updated')
                            ->body("Workstation has been marked as '{$record->status}'.")
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->can('edit workstations')),

                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()->can('delete workstations')),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
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
