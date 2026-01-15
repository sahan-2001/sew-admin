<?php

// app/Imports/DynamicModelImport.php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DynamicModelImport implements ToModel, WithHeadingRow
{
    protected string $model;
    protected array $fields;

    public function __construct(string $model, array $fields)
    {
        $this->model  = $model;
        $this->fields = $fields;
    }

    public function model(array $row)
    {
        $data = [];

        foreach ($this->fields as $field) {
            if (isset($row[$field])) {
                $data[$field] = $row[$field];
            }
        }

        return new $this->model($data);
    }
}
