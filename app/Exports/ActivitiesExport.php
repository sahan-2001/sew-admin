<?php

namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivitiesExport implements FromCollection, WithHeadings
{
    protected bool $viewAll;
    protected ?string $fromDate;
    protected ?string $toDate;
    protected string $permissionLabel;

    public function __construct(bool $viewAll, ?string $fromDate = null, ?string $toDate = null)
    {
        $this->viewAll = $viewAll;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->permissionLabel = $viewAll ? 'View All Users' : 'View Self Only';
    }

    public function collection()
    {
        $query = Activity::query()->with('causer')->orderByDesc('created_at');

        if (! $this->viewAll) {
            $query->where('causer_id', Auth::id());
        }

        if ($this->fromDate) {
            $query->where('created_at', '>=', $this->fromDate . ' 00:00:00');
        }

        if ($this->toDate) {
            $query->where('created_at', '<=', $this->toDate . ' 23:59:59');
        }

        return $query->get()->map(function ($log) {
            return [
                'Permission Scope' => $this->permissionLabel,
                'Log Name'         => $log->log_name,
                'Description'      => $log->description,
                'Caused By'        => $log->causer?->name ?? 'System',
                'Created At'       => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Permission Scope', 'Log Name', 'Description', 'Caused By', 'Created At'];
    }
}
