<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;

class EditSampleOrder extends EditRecord
{
    protected static string $resource = SampleOrderResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::pluck('name', 'customer_id')->toArray())
                    ->searchable()
                    ->disabled(),

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->disabled(),

                TextInput::make('phone_1')
                    ->label('Phone 1')
                    ->disabled(),

                TextInput::make('phone_2')
                    ->label('Phone 2')
                    ->disabled(),

                TextInput::make('email')
                    ->label('Email')
                    ->disabled(),

                TextInput::make('name')
                    ->label('Order Name')
                    ->required(),

                DatePicker::make('wanted_delivery_date')
                    ->label('Wanted Delivery Date')
                    ->required(),

                Textarea::make('special_notes')
                    ->label('Special Notes')
                    ->nullable(),

                Section::make('Order Items')
                    ->schema([
                        Repeater::make('sample_order_items')
                            ->relationship('items')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('item_name')
                                            ->label('Item Name')
                                            ->required(),

                                        Toggle::make('is_variation')
                                            ->label('Is Variation')
                                            ->default(false)
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $set('quantity', 0);
                                                    $set('price', 0);
                                                    $set('total', 0);
                                                }
                                            }),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set, $state, $get) => $set('total', $state * $get('price')))
                                            ->visible(fn ($get) => !$get('is_variation')),

                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set, $state, $get) => $set('total', $state * $get('quantity')))
                                            ->visible(fn ($get) => !$get('is_variation')),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price'))
                                            ->visible(fn ($get) => !$get('is_variation')),
                                    ]),
                                
                                // Variation Items (Editable)
                                Repeater::make('sample_order_variations')
                                    ->label('Variation Items')
                                    ->relationship('variations')
                                    ->schema([
                                        TextInput::make('variation_name')
                                            ->label('Variation Name')
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price')),
                                    ])
                                    ->columns(4)
                                    ->visible(fn ($get) => $get('is_variation') == true)
                                    ->addable()
                                    ->reorderable()
                                    ->createItemButtonLabel('Add Variation Item'),
                            ])
                            ->columns(1)
                            ->createItemButtonLabel('Add Order Item'),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $customer = Customer::find($data['customer_id']);
        if ($customer) {
            $data['customer_name'] = $customer->name;
            $data['phone_1'] = $customer->phone_1;
            $data['phone_2'] = $customer->phone_2;
            $data['email'] = $customer->email;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record->getKey()]);
    }
}
