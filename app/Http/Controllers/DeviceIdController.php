<?php

namespace App\Http\Controllers;

use App\Helpers\DeviceInfo;
use App\Models\DeviceId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeviceIdController extends Controller
{
    public static function verifyDevice($device) {
        #dd($device);
        if ($device->browser === DeviceInfo::get_browsers() && $device->system === DeviceInfo::get_os() && $device->device === DeviceInfo::get_device()) {
            $device->touch();
            return true;
        } else {
            return false;
        }

    }

    public static function getDevice($token) {
        $device = DeviceId::where('token', '=', $token)->where('locked', '=', 0)->get();
        if($device->count()===0){
            return ['data'=>false, 'auth'=>false];
        }else{
            return ['data'=>$device->first(), 'auth'=>true];
        }
    }

    public static function getTokenDevice($userID) {
        $device=DeviceId::where('user', '=', $userID)
            ->where('browser', '=', DeviceInfo::get_browsers())
            ->where('system', '=', DeviceInfo::get_os())
            ->where('device', '=', DeviceInfo::get_device())
            ->where('locked', '=', 0)
            ->get();
        if($device->count()===0){
            $registry = new DeviceId();
            $registry->user=$userID;
            $registry->browser=DeviceInfo::get_browsers();
            $registry->system=DeviceInfo::get_os();
            $registry->device=DeviceInfo::get_device();
            $registry->ip=DeviceInfo::get_ip();
            $registry->token=Str::uuid();
            $registry->save();
            return $registry->token;
        }else{
            return $device->first()->token;
        }
    }

    public function getLoginDevices(Request $request) {
        $user = $request->get('user');
        $data=array();
        $data['data']=DeviceId::where('user', '=', $user->id)->orderBy('updated_at', 'desc')->get();
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);
    }

    public function changeDeviceStatus(Request $request, $lang, $deviceID) {
        $data=$request->get('data');
        $user=$request->get('user');
        $validator = Validator::make(
            $data,
            [
                'password' => 'required|min:8',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
        }else{
            if(Hash::check($data['password'], $user->password)){
                $device=DeviceId::where('user', '=', $user->id)->where('id', '=', $deviceID)->get();
                if($device->count()!==0){
                    $device=$device->first();
                    $device->locked=!$device->locked;
                    $device->save();
                    $data=array();
                    if($device->locked){
                        $data['data']=array(__('device.disable.success'));
                    }else{
                        $data['data']=array(__('device.enabled.success'));
                    }
                    $data['error']=array();
                    $data['code']=0;
                }else{
                    $data=array();
                    $data['data']=array();
                    $data['error']=array(__('device.disable.notFound'));
                    $data['code']=-1;
                }
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=array(__('login.error.passwordWrong'));
                $data['code']=-1;
            }
        }
        return response()->json($data);
    }
}
