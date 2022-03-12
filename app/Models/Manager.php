<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'user_id',
        'station_name',
        'fuel_station_id'
    ];
    public function fuelStation(){
        return $this->belongsTo(FuelStation::class);
    }
}
