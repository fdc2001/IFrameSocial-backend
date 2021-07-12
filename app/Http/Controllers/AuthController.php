<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public static function authenticationUser($authToken) {
        $user = User::where('remember_token', '=', $authToken)->get();
        if($user->count()===0){
            return ["isAuth"=>false, "data"=>[]];
        }else{
            return ["isAuth"=>true, "data"=>$user->first()];
        }
    }
}
