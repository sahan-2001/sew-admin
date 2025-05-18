<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UMOperation extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['order_type', 'order_id', 'created_by', 'updated_by'];

   public function Lines()
    {
        return $this->hasMany(UMOperationLine::class, 'u_m_operation_id'); 
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