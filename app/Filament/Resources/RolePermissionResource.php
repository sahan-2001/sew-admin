<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Navigation\NavigationItem;

class RolePermissionResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Role Name')
                    ->required(),
                Forms\Components\Select::make('permissions')
                    ->label('Assign Permissions')
                    ->multiple()
                    ->relationship('permissions', 'name')
                    ->options(Permission::all()->pluck('name', 'id'))
                    ->preload(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TagsColumn::make('permissions.name')
                    ->label('Permissions')
                    ->getStateUsing(function ($record) {
                        return $record->permissions->pluck('name')->toArray();
                    }),
            ])
            ->filters([
                // Define your filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any related models or relations
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRolePermissions::route('/'),
            'create' => Pages\CreateRolePermission::route('/create'),
            'edit' => Pages\EditRolePermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make()
                ->label('Roles & Permissions')
                ->icon(static::$navigationIcon)
                ->url(static::getUrl('index'))
                ->visible(fn () => auth()->user()->hasRole('admin')),
        ];
    }
}