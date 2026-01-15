<?php   

// app/Exports/DynamicModelExport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DynamicModelExport implements FromCollection, WithHeadings
{
    protected string $model;
    protected array $fields;

    public function __construct(string $model, array $fields)
    {
        $this->model = $model;
        $this->fields = $fields;
    }

    public function collection()
    {
        return ($this->model)::select($this->fields)->get();
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
