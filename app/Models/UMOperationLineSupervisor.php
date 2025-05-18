<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UMOperationLineSupervisor extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['user_id', 'u_m_operation_line_id', 'created_by', 'updated_by',];

    public function line()
    {
        return $this->belongsTo(UMOperationLine::class, 'u_m_operation_line_id');
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}