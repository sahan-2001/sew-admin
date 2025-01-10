<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        $actions = [
            Actions\ButtonAction::make('save')
                ->label('Save')
                ->action('save'),
        ];

        if (auth()->user()->hasAnyRole(['admin', 'superuser'])) {
            $actions[] = Actions\DeleteAction::make();
        }

        return $actions;
    }

    protected static function canEdit($record): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'superuser', 'manager']);
    }

    public function resetPassword()
    {
        $this->record->password = bcrypt('12345678');
        $this->record->save();
    }
}