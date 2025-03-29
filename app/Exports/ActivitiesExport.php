<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ActivitiesExport implements FromCollection, WithHeadings
{
    protected $activities;

    public function __construct(Collection $activities)
    {
        $this->activities = $activities;
    }

    public function collection()
    {
        return $this->activities->map(function ($activity) {
            return [
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'caused_by' => $activity->causer->name ?? 'N/A',
                'created_at' => $activity->created_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Log Name',
            'Description',
            'Caused By',
            'Created At',
        ];
    }
}