<?php

namespace App\Filament\Resources\ControlAccountResource\Widgets;

use Filament\Widgets\Widget;

class ControlAccountButtons extends Widget
{
    protected static string $view = 'filament.resources.control-account-resource.widgets.control-account-buttons';

    // Make widget full width
    protected int|string|array $columnSpan = 'full';
}
