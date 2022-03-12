<?php

namespace App\Http\Controllers;

use App\Models\FuelStation;
use App\Models\Petrol;
use Doctrine\DBAL\Query\QueryException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class FuelStationController extends Controller
{

    public function index(){
        $fuel_station=FuelStation::all();
        return $fuel_station;
    }

    public function store(Request $request){
        $validated=Validator::make($request->all(),[
            'name'=>'required|string',
            'lat'=>'required|numeric|max:180|min:-180',
            'lon'=>'required|numeric|max:180|min:-180',
            'address'=>'string|nullable',
            'img_url'=>'url|nullable',
            'status'=>'boolean|nullable'
        ]);

        if($validated->fails()){
            return response([
                "message"=>"cannot create new fuel station",
            ],400);
        }
        $fuel_station=new FuelStation([
            'name'=>$request->input('name'),
            'lat'=>$request->input('lat'),
            'lon'=>$request->input('lon'),
            'address'=>$request->input('address'),
            'img_url'=>$request->input('img_url',asset('logos/default.png')),
            'status'=>$request->input('status',0)
        ]);

        //checks proccess of storing to the database
        if($fuel_station->save()){
            return response(["message"=>"success"],200);
        }else{
            return response(["message"=>"cannot save data to the database"],500);
        }

    }

    //destroy fuel_station by id
    public function destroy(Request $request,$stationId){
        try {
            $success_state = FuelStation::destroy($stationId);
        } catch (QueryException $error) {
            return response(["message"=>$error->errorInfo[0],"code"=>$error->errorInfo[1]],500);
        }

        if ($success_state) {
            return response(["message"=>"record succesfully deleted","code"=>$success_state],200);
        }else{
            return response(["message"=>"error while deleting record","code"=>$success_state],500);
        }
    }

    public function edit(Request $request,$stationId){
        $station = FuelStation::find($stationId,['name','id','img_url','address','lon','lat']);

        if(is_null($station)){
            return response(['message'=>'no station with given id'],404);
        }else{
            return response($station,200);
        }
    }

    public function update(Request $request,$stationId){
        // $this->authorize('update');
        $validation=Validator::make($request->all(),[
            'name'=>'string|nullable',
            'lat'=>'numeric|max:180|min:-180|nullable',
            'lon'=>'numeric|max:180|min:-180|nullable',
            'address'=>'string|nullable',
            'img_url'=>'url|nullable'
        ]);
        if($validation->fails()){
            return response(['message'=>'values not satisfies the needs'],400);
        }

        $station = FuelStation::find($stationId);

        $station->name = $request->input('name');
        $station->lat=$request->input('lat');
        $station->lon=$request->input('lon');
        $station->address=$request->input('address');
        $station->img_url=$request->input('img_url',asset('logos/default.png'));


        // $update_status= $station->update([
        //     'name'=>$request->input('name'),
        //     'lat'=>$request->input('lat'),
        //     'lon'=>$request->input('lon'),
        //     'address'=>$request->input('address'),
        //     'img_url'=>$request->input('img_url',asset('logos/default.png'))
        // ]);

        if($station->save()){
            return response(['message'=>'record successfuly updated'],200);
        }else{
            return response(['message'=>'unable to update record'],400);
        }

    }
    //selects point which are located at the given area
    public function boundary(Request $request){
        $request->query();
        $validated=Validator::make($request->query(),[
            'n_lon'=>'required|numeric',
            'n_lat'=>'required|numeric',
            's_lon'=>'required|numeric',
            's_lat'=>'required|numeric',
            'type'=>'string|nullable'
        ]);

        if($validated->fails()){
            return response([
                "message"=>"please check input values",
            ],400);
        }

        $values=$request->query();
        //stores location data to x,y axis
        $x=array($values['n_lon'],$values['s_lon']);
        $y=array($values['n_lat'],$values['s_lat']);
        $type = $request->query('type','ALL');

        //makes Polygon string template
        $polygonPoints=sprintf('POLYGON((%s %s,%s %s,%s %s,%s %s,%s %s))',$x[0],$y[0],$x[1],$y[0],$x[1],$y[1],$x[0],$y[1],$x[0],$y[0]);
        //selects points which are inside given polygon
        if($values['type']=="ALL"){
            $rawData=FuelStation::select("img_url",'lon','lat','id')->with(['petrols' => function ($query) {
                $query->select("type");
            }])->whereRaw("ST_Within(POINT(lon, lat),ST_GeomFromText('".$polygonPoints."'))")
            ->get();
            return $rawData;;
        }else{
            $rawData=FuelStation::select("img_url",'lon','lat','id')->
            whereHas('petrols',function($query) use($type) {
                $query->where('type', $type)->where('is_available',1)->where('price','>',0);
             })->whereRaw("ST_Within(POINT(lon, lat),ST_GeomFromText('".$polygonPoints."'))")
            ->get();
            return $rawData;;
        }

    }

    public function boundary_pagination(Request $request){
        $request->query();
        $validated=Validator::make($request->query(),[
            'n_lon'=>'required|numeric',
            'n_lat'=>'required|numeric',
            's_lon'=>'required|numeric',
            's_lat'=>'required|numeric',
            'type'=>'string|nullable'
        ]);

        if($validated->fails()){
            return response([
                "message"=>"please check input values",
            ],400);
        }
        $type = $request->query('type','ALL');
        $values=$request->query();
        //stores location data to x,y axis
        $x=array($values['n_lon'],$values['s_lon']);
        $y=array($values['n_lat'],$values['s_lat']);
        //makes Polygon string template
        $polygonPoints=sprintf('POLYGON((%s %s,%s %s,%s %s,%s %s,%s %s))',$x[0],$y[0],$x[1],$y[0],$x[1],$y[1],$x[0],$y[1],$x[0],$y[0]);

        if($type=="ALL"){
            $rawData=FuelStation::with(['petrols' => function ($query) {
                $query->where('is_available', 1)->where('price','>',0);
            }])
            ->whereRaw("ST_Within(POINT(lon, lat),ST_GeomFromText('".$polygonPoints."'))")
            ->paginate(15);
            return $rawData;
        }
        else{
            $rawData=FuelStation::
            whereHas('petrols',function($query) use($type) {
                $query->where('type', $type)->where('is_available',1)->where('price','>',0);
             })
            ->with(['petrols' => function ($query) {
                $query->where('is_available', 1)->where('is_available',1)->where('price','>',0);;
            }])
            ->whereRaw("ST_Within(POINT(lon, lat),ST_GeomFromText('".$polygonPoints."'))")
            ->paginate(15);
            return $rawData;;
        }
    }

    public function chunk(Request $request){
        $managers = FuelStation::paginate(30);

        return response($managers,200);
    }


    public function search(Request $request){


        Validator::make($request->query(),[
            'name'=>'string|nullable',
            'type'=>'string|nullable',
            'sortOrder'=>'string|nullable',
        ]);
        $search_name = $request->query('name');
        $sortOrder = $request->query('order','ASC');
        $type = $request->query('type');

        if(!is_null($type)&&!is_null($search_name)){

            $station = FuelStation::select('fuel_stations.*','petrols.type')
            ->where("name",'like',"%".$search_name."%")
            ->join('petrols', function ($join) use($type){
                $join->on('petrols.fuel_station_id', '=', 'fuel_stations.id')
                     ->where('petrols.type', $type)
                     ->where('petrols.is_available',1)
                     ;
            })
            ->orderBy('petrols.price',$sortOrder)
            ->with(['petrols' => function ($q){
                $q->where('is_available', 1)->where('price','>',0);
            }])
            ->paginate(15);
            //add limit
            return $station;
        }elseif(is_null($search_name)&&!is_null($type)){

            $station = FuelStation::select('fuel_stations.*','petrols.type')
            ->join('petrols', function ($join) use($type){
                $join->on('petrols.fuel_station_id', '=', 'fuel_stations.id')
                     ->where('petrols.type', $type)
                     ->where('petrols.is_available',1)
                     ;
            })
            ->orderBy('petrols.price',$sortOrder)
            ->with(['petrols' => function ($q) {
                $q->where('is_available', 1)->where('price','>',0);
            }])
            ->paginate(15);
            ;

            return $station;
        }else{
            return response(['message'=>'no results related to search'],404);
        }

    }
    public function find_by_name(Request $request){
        validator($request->all(),[
            'name'=>'required'
        ]);

        $name = $request->input('name');
        $manager = FuelStation::where('name', 'like','%'.$name.'%')->paginate(30);

        return $manager;
    }
    //change visibility of station
    public function change_visibility($stationId,$status){
        $station = FuelStation::find($stationId);

        if($status>0){
            $station->status = 1;
        }else{
            $station->status = 0;
        }

        if($station->save()){
            return response(['message'=>'record successfuly updated'],200);
        }else{
            return response(['message'=>'unable to update record'],400);
        }
    }
    //returns single station with fuels
    public function single_station(Request $request,$stationId){
        $request->merge(['id'=>$stationId]);
        $validated = Validator::make($request->all(),[
            'id'=>'required|numeric'
        ]);

        if($validated->fails()){
            return response([
                "message"=>"please check input values",
            ],400);
        }
        $result=FuelStation::where('id',$stationId)->with(['petrols'=>function($query){
            $query->orderBy('price')->where('is_available',1)->where('price','>',0);;
        }]);
        if($result->count()>0){
            //check
            return $result->first();
        }else{
            return response([
                "message"=>"no fuel station with given id"],
                404
            );
        }
    }

    public function upload_logo(Request $request){
        if($request->hasFile('logo')){
            //^checks wether file exists
            $logo = $request->file('logo');
            $mime = $logo->getMimeType();
            if($mime=='image/jpeg'||$mime=="image/png"){
                //^checks file format
                $check_dimensions = Validator::make($request->all(),[
                    'logo'=>'required|dimensions:width=128,height=128,ratio=1'
                ]);
                //^checks image dimensions 128x128 1:1
                if(!$check_dimensions->fails()){
                    try {
                        $path = $logo->store('logos');
                    } catch (Exception $th) {
                        return response(['message'=>$th->getMessage()],$th->getCode());
                    }
                    return response(['message'=>'success','path'=>asset($path),'code'=>200],200);
                }else{
                    return response(['message'=>'input file dimensions needs to be 128 by 128',],422);
                    //runs if file dimensions not equals to 128 by 128
                }

            }else{
                return response(['message'=>'input file is not valid',],422);
                //^runs if file format not satifies the needs
            }
        }else{
            return response(['message'=>'request does not containe logo file'],403);
            //^runs if file doesn't exists
        }
    }

    public function uz_nefti(Request $request){
        $validated = $request->validate(
        [   'type'=>'required|string',
            'price'=>'required|numeric|min:0'
        ]);


        // if($validated->fails()){
        //     return response([
        //         "message"=>"please check input values",
        //     ],400);
        // }

        $type = $request->input("type");
        $price = $request->input("price");

        $petrols = Petrol::where('type',$type)
            ->with('fuelStation')
            ->whereHas('fuelStation',function($query){
                $query->where('name',"UZBEKNEFTEGAZ");
            })
            ->update(['price'=>$price]);

        if($petrols>0){
            return response([
                "message"=>"updated"
            ],200);
        }else{
            return response([
               "message"=>"failed to update"
            ],400);

        }
    }
}
