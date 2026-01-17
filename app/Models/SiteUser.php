<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteUser extends Model
{
    protected $table = 'site_user';

    protected $fillable = [
        'site_id',
        'user_id',
        'created_by',
        'updated_by',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
