<?php

namespace App\Http\Controllers;

use App\Jobs\FirstSync;
use App\Models\ConfigSocial;
use App\Models\ConfigSocialUser;
use App\Models\Publication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class InstagramController extends Controller
{
    public function register(Request $request) {
        $user = $request->get('user');
        $data=$request->get("data");
        $socialConfig=ConfigSocial::where('name', '=', 'Instagram')->first();
        if(isset($data['token'])){
            $response =Http::asForm()->post('https://api.instagram.com/oauth/access_token',[
                'client_id'=>$socialConfig->appID,
                'client_secret'=>$socialConfig->appSecrete,
                'grant_type'=>'authorization_code',
                'redirect_uri'=>'https://localhost:3333/',
                'code'=>$data['token'],
            ]);
            if($response->successful()){
                $res=$response->json();
                if($res['access_token']){
                    if(ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', $socialConfig->id)->get()->count()===0){
                        $config=new ConfigSocialUser();
                    }else{
                        $config=ConfigSocialUser::where('user', '=', $user->id)->where('socialID', '=', $socialConfig->id)->get()->first();
                    }
                    $config->user=$user->id;
                    $config->socialID=$socialConfig->id;
                    $config->userID=$res['user_id'];
                    $config->automatePublications=$data['automatePublications'];
                    $config->automateRenewAccess=$data['automateRenewAccess'];
                    $response =Http::get('https://graph.instagram.com/access_token',[
                        'client_secret'=>$socialConfig->appSecrete,
                        'grant_type'=>'ig_exchange_token',
                        'access_token'=>$res['access_token'],
                    ]);
                    if($response->successful()){
                        $res=$response->json();
                        $config->accessToken=$res['access_token'];
                        $config->expireDate=Carbon::now()->add('seconds',$res['expires_in']);
                        $config->automatePublications=true;
                        $config->automateRenewAccess=true;
                        $config->save();
                        $job = new FirstSync($config);
                        $this->dispatch($job);

                        $data=array();
                        $data['data']=array(__('sync.instagram.success'));
                        $data['error']=array();
                        $data['code']=0;
                    }else{
                        $data=array();
                        $data['data']=array();
                        $data['error']=array(__('sync.instagram.error.invalid'));
                        $data['code']=-4;
                    }
                }else{
                    $data=array();
                    $data['data']=array();
                    $data['error']=array(__('sync.instagram.error.invalid'));
                    $data['code']=-3;
                }
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=array(__('sync.instagram.error.invalid'));
                $data['code']=-2;
            }
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('sync.instagram.error.invalid'));
            $data['code']=-1;
        }
        return response()->json($data);
    }

    public static function sendRequestGetMedia($url, $configUser){
        return Http::get($url, [
            'fields'=> 'id,media_type,media_url,username,timestamp,thumbnail_url,caption',
            'access_token'=> $configUser->accessToken
        ]);
    }

    public static function saveMedia($media, $configUser, $configuration, $pubID=null){
        if(Publication::where('user', '=', $configUser->user)->where('socialID', '=', $configuration->id)->where('externalID', '=', $media['id'])->get()->count()===0) {
            $publication = new Publication();
            $publication->externalID = $media['id'];
            $publication->user = $configUser->user;
            $publication->socialID = $configuration->id;
            $publication->publishDate = $media['timestamp'];
            $publication->publishDate = Carbon::parse($media['timestamp'])->toDateTimeString();;
            $publication->type = $media['media_type'];
            $publication->status=$configUser->automatePublications;
            if (isset($media['caption']))
                $publication->description = $media['caption'];

            if ($pubID !== null)
                $publication->pubID = $pubID;

            if ($media['media_type'] === "VIDEO") {
                $info = pathinfo($media['thumbnail_url']);
                $name = explode('?', $info['filename'])[0];
                Storage::put('publications/videos/thumbnail/' . $name, file_get_contents($media['thumbnail_url']));
                $publication->thumbnail = $name;

                $content = file_get_contents($media['media_url']);
                $info = pathinfo($media['media_url']);
                $name = explode('?', $info['filename'])[0];
                Storage::put('publications/videos/' . $name, $content);
                $publication->content = $name;
                $publication->save();
            } else if ($media['media_type'] === "CAROUSEL_ALBUM") {
                $url = 'https://graph.instagram.com/' . $media['id'] . '/children';
                do {
                    $response = Http::get($url, ['fields' => 'id,media_type,media_url,username,timestamp,thumbnail_url,caption', 'access_token' => $configUser->accessToken]);
                    if ($response->successful()) {
                        $publication->save();
                        $data = $response->json();
                        foreach ($data as $row) {
                            self::saveMedia($row, $configUser, $configuration, $publication->id);
                        }
                        if (isset($res['paging']['next'])) {
                            $url = $res['paging']['next'];
                        }
                    }

                } while (isset($res['paging']['next']));

            } else {
                $info = pathinfo($media['media_url']);
                $name = explode('?', $info['filename'])[0];
                Storage::put('publications/images/' . $name, file_get_contents($media['media_url']));
                $publication->content = $name;
                $publication->save();
            }
            return true;
        }else{
            return false;
        }
    }

    public static function sync($configUser, $configuration) {
        $url='https://graph.instagram.com/me/media';
        do{
            $response=self::sendRequestGetMedia($url, $configUser);
            if($response->successful()){
                $res=$response->json();
                foreach ($res['data'] as $row){
                    $status = self::saveMedia($row, $configUser, $configuration);
                }
                if($status) {
                    if (isset($res['paging']['next'])) {
                        $url = $res['paging']['next'];
                    }
                }
            }
        }while(isset($res['paging']['next']));
    }

    public function renewAccess(){
        $configs = ConfigSocialUser::where('socialID', '=', 1)->where('expireDate', '<=', Carbon::now()->add('days',10)->toDateTime())->get();
        foreach ($configs as $row){
            $response =Http::get('https://graph.instagram.com/refresh_access_token',[
                'grant_type'=>'ig_refresh_token',
                'access_token'=>$row->accessToken,
            ]);
            echo $response->successful();
            if($response->successful()) {
                $res = $response->json();
                $row->accessToken = $res['access_token'];
                $row->expireDate = Carbon::now()->add('seconds', $res['expires_in']);
                $row->automatePublications = true;
                $row->automateRenewAccess = true;
                $row->save();
            }
        }
    }
}
