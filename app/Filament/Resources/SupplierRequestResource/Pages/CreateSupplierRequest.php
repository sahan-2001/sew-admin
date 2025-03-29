<?php

namespace App\Filament\Resources\SupplierRequestResource\Pages;

use App\Filament\Resources\SupplierRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;

class CreateSupplierRequest extends CreateRecord
{
    protected static string $resource = SupplierRequestResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('shop_name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('address')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->required()
                ->email()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone_1')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone_2')
                ->maxLength(255),
            Forms\Components\Textarea::make('note'),
            Forms\Components\Hidden::make('outstanding_balance')
                ->default(0),
            Forms\Components\Hidden::make('requested_by')
                ->default(auth()->user()->id),
            Forms\Components\Hidden::make('approved_by'),
            Forms\Components\Hidden::make('status')
                ->default('pending'),
        ];
    }
}