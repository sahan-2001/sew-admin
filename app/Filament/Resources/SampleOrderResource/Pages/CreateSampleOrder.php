<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSampleOrder extends CreateRecord
{
    protected static string $resource = SampleOrderResource::class;
}
