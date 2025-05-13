<?php
namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReleaseMaterial extends CreateRecord
{
    protected static string $resource = ReleaseMaterialResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $releaseMaterial = parent::handleRecordCreation($data);

        // Deduct stock after creating the release material
        $releaseMaterial->deductStock();

        return $releaseMaterial;
    }
}