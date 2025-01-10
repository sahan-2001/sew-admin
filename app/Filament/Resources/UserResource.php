<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->default('12345678') // Default password for new users
                    ->required(fn ($record) => !$record) // Only required for new users
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null), // Hash the password
                Forms\Components\Select::make('roles')
                    ->label('Assign Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->required()
                    ->options(Role::all()->pluck('name', 'id')), // Populating the dropdown with available roles
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TagsColumn::make('roles.name')->label('Roles'),
            ])
            ->filters([
                // Define your filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => auth()->user()->can('edit users')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => auth()->user()->can('delete users')),
                Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->action(function (User $record) {
                        $record->password = bcrypt('12345678');
                        $record->save();
                    })
                    ->visible(fn (User $record) => auth()->user()->can('edit users')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete users')),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}