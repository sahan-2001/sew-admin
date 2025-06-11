<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordDailyEmployeePerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'task',
        'output_quantity',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'output_quantity' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
