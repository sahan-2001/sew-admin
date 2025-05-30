<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UMOperationLineMachine extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['production_machine_id', 'u_m_operation_line_id', 'created_by', 'updated_by'];

    public function line()
    {
        return $this->belongsTo(UMOperationLine::class, 'u_m_operation_line_id');
    }

    public function productionMachine()
    {
        return $this->belongsTo(ProductionMachine::class, 'production_machine_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}