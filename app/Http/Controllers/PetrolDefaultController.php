<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PetrolDefault;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class PetrolDefaultController extends Controller
{
    public function index(){
        $petrol=PetrolDefault::select('type','id')->limit(15)->get();

        return $petrol;
    }


    public function store(Request $request){
        $validated=Validator::make($request->all(),[
            'type'=>'required|string',
            'supplier'=>'string|nullable',
        ]);

        if($validated->fails()){
            return response([
                "message"=>"error while storing data please check inputs"
                ],300);
        }


        $petrol= new PetrolDefault([
            'type'=>$request->input('type'),
            'supplier'=>$request->input('supplier','uz'),
            'price'=>$request->input('price',0),
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
                        "message"=>$error->getMessage(),
                        "code"=>$error->getCode()
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

    public function edit($petrol){
        $petrol_collection = PetrolDefault::find($petrol,['type','id']);

        if(is_null($petrol_collection)){
            return response(['message'=>'no petrol with given id'],404);
        }else{
            return response($petrol_collection,200);
        }
    }

    public function update(Request $request,$petrolId){
        $validation=Validator::make($request->all(),[
            'type'=>'string|nullable'
        ]);
        if($validation->fails()){
            return response(['message'=>'values not satisfies the needs'],400);
        }

        $petrol = PetrolDefault::find($petrolId);

        $petrol->type = $request->input('type');

        if($petrol->save()){
            return response(['message'=>'record successfuly updated'],200);
        }else{
            return response(['message'=>'unable to update record'],400);
        }

    }
    //destroy petrol by id
    public function destroy($petrol_id){
        try {
            $success_state = PetrolDefault::destroy($petrol_id);
        } catch (QueryException $error) {
            return response(["message"=>$error->getMessage(),"code"=>$error->getCode()],500);
        }

        if ($success_state) {
            return response(["message"=>"record succesfully deleted","code"=>$success_state],200);
        }else{
            return response(["message"=>"error while deleting record","code"=>$success_state],500);
        }
    }
}
