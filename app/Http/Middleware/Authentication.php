<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceIdController;
use App\Models\DeviceId;
use Closure;
use Illuminate\Http\Request;

class Authentication
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $openRoutes=array(
            'account/login',
            'account/validate',
            'account/recovery',
            'account/new',
            'account/validateNewCode',
            'account/photo/{username}',
            'account/profile/{username}',
            'account/new/verify',
            'publication/media/{id}/{auth?}'
        );

        $route=explode('/', $request->route()->uri());
        unset($route[0],$route[1],$route[2]);
        $route= join('/', $route);
        if($request->header('authorization')===null) {
            if (in_array($route, $openRoutes)) {
                $request->request->add(['data' => $request->all()]);
                return $next($request);
            } else {
                return response()->json(['data' => __('error.401'), 'error' => -1], 401);
            }
        }else{
            $header = $request->header('authorization');
            $header=substr($header, 6);
            $header=base64_decode($header);
            $header=explode(':', $header);
            if(count($header)!==2){
                return response()->json(['data'=>__('error.401'), 'error'=>-1], 401);
            }
            $auth=AuthController::authenticationUser($header[1]);
            if($auth['isAuth']){
                $device = DeviceIdController::getDevice($header[0]);
                if($device['auth']){
                    if(DeviceIdController::verifyDevice($device['data'])){
                        $request->request->add(['data' => $request->all(), 'user'=>$auth['data'], 'deviceID'=>$device['data']->id]);
                        return $next($request);
                    }else{
                        return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                    }
                }else{
                    return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                }
            }else{
                return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
            }
        }
    }
}
