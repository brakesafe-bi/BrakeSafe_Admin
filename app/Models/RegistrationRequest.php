<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $table = 'registration_requests';

    protected $fillable = [
        'public_token',
        'first_name',
        'last_name',
        'birthday',
        'email',
        'status',
        'code_hash',
        'code_expires_at',
        'code_attempts',
        'completed_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'code_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'code_attempts' => 'integer',
        ];
    }
}
