<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'category', 'neighborhood', 'description', 'source_type', 'source_name', 'latitude', 'longitude',
        'status', 'occurred_at', 'reviewed_at', 'affected_area', 'source_url',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'affected_area' => 'array',
        ];
    }
}
