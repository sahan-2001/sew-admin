<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerOrderResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CustomerOrderResource\Pages;
use App\Models\CustomerOrder;
use Filament\Resources\Resource;

class CustomerOrderResource extends Resource
{
    protected static ?string $model = CustomerOrder::class;

    protected static ?string $slug = 'customer-orders';

    protected static ?string $navigationLabel = 'Customer Orders';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Define your form fields here
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Define your table columns here
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerOrders::route('/'),
            'create' => Pages\CreateCustomerOrder::route('/create'),
            'edit' => Pages\EditCustomerOrder::route('/{record}/edit'),
        ];
    }
}