<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use App\Models\FixedAssetControlAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Forms;

class FixedAssetControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;
    protected static ?string $title = 'Fixed Asset Control Accounts';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ControlAccountResource\Widgets\ControlAccountButtons::class,
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(FixedAssetControlAccount::query())
            ->columns([
                Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('asset_category')->label('Category'),
                Tables\Columns\TextColumn::make('net_book_value')->label('NBV'),
                Tables\Columns\TextColumn::make('is_active')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
            ])

            ->actions([

                // --------------------------------------------------------------------------------------------------
                // 🔍 VIEW ACTION (READ-ONLY)
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Fixed Asset Details')
                    ->modalWidth('5xl') // wider layout
                    ->fillForm(fn(FixedAssetControlAccount $record) => $record->toArray())
                    ->form([

                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('code')->disabled(),
                                    Forms\Components\TextInput::make('name')->disabled(),
                                    Forms\Components\TextInput::make('asset_category')->label('Category')->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Cost Breakdown')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('purchasing_cost')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('additional_cost_1')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('additional_cost_description_1')->disabled(),

                                    Forms\Components\TextInput::make('additional_cost_2')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('additional_cost_description_2')->disabled(),

                                    Forms\Components\TextInput::make('additional_cost_3')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('additional_cost_description_3')->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Depreciation & NBV')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('total_initial_cost')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('accumulated_depreciation')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('net_book_value')->numeric()->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Balance Summary')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('debit_balance')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('credit_balance')->numeric()->disabled(),
                                    Forms\Components\TextInput::make('net_debit_balance')->numeric()->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')->disabled(),
                            ]),
                    ])
                    ->action(fn() => null),

                // --------------------------------------------------------------------------------------------------
                // ✏️ EDIT ACTION
                // --------------------------------------------------------------------------------------------------
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Edit Fixed Asset Control Account')
                    ->modalWidth('5xl')
                    ->fillForm(fn(FixedAssetControlAccount $record) => $record->toArray())
                    ->form([
                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('code')->required(),
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\TextInput::make('asset_category')->label('Category')->required(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Cost Breakdown')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('purchasing_cost')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_1')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_1'),

                                    Forms\Components\TextInput::make('additional_cost_2')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_2'),

                                    Forms\Components\TextInput::make('additional_cost_3')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_3'),
                                ]),
                            ]),

                        Forms\Components\Section::make('Depreciation & NBV')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('total_initial_cost')->numeric()->reactive()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('net_book_value', $state - $set('accumulated_depreciation'))),
                                    Forms\Components\TextInput::make('accumulated_depreciation')->numeric()->reactive()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('net_book_value', $set('total_initial_cost') - $state)),
                                    Forms\Components\TextInput::make('net_book_value')->numeric()->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Balance Summary')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('debit_balance')->numeric(),
                                    Forms\Components\TextInput::make('credit_balance')->numeric(),
                                    Forms\Components\TextInput::make('net_debit_balance')->numeric(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')->label('Active'),
                            ]),
                    ])
                    ->action(function (array $data, FixedAssetControlAccount $record) {
                        $record->update($data);

                        Notification::make()
                            ->title('Updated successfully')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])

            // --------------------------------------------------------------------------------------------------
            // ➕ CREATE ACTION
            // --------------------------------------------------------------------------------------------------
            ->headerActions([
                Action::make('create')
                    ->label('Add Fixed Asset Control Account')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('5xl')
                    ->color('success')
                    ->form([
                        Forms\Components\Section::make('Basic Information')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('code')->required(),
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\TextInput::make('asset_category')->required(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Cost Breakdown')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('purchasing_cost')->numeric()->required(),
                                    Forms\Components\TextInput::make('additional_cost_1')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_1'),

                                    Forms\Components\TextInput::make('additional_cost_2')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_2'),

                                    Forms\Components\TextInput::make('additional_cost_3')->numeric(),
                                    Forms\Components\TextInput::make('additional_cost_description_3'),
                                ]),
                            ]),

                        Forms\Components\Section::make('Depreciation & NBV')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('total_initial_cost')->numeric()->reactive()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('net_book_value', $state - $set('accumulated_depreciation'))),
                                    Forms\Components\TextInput::make('accumulated_depreciation')->numeric()->reactive()->disabled()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('net_book_value', $set('total_initial_cost') - $state)),
                                    Forms\Components\TextInput::make('net_book_value')->numeric()->disabled(),
                                ]),
                            ]),

                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')->default(true)->label('Active'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        FixedAssetControlAccount::create($data);

                        Notification::make()
                            ->title('Created successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
