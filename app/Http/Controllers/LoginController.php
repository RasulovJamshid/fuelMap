<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return response(['message'=>'success'],200);
        }

        return response(['message'=>'records dont match'],404);
    }
}


//API-TOKEN LOGIN
// class LoginController extends Controller
// {
//     public function authenticate(Request $request)
//     {
//         $user=new User();
//         $attr = $request->validate([
//             'email' => 'required|string|email|',
//             'password' => 'required|string|min:6'
//         ]);

//         if (!Auth::attempt($attr)) {
//             return response('Credentials not match', 401);
//         }
//         $token=request()->user()->createToken('API Token')->plainTextToken;
//         return response(['message'=>'success','token'=>$token],200,);

//     }
// }
