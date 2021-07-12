<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function startFollow(Request $request, $lang, $user): JsonResponse {
        $session=$request->get('user');
        $profile=User::where('username', '=', $user)->get();
        if($profile->count()===1){
            if($session->id!==$profile->first()->id) {
                if (!BlockController::verifyIsBlocked($session->id, $profile->first()->id)) {

                    $order = Follow::where('user', '=', $session->id)->where('follower', '=', $profile->first()->id)->get();
                    if ($order->count() === 0) {
                        $newOrder = new Follow();
                        $newOrder->user = $session->id;
                        $newOrder->follower = $profile->first()->id;
                        $newOrder->accepted = $profile->first()->private === 0;
                        $newOrder->save();
                    }
                    $data = array();
                    $data['data'] = array(__('user.follow'));
                    $data['error'] = array();
                    $data['code'] = 0;
                } else {
                    $data = array();
                    $data['data'] = array();
                    $data['error'] = array(__('user.notFound'));
                    $data['code'] = -1;
                }
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=array(__('user.notFound'));
                $data['code']=-1;
            }
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('user.notFound'));
            $data['code']=-1;
        }
        return response()->json($data);
    }

    public function stopFollow(Request $request, $lang, $user): JsonResponse {
        $session=$request->get('user');
        $profile=User::where('username', '=', $user)->get();
        if($profile->count()===1){
            Follow::where('user', '=', $session->id)->where('follower', '=', $profile->first()->id)->delete();
            $data=array();
            $data['data']=array(__("user.follow.stop"));
            $data['error']=array();
            $data['code']=0;
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('user.notFound'));
            $data['code']=-1;
        }
        return response()->json($data);

    }

    public static function verifyIsFollowing($userSession, $userSee): bool {
        return Follow::where('user', '=', $userSession)->where('follower', '=', $userSee)
            ->where('accepted', '=', true)
            ->get()->count()===1;
    }

    public static function verifyIsFollowMe($userSession, $userSee): bool {
        return Follow::where('follower', '=', $userSession)->where('user', '=', $userSee)
                ->where('accepted', '=', true)
                ->get()->count()===1;
    }

    public function listPending(Request $request) {
        $session=$request->get('user');
        $data=array();
        $data['data']=Follow::where('follower', '=', $session->id)->where('accepted', '=', 0)->get();
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);
    }

    public function accept (Request $request, $lang, $order): JsonResponse {
        $session=$request->get('user');
        $order=Follow::where('id', '=', $order)->where('follower', '=', $session->id)->get();
        if($order->count()===1){
            $order=$order->first();
            $order->accepted=1;
            $order->save();
            $data=array();
            $data['data']=array(__('user.follow.accepted'));
            $data['error']=array();
            $data['code']=0;
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('user.follow.error'));
            $data['code']=-1;
        }
        return response()->json($data);
    }

    public function removeFollow(Request $request, $lang, $order):JsonResponse {
        $session=$request->get('user');
        $order=Follow::where('id', '=', $order)->where('follower', '=', $session->id)->get();
        if($order->count()===1){
            Follow::where('id', '=', $order)->where('follower', '=', $session->id)->delete();
            $data=array();
            $data['data']=array(__('user.follow.removed'));
            $data['error']=array();
            $data['code']=0;
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('user.follow.error'));
            $data['code']=-1;
        }
        return response()->json($data);
    }
}
