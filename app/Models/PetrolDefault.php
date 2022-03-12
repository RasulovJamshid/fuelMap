<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetrolDefault extends Model
{
    use HasFactory;

    protected $fillable=[
        'type',
        'price',
        'supplier'
    ];

//     public function fuelStations(){
//         return $this->hasMany(FuelStation::class);
//     }
}
