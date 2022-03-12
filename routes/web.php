<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// Route::get('/stations',function(){
//     return view('fuelStations');
// });
// Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Route::get('/app/install/{key?}',  array('as' => 'install', function($key = null)
// {
//     if($key == "fuelapi"){
//     try {
//       echo '<br>init migrate...';
//       Artisan::call('storage:link');
//       echo '<br>done with';
//     } catch (Exception $e) {
//       Response::make($e->getMessage(), 500);
//     }
//   }else{
//     App::abort(404);
//   }
// }

// ));


// Route::fallback(function() {
//     return view('welcome');
// });
