<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyBrakeApaCluster extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'hit_count',
        'accident_prone_area_id',
    ];

    public function accidentProneArea()
    {
        return $this->belongsTo(AccidentProneArea::class);
    }
}
