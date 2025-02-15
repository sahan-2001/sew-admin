<?php

namespace App\Filament\Resources\RegisterArrivalItemResource\Pages;

use App\Filament\Resources\RegisterArrivalItemResource;
use Filament\Resources\Pages\Page;

class RegisterArrivalItemCreate extends Page
{
    protected static string $resource = RegisterArrivalItemResource::class;

    protected static string $view = 'filament.resources.register-arrival-item-resource.pages.register-arrival-item-create';
}
