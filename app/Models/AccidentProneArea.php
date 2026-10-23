<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccidentProneArea extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'barangay',
        'municipality',
        'province',
        'country',
        'place',
        'reason',
        'emergency_hit_count',
        'is_auto_generated',
    ];

    protected function casts(): array
    {
        return [
            'emergency_hit_count' => 'integer',
            'is_auto_generated' => 'boolean',
        ];
    }
}
