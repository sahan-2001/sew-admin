<?php

namespace App\Filament\Resources\VatGroupResource\Pages;

use App\Filament\Resources\VatGroupResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use App\Models\SupplierVatGroup;

class SupplierVatGroups extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = VatGroupResource::class;

    protected static string $view =
        'filament.resources.vat-group-resource.pages.supplier-vat-groups';

    /* ---------------------------------
     | Page Header Action (Create Button)
     |----------------------------------*/
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Supplier VAT Group')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Supplier VAT Group')
                ->form($this->getFormSchema())
                ->action(fn (array $data) =>
                    SupplierVatGroup::create($data)
                ),
        ];
    }

    /* ---------------------------------
     | Table
     |----------------------------------*/
    public function table(Table $table): Table
    {
        return $table
            ->query(SupplierVatGroup::query())

            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vat_group_name')
                    ->label('VAT Group'),

                Tables\Columns\TextColumn::make('vat_rate')
                    ->label('VAT %')
                    ->numeric(2),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->getFormSchema()),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /* ---------------------------------
     | Shared Form Schema
     |----------------------------------*/
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('code')
                ->label('VAT Code')
                ->required()
                ->unique(
                    SupplierVatGroup::class,
                    'code',
                    ignoreRecord: true
                )
                ->maxLength(50),

            Forms\Components\TextInput::make('vat_group_name')
                ->label('VAT Group Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('vat_rate')
                ->label('VAT Rate (%)')
                ->numeric()
                ->required(),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->required(),
        ];
    }
}
