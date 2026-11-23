<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotStation extends Model
{
    protected $table = 'iot_stations';

    protected $fillable = [
        'station_code', 'name', 'neighborhood', 'latitude', 'longitude',
        'sensor_type', 'metric_name', 'metric_unit', 'warning_threshold',
        'critical_threshold', 'reading_value', 'reading_status', 'read_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'warning_threshold' => 'float',
            'critical_threshold' => 'float',
            'reading_value' => 'float',
            'read_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
