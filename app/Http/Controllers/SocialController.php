<?php

namespace App\Http\Controllers;

use App\Models\ConfigSocial;
use App\Models\ConfigSocialUser;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function getSocialsConfig(Request $request) {
        $user=$request->get('user');
        $data = array();
        $data['data'] = array(
            "twitter"=>ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', ConfigSocial::where('name', '=', "Twitter")->first()->id)->first(),
            "instagram"=>ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', ConfigSocial::where('name', '=', "instagram")->first()->id)->first()
        );
        $data['error'] = array();
        $data['code'] = 0;
        return response()->json($data);

        #$social=ConfigSocialUser::where('user', '=', $user->id)->get();
    }

    public function saveSession(Request $request, $lang, $auth, $route) {
        $header = $auth;
        $header=base64_decode($header);
        $header=explode(':', $header);
        if(count($header)!==2){
            return response()->json(['data'=>__('error.401'), 'error'=>-1], 401);
        }
        $auth=AuthController::authenticationUser($header[1]);
        if($auth['isAuth']){

                /*response()->cookie('device', $header[0], 60);
                response()->cookie('user', $header[1], 60);*/
                #->withCookie(Cookie::make('device', $header[0], 60))->withCookie(Cookie::make('user', $header[1], 60));
                return response()->redirectToRoute(strtolower($route), ["locale"=>$lang])->withCookies([cookie('device', $header[0], 120, '/'), cookie('user', $header[1], 120, '/')]);

        }else{
            return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
        }
    }
}
