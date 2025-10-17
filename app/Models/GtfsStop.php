<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtfsStop extends Model
{
    use HasFactory;

    protected $table = 'gtfs_stops';

    protected $primaryKey = 'stop_id';

    public $incrementing = false;

    protected $fillable = [
        'stop_id',
        'stop_name',
        'stop_lat',
        'stop_lon',
        'location_type',
        'parent_station',
        'platform_code',
    ];

    protected $casts = [
        'stop_lon' => 'decimal:6',
        'stop_lat' => 'decimal:6',
        'location_type' => 'integer',
    ];

    public function stopTimes()
    {
        return $this->hasMany(GtfsStopTime::class, 'stop_id', 'stop_id');
    }
}
