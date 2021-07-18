<?php

namespace App\Http\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Jobs\FirstSync;
use App\Models\ConfigSocial;
use App\Models\ConfigSocialUser;
use App\Models\Publication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TheSeer\Tokenizer\Exception;

class TwitterController extends Controller
{
    public function redirectToLogin(Request $request) {
        $config = ConfigSocial::where('name', '=', 'Twitter')->first();
        if ($config!==null){
            $conn = new TwitterOAuth($config->appID, $config->appSecret);

            if(strpos($request->root(), 'https')===false){
                $url=$request->root()."/api/"."connect"."/v1"."/sync/twitter";
            }else{
                $url=str_replace('http', 'https', $request->root()."/api/"."connect"."/v1")."/sync/twitter";
            }
            #dd($url);

            $requestToken = $conn->oauth('oauth/request_token', array('oauth_callback'=>$url));
            return  redirect($conn->url('oauth/authorize', array('oauth_token'=>$requestToken['oauth_token'])));
        }
    }

    public function register(Request $request) {
        $auth=AuthController::authenticationUser($request->cookie('user'));
        $user=$auth['data'];
        if($auth['isAuth']){
            $configSocial = ConfigSocial::where('name', '=', 'Twitter')->first();
            if ($configSocial!==null){
                    $oauth_token=$request->input('oauth_token');
                    $oauth_verifier=$request->input('oauth_verifier');
                    try{
                        $conn = new TwitterOAuth($configSocial->appID, $configSocial->appSecret, $oauth_token, $oauth_verifier);
                        $access = $conn->oauth('oauth/access_token', array('oauth_verifier'=>$oauth_verifier));
                        if(ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', $configSocial->id)->get()->count()===0){
                            $config=new ConfigSocialUser();
                        }else{
                            $config=ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', $configSocial->id)->get()->first();
                        }
                        $config->user=$user->id;
                        $config->socialID=$configSocial->id;
                        $config->accessToken=$access['oauth_token'];
                        $config->tokenSecret=$access['oauth_token_secret'];
                        $config->username=$access['screen_name'];
                        $config->userID=$access['user_id'];
                        $config->automatePublications=true;
                        $config->automateRenewAccess=true;
                        $config->save();
                        $sync = new FirstSync($config);
                        $this->dispatch($sync);

                        $data=array();
                        $data['data']=array(__('sync.twitter.success'));
                        $data['error']=array();
                        $data['code']=0;
                    }catch (\Exception $e){
                        dd($e);
                        $data=array();
                        $data['data']=array();
                        $data['error']=array(__('sync.twitter.error.invalid'));
                        $data['code']=-2;
                    }
                    return response()->json($data);

                }
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('sync.twitter.error.invalid'));
            $data['code']=-2;
            return response()->json($data);
        }
    }

    public static function sync($configUser, $configuration) {
        $conn = new TwitterOAuth($configuration->appID, $configuration->appSecret, $configUser->accessToken, $configUser->tokenSecret);
        $data = $conn->get("statuses/user_timeline",array('count' => 50, 'exclude_replies' => true, 'screen_name' => $configUser->username, 'include_rts'=>false));
        //$data = $conn->get("account/verify_credentials");
        foreach ($data as $row){
            if(Publication::where('user', '=', $configUser->user)->where('socialID', '=', $configuration->id)->where('externalID', '=', $row->id)->get()->count()===0) {
                $publication = new Publication();
                $publication->externalID = $row->id;
                $publication->user = $configUser->user;
                $publication->socialID = $configuration->id;
                $publication->publishDate = Carbon::parse($row->created_at)->toDateTimeString();;
                $publication->type = "Text";
                $publication->status = $configUser->automatePublications;
                $publication->content = $row->text;
                $publication->save();
            }
        }
    }

    public function test() {
        $sync = new FirstSync(ConfigSocialUser::where('id', '=', 3)->get()->first());
        $this->dispatch($sync);

    }
}
