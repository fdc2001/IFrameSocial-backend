<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function blockUser(Request $request, $lang, $userID): JsonResponse {
        $userInfo=User::where('id', '=', $userID)->get();
        $user=$request->get('user');
        if($userID!==$user->id){
            if(Block::where('user', '=', $user->id)->where('block', '=', $userID)->get()->count()===0){
                if($userInfo->count()===1){
                    Follow::whereOr(function ($q) use ($user, $userID) {
                        $q->where('user', '=', $userID);
                        $q->where('follower', '=', $user->id);
                    })->whereOr(function ($q) use ($user, $userID) {
                        $q->where('follower', '=', $userID);
                        $q->where('user', '=', $user->id);
                    })->delete();
                    $blockRegist=new Block();
                    $blockRegist->user=$user->id;
                    $blockRegist->block=$userID;
                    $blockRegist->save();

                    $data=array();
                    $data['data']=__('block.success', ['user'=>$userInfo->first()->name]);
                    $data['error']=array();
                    $data['code']=0;
                }else{
                    $data=array();
                    $data['data']=array();
                    $data['error']=array(__('block.error.userNotFound'));
                    $data['code']=-1;
                }
            }else{
                $data=array();
                $data['data']=__('block.success', ['user'=>$userInfo->first()->name]);
                $data['error']=array();
                $data['code']=0;
            }
        }else{
            $data=array();
            $data['data']=__('block.success', ['user'=>$userInfo->first()->name]);
            $data['error']=array();
            $data['code']=0;
        }
        return response()->json($data);
    }

    public function unblock(Request $request, $lang, $userID): JsonResponse {
        $userInfo=User::where('id', '=', $userID)->get();
        $user=$request->get('user');
        if($userID!==$user->id){
            if($userInfo->count()===1) {
                Block::where('user', '=', $user->id)->where('block', '=', $userID)->delete();
                $data = array();
                $data['data'] = __('unblock.success', ['user' => $userInfo->first()->name]);
                $data['error'] = array();
                $data['code'] = 0;
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=array(__('block.error.userNotFound'));
                $data['code']=-1;
            }
        }else{
            $data=array();
            $data['data']=__('unblock.success', ['user'=>$userInfo->first()->name]);
            $data['error']=array();
            $data['code']=0;
        }
        return response()->json($data);
    }

    public static function verifyIsBlocked($userSession, $userSee): bool {
        $verify=Block::where(function ($q) use ($userSession, $userSee) {
            $q->where('user', '=', $userSee)->where('block', '=', $userSession);
        })->get();
        return $verify->count()===1;
    }
}
