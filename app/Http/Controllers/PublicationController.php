<?php

namespace App\Http\Controllers;

use App\Helpers\VideoStream;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PublicationController extends Controller
{
    public function feed(Request $request) {
        $userID = $request->get('user')->id;
        $user=User::with('followings')->where('id', '=', $userID)->first();
        $ids = array($userID);
        foreach ($user->followings as $row){
            array_push($ids, $row->id);
        }
        $data=Publication::whereIn('user', $ids)->orderBy('publishDate', 'DESC')->paginate();
        return response()->json($data);
    }

    public function myPublications(Request $request) {
        $user= $request->get('user');
        $data= Publication::where('user', '=', $user->id)->where('status', '=', 1)->orderBy('publishDate', 'DESC')->paginate();
        return response()->json($data);
    }

    public function getMedia(Request $request, $lang, $fileID, $auth=null) {
        $publication=Publication::where('id', '=', $fileID)->get();

        if($publication->count()===1){
            if($auth!==null){
                $header = $auth;
                $header=base64_decode($header);
                $header=explode(':', $header);
                if(count($header)!==2){
                    return response()->json(['data'=>__('error.401'), 'error'=>-1], 401);
                }
                $auth=AuthController::authenticationUser($header[1]);
                if($auth['isAuth']){
                            if($auth['data']->username!==$publication->first()->user){
                                $follow = FollowController::verifyIsFollowing($auth['data']->id, User::where('username', '=', $publication->first()->user)->first()->id);
                            }else{
                                $follow=true;
                            }
                }else{
                    return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                }
            }else{
                $follow=false;
            }
            $publication=$publication->first();
            if(User::where('username', '=', $publication->user)->first()->private && !$follow){
                response('', 404);
            }else{
                if($publication->type==="VIDEO"){
                    $stream = new VideoStream(storage_path('app/publications/videos/'.$publication->content));
                    $stream->start();
                }else if ($publication->type==="IMAGE"){
                    if(Storage::disk()->exists('publications/images/'.$publication->content))
                        return Storage::response('publications/images/'.$publication->content);
                    else
                        response('', 404);
                }
            }
        }
    }

    public function getMediaThumb(Request $request, $lang, $fileID, $auth=null) {
        $publication=Publication::where('id', '=', $fileID)->get();

        if($publication->count()===1){
            if($auth!==null){
                $header = $auth;
                $header=base64_decode($header);
                $header=explode(':', $header);
                if(count($header)!==2){
                    return response()->json(['data'=>__('error.401'), 'error'=>-1], 401);
                }
                $auth=AuthController::authenticationUser($header[1]);
                if($auth['isAuth']){
                    if($auth['data']->username!==$publication->first()->user){
                        $follow = FollowController::verifyIsFollowing($auth['data']->id, User::where('username', '=', $publication->first()->user)->first()->id);
                    }else{
                        $follow=true;
                    }
                }else{
                    return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                }
            }else{
                $follow=false;
            }
            $publication=$publication->first();
            if(User::where('username', '=', $publication->user)->first()->private && !$follow){
                response('', 404);
            }else{
                if($publication->type==="VIDEO"){
                    if(Storage::disk()->exists('publications/videos/thumbnail/'.$publication->thumbnail))
                        return Storage::response('publications/videos/thumbnail/'.$publication->thumbnail);
                    else
                        response('', 404);
                }else if ($publication->type==="IMAGE"){
                    if(Storage::disk()->exists('publications/images/thumbnail/'.$publication->content))
                        return Storage::response('publications/images/thumbnail/'.$publication->content);
                    else
                        response('', 404);
                }

            }
        }
    }

    public function getThumbnail(Request $request, $lang, $fileName, $auth=null) {
        $publication=Publication::where('thumbnail', '=', $fileName)->get();
        if($auth!==null){
            $header = $auth;
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
                        $haveAccess = true;
                        if($auth['data']->username!==$publication->first()->user){
                            $follow = FollowController::verifyIsFollowing($auth['data']->id, User::where('username', '=', $publication->first()->user)->first()->id);
                        }else{
                            $follow=true;
                        }
                    }else{
                        return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                    }
                }else{
                    return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
                }
            }else{
                return response()->json(['data'=>__('error.sessionDie'), 'error'=>-2], 401);
            }
        }else{
            $follow=false;
        }

        if($publication->count()===1){
            $publication=$publication->first();
            if(User::where('username', '=', $publication->user)->first()->private && !$follow){
                response('', 404);
            }else{
                if(Storage::disk()->exists('publications/videos/thumbnail/'.$publication->thumbnail))
                    return Storage::response('publications/videos/thumbnail/'.$publication->thumbnail);
                else
                    response('', 404);
            }
        }
    }

}
