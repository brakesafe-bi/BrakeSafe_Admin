<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrakeEvent extends Model
{
    protected $fillable = [
        'vehicle_id',
        'device_id',
        'latitude',
        'longitude',
        'speed_kmph',
        'speed_kmh',
        'deceleration_force',
        'brake_classification',
        'timestamp',
        'classification_reason',
        'municipality',
        'barangay',
        'direction',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'apa_processed_at' => 'datetime',
        ];
    }

    public function getDecelerationGAttribute()
    {
        return $this->deceleration_force;
    }

    public function setDecelerationGAttribute($value)
    {
        $this->attributes['deceleration_force'] = $value;
    }

}
