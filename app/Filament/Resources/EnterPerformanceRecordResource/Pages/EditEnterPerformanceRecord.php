<?php

namespace App\Filament\Resources\EnterPerformanceRecordResource\Pages;

use App\Filament\Resources\EnterPerformanceRecordResource;
use App\Models\EnterPerformanceRecord;
use App\Models\EnterEmployeePerformance;
use App\Models\EnterMachinePerformance;
use App\Models\EnterSupervisorPerformance;
use App\Models\EnterServicePerformance;
use App\Models\EnterInvWastePerformance;
use App\Models\EnterNonInvPerformance;
use App\Models\EnterByProductsPerformance;
use App\Models\EnterQcPerformance;
use App\Models\EnterQcLabelPerformance;
use App\Models\EnterMachineLabelPerformance;
use App\Models\EnterEmployeeLabelPerformance;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditEnterPerformanceRecord extends EditRecord
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}