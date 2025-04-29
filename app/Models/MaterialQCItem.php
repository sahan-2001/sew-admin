<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialQCItem extends Model
{
    use HasFactory;

    protected $table = 'material_qc_items';

    protected $fillable = ['material_qc_id', 'item_id', 'quantity', 'status'];

    public function materialQC()
    {
        return $this->belongsTo(MaterialQC::class, 'material_qc_id'); // Ensure the foreign key is correct
    }
}