<?php

namespace App\Filament\Resources\DatabaseRecordResource\Pages;

use App\Filament\Resources\DatabaseRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDatabaseRecord extends CreateRecord
{
    protected static string $resource = DatabaseRecordResource::class;
}
