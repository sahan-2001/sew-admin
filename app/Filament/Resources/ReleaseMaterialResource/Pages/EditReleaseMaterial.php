<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use App\Models\Stock;
use App\Models\ReleaseMaterialLine;

class EditReleaseMaterial extends EditRecord
{
    protected static string $resource = ReleaseMaterialResource::class;
    protected function getHeaderActions(): array
    {
        return [
        ];

    }
}