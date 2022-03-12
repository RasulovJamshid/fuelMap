<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Petrol;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class PetrolController extends Controller
{
    //lists all petrols
    public function index(){
        $petrol=Petrol::all();

        return $petrol;
    }
    //list petrols binded to stationId
    public function station_binded(Request $request,$stationId){
        $request->merge(['stationId'=>$stationId]);
        $validated = Validator::make($request->all(),[
            'stationId'=>'numeric'
        ]);

        if($validated->fails()){
            return response(['message'=>'please check input values'],409);
        }

        $petrols = Petrol::select(['id','type','supplier','price','is_available'])->where('fuel_station_id',$stationId)->get();

        if($petrols->isEmpty()){
            return response(['message'=>'no records with given id'],404);
        }else{
            return $petrols;
        }

    }
    //creates new petrol column
    public function store(Request $request){
        $validated=Validator::make($request->all(),[
            'type'=>'required|string',
            'price'=>'numeric|nullable',
            'fuel_station_id'=>'required|integer'
        ]);

        if($validated->fails()){
            return response([
                "message"=>"error while storing data please check inputs"
                ],300);
        }


        $petrol= new Petrol([
            'type'=>$request->input('type'),
            'price'=>$request->input('price',0),
            'supplier'=>$request->input('supplier','uz'),
            'fuel_station_id'=>$request->input('fuel_station_id'),
        ]);

        $success_status=false;

        //checks petrol-type for duplicate entry
        try{
            $success_status=$petrol->save();
        }catch(QueryException $error){
            $code=$error->errorInfo[1];
            if($code == 1062){
                return response(["message"=>"duplicate entry for type field"],409); //Conflict
            }else{
                return response(
                    [
                        "message"=>$error->errorInfo[0],
                        "code"=>$error->errorInfo[1]
                    ],500);
            }
        }

        //response success if record saved
        if($success_status){
            return response(["message"=>"success"],200);
        }else{
            return response(["message"=>"cannot save data to the database"],500);
        }

    }

    public function edit($petrolId){
        $petrol = Petrol::find($petrolId,['name','id']);

        if(is_null($petrol)){
            return response(['message'=>'no petrol with given id'],404);
        }else{
            return response($petrol,200);
        }
    }

    public function update(Request $request,$petrolId){
        $validation=Validator::make($request->all(),[
            'name'=>'string|nullable'
        ]);
        if($validation->fails()){
            return response(['message'=>'values not satisfies the needs'],400);
        }

        $petrol = Petrol::find($petrolId);

        $petrol->name = $request->input('name');

        if($petrol->save()){
            return response(['message'=>'record successfuly updated'],200);
        }else{
            return response(['message'=>'unable to update record'],400);
        }

    }


    //destroy petrol by id
    public function destroy($petrol_id){
        try {
            $success_state = Petrol::destroy($petrol_id);
        } catch (QueryException $error) {
            return response(["message"=>$error->errorInfo[0],"code"=>$error->errorInfo[1]],500);
        }

        if ($success_state) {
            return response(["message"=>"record succesfully deleted","code"=>$success_state],200);
        }else{
            return response(["message"=>"error while deleting record","code"=>$success_state],500);
        }
    }

}
