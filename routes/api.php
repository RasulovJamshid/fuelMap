<?php
namespace App\Models;

use App\Http\Controllers\FuelStationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\PetrolController;
use App\Http\Controllers\PetrolDefaultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("login",[LoginController::class,'authenticate']);

// api/petroldefaults PetrolDefaults start

Route::resource('petroldefaults',PetrolDefaultController::class)
        ->only(['index','store', 'index','destroy','update','edit'])
        ->middleware('auth:sanctum');
// Route::prefix('/petrol')->group(function(){
//     Route::resource('petrol',PetrolController::class)
//     ->only(['store', 'index','destroy']);
// });
// api/petroldefaults PePetrolDefaults end

// api/petrols Petrol start
Route::prefix('petrols')->middleware('auth:sanctum')->group(function(){
    Route::get('parent/{stationId}',[PetrolController::class,'station_binded'])
        ->whereNumber('stationId')->name('binded_petrols');
    Route::apiResource('/',PetrolController::class)
        ->only(['store']);
});
// api/petrols Petrol end


// Route::get('station/chunk',[FuelStationController::class,'chunk']);
//api/fuel_station FuelStation start
Route::prefix('station')->middleware('auth:sanctum')->group(function(){


    Route::get("/{stationId}/edit",[FuelStationController::class,'edit']);

    Route::put("/{stationId}",[FuelStationController::class,'update'])->name('fuelStation.update');

    Route::get('/{stationId}/status/{status}',[FuelStationController::class,'change_visibility'])
        ->whereNumber(['status','stationId']);

    Route::resource('',FuelStationController::class)
        ->only(['store', 'index']);
        // ->middleware('permission');
    Route::get('chunk',[FuelStationController::class,'chunk']);
    Route::delete('/{station}', [FuelStationController::class, 'destroy']);

    Route::get('/boundary',[FuelStationController::class,'boundary']);
    ROute::get('find',[FuelStationController::class,'find_by_name']);
    Route::get('/single/{id}',[FuelStationController::class,'single_station'])
        ->whereNumber('id');
    Route::post('logo/create',[FuelStationController::class,'upload_logo']);
});
//api/fuel_station FuelStation end

//api Manager start

Route::prefix('managers')->group(function(){
    Route::resource('', ManagerController::class)
        ->only(['update','edit'])
        ->middleware('auth:sanctum');
    Route::delete('/{id}',[ManagerController::class,'destroy'])
        ->middleware('auth:sanctum');;
    Route::get('parent/{stationId}',[ManagerController::class,'station_binded'])
        ->middleware('auth:sanctum');
    Route::post('bind',[ManagerController::class,'bind_manager'])
        ->middleware('auth:sanctum');
    Route::post('unbind',[ManagerController::class,'unbind_manager'])
        ->middleware('auth:sanctum');
    Route::get('find',[ManagerController::class,'find_by_name'])
        ->middleware('auth:sanctum');
    Route::post('/',[ManagerController::class,'store'])
        ->middleware('permission');
    Route::put('change/status',[ManagerController::class,'changeStationVisibility'])
            ->middleware('permission');
    Route::put('/petrol/visibility',[ManagerController::class,'change_petrol_visibility'])
        ->middleware('permission');
    Route::get('/{managerId}/petrol/{petrolId}',[ManagerController::class,'manager_single_petrol'])
        ->whereNumber(['managerId','petrolId'])
        ->middleware('permission');
    Route::put('/{managerId}/petrol/{petrolId}',[ManagerController::class,'change_price'])
        ->whereNumber(['managerId','petrolId'])
        ->middleware('permission');
    Route::put('/petrol/standart',[ManagerController::class,'changePetrolStandart'])
        ->middleware('permission');
    Route::get('/{managerId}',[ManagerController::class,'manager_binded'])
        ->whereNumber('managerId')
        ->middleware('permission');
    Route::get('/chunk',[ManagerController::class,'chunk'])
        ->middleware('auth:sanctum');
    Route::put('/uzbeknef',[FuelStationController::class,"uz_nefti"])
        ->middleware('permission');
    Route::get('/availablep',[PetrolDefaultController::class,'index'])
        ->middleware('permission');
});

//api Manager end



//api Mobile App start
//get single station
//get by search name
//get based on filter fuel types
Route::prefix('fuelstations')->middleware("permission")->group(function(){
    Route::get('/fuels',[PetrolDefaultController::class,'index']);//
    Route::get('/search',[FuelStationController::class,'search']);//
    Route::get('/boundary',[FuelStationController::class,'boundary']);//limit boundary min to max range
    Route::get('/boundary/chunk',[FuelStationController::class,'boundary_pagination']);//limit boundary min to max range
    // Route::get('/all',[FuelStationController::class,'index']);//remove
    Route::get('/{stationId}',[FuelStationController::class,'single_station']);//

});

//api Mobile App end
