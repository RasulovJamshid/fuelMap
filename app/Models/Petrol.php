<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Petrol extends Model
{
    use HasFactory;

    protected $fillable=[
        'type',
        'price',
        'is_available',
        'supplier',
        'fuel_station_id'
    ];

    public function fuelStation(){
        return $this->belongsTo(FuelStation::class);
    }
}
