<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Models\SampleOrder;

class HandleSampleOrder extends Page
{
    protected static string $resource = SampleOrderResource::class;

    protected static string $view = 'filament.resources.sample-order.handle-sample-order';

    protected static ?string $title = 'Handle Sample Order';

    public SampleOrder $record;

    public function mount(SampleOrder $record)
    {
        $this->record = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Close')
                ->label('Close')
                ->color('secondary')
                ->url(fn () => SampleOrderResource::getUrl('index'))
                ->openUrlInNewTab(false),
        ];
    }
}
