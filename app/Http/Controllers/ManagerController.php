<?php

namespace App\Http\Controllers;

use App\Models\FuelStation;
use App\Models\Petrol;
use App\Models\Manager;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    public function store(Request $request){

        $validated=Validator::make($request->all(),[
            'user_id'=>'required|numeric',
            'name'=>'string|nullable',
            'station_name'=>'string|nullable',
            'fuel_station_id'=>'numeric|nullable',
        ]);

        if($validated->fails()){
            return response([
                "message"=>"unable to create manager",
            ],400);
        }
        $manager=new Manager([
            'name'=>$request->input('name'),
            'user_id'=>$request->input('user_id'),
            'station_name'=>$request->input('station_name','default'),
            'fuel_station_id'=>$request->input('fuel_station_id',0),
        ]);

        //checks proccess of storing to the database
        $manager->save();
    }
    public function chunk(Request $request){
        $managers = Manager::paginate(30);

        return response($managers,200);
    }

    public function find_by_name(Request $request){
        validator($request->all(),[
            'name'=>'required'
        ]);

        $name = $request->input('name');
        $manager = Manager::where('name', 'like','%'.$name.'%')->paginate(30);

        return $manager;
    }
    //destroy manager by id
    public function destroy(Request $request,$manager){
        try {
            $success_state = Manager::destroy($manager);
        } catch (QueryException $error) {
            return response(["message"=>$error->errorInfo[0],"code"=>$error->errorInfo[1]],500);
        }

        if ($success_state) {
            return response(["message"=>"record succesfully deleted","code"=>$success_state],200);
        }else{
            return response(["message"=>"error while deleting record","code"=>$success_state],500);
        }
    }

    public function edit(Request $request,$managerId){
        $manager = Manager::find($managerId,['name','id','user_id','station_name','fuel_station_id']);

        if(is_null($manager)){
            return response(['message'=>'no station with given id'],404);
        }else{
            return response($manager,200);
        }
    }

    public function update(Request $request,$managerId){
        $validation=Validator::make($request->all(),[
            'user_id'=>'required|nullable',
            'name'=>'string|nullable',
            'station_name'=>'string|nullable',
            'fuel_station_id'=>'numeric|nullable',
        ]);
        if($validation->fails()){
            return response(['message'=>'values not satisfies the needs'],400);
        }

        $manager = Manager::find($managerId);

        $manager->name = $request->input('name');
        $manager->lat=$request->input('user_id');
        $manager->lon=$request->input('station_name');
        $manager->address=$request->input('fuel_station_id');

        if($manager->save()){
            return response(['message'=>'record successfuly updated'],200);
        }else{
            return response(['message'=>'unable to update record'],400);
        }

    }

    public function change_petrol_visibility(Request $request){

        $validated = $request->validate([
            'manager_id' => 'required|numeric',
            'petrol_id' => 'required|numeric',
        ]);

        $managerId = $request->input('manager_id');
        $petrolId = $request->input('petrol_id');
        $fuel_station_manager = Manager::select('fuel_station_id')->where('user_id',$managerId)->firstOrFail();
        // $fuel_station_petrol = Petrol::select('fuel_station_id,'is_available')->where('id',$petrolId)->firstOrFail();
        $fuel_station_petrol = Petrol::find($petrolId);

        if($fuel_station_manager->fuel_station_id===$fuel_station_petrol->fuel_station_id){
            if($fuel_station_petrol->is_available == 1){
                $fuel_station_petrol->is_available = 0;
            }else{
                $fuel_station_petrol->is_available = 1;
            }
            if($fuel_station_petrol->save()){
                return response(['message'=>'record successfuly updated'],200);
            }else{
                return response(['message'=>'unable to update record'],400);
            }
        }
        else{
            return response(['message'=>'unable to update record'],419);
        }
    }

    public function changeStationVisibility(Request $request){

        $validated = $request->validate([
            'manager_id' => 'required|numeric',
            'station_id' => 'required|numeric',
        ]);

        $managerId = $request->input('manager_id');
        $stationId = $request->input('station_id');
        $fuel_station_manager = Manager::select('fuel_station_id')->where('user_id',$managerId)->firstOrFail();
        // $fuel_station_petrol = Petrol::select('fuel_station_id,'is_available')->where('id',$petrolId)->firstOrFail();
        $fuel_station = FuelStation::find($stationId);

        if($fuel_station_manager->fuel_station_id==$fuel_station->id){
            if($fuel_station->status == 1){
                $fuel_station->status = 0;
            }else{
                $fuel_station->status = 1;
            }
            if($fuel_station->save()){
                return response(['message'=>'record successfuly updated'],200);
            }else{
                return response(['message'=>'unable to update record'],400);
            }
        }
        else{
            return response(['message'=>'unable to update record','ID'=>$fuel_station->id,'MANAGER'=>$fuel_station_manager->fuel_station_id],419);
        }
    }


    public function manager_binded(Request $request,$managerId){

        $fuel_station = Manager::select('fuel_station_id')->where('user_id',$managerId)->firstOrFail();

        $petrols = FuelStation::with('petrols')->find($fuel_station->fuel_station_id);

        return $petrols;
    }
    public function manager_single_petrol(Request $request,$managerId,$petrolId){

        $manager = Manager::select('fuel_station_id')->where('user_id',$managerId)->firstOrFail();

        $petrol = Petrol::find($petrolId);

        if($manager->fuel_station_id===$petrol->fuel_station_id){
            return $petrol;
        }else{
            return response(['message'=>"access denied",419]);
        }

    }
    public function change_price(Request $request,$managerId,$petrolId){

        $request->validate([
            'price'=>"required|numeric"
        ]);

        $manager = Manager::select('fuel_station_id')->where('user_id',$managerId)->firstOrFail();

        $petrol = Petrol::find($petrolId);

        if($manager->fuel_station_id===$petrol->fuel_station_id){
            $petrol->price = $request->input('price');
            if($petrol->save()){
                return response(['message'=>'succces'],200);
            }
            return response(['message'=>"cannot update record",500]);
        }else{
            return response(['message'=>"access denied",419]);
        }
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

        $petrols = Manager::select(['id','user_id','name'])->where('fuel_station_id',$stationId)->get();

        if($petrols->isEmpty()){
            return response(['message'=>'no records with given id'],404);
        }else{
            return $petrols;
        }

    }

    public function bind_manager(Request $request){

        $validated = Validator::make($request->all(),[
            'stationId'=>'required',
            'user_id'=>'required'
        ]);

        if($validated->fails()){
            return response(['message'=>'please check input values'],409);
        }

        $user_id = $request->input('user_id');
        $manager = Manager::where('user_id',$user_id)->firstOrFail();
        // $manager = Manager::find(['user_id'=>$user_id]);
        // if(is_null($manager)||$manager->some()){
        //     return response(['message'=>'Manager with this user_id does not exist'],409);
        // }

        if($manager->fuel_station_id==0||is_null($manager->fuel_station_id)){
            $station_id=$request->input('stationId');
            $station=FuelStation::find($station_id,['name']);
            $manager->fuel_station_id = $station_id;
            $manager->station_name = $station->name;

            if($manager->save()){
                return response(['message'=>'Manager binded to Station'],200);
            }else{
                return response(['message'=>'Unable to save data'],500);
            }
        }else{
            return response(['message'=>'Manager already binded to other station'],409);
        }
    }

    public function changePetrolStandart(Request $request){
        $request->validate([
            'petrol_id'=>"required|numeric",
            'manager_id'=>"required|numeric",
            'supplier'=>"required|string"
        ]);
        $petrol_id = $request->input('petrol_id');
        $manager_id = $request->input('manager_id');
        $supplier = $request->input('supplier');
        $petrol = Petrol::find($petrol_id);
        $manager = Manager::select('fuel_station_id')->where('user_id',$manager_id)->firstOrFail();
        if($manager->fuel_station_id === $petrol->fuel_station_id){
            $petrol->supplier = $supplier;
            if($petrol->save()){
                return response(['message'=>'Petrol standart changed succesfully'],200);
            }else{
                return response(['message'=>'Unable to save data'],500);
            }
        }else{
            return response(['message'=>'Manager do not have access to this petrol'],409);
        }
    }

    public function unbind_manager(Request $request){
        $validated = Validator::make($request->all(),[
            'id'=>'required'
        ]);

        if($validated->fails()){
            return response(['message'=>'please check input values'],409);
        }

        $id = $request->input('id');
        $manager = Manager::find($id);
        $manager->fuel_station_id=0;

        if($manager->save()){
            return response(['message'=>'Manager binded to Station'],200);
        }else{
            return response(['message'=>'Unable to save data'],500);
        }
    }
}
