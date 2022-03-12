<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelStation extends Model
{
    use HasFactory;
    // use SpatialTrait;


    public function petrols(){
        return $this->hasMany(Petrol::class);
    }

    public function managers(){
        return $this->hasMany(Manager::class);
    }

    protected $fillable=[
        'name','lat','lon','address','img_url','status','is_active','access_list'];

}
