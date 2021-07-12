<?php

namespace App\Http\Controllers;

use App\Models\DeleteAccount;
use App\Models\Publication;
use App\Models\RecoveryPasswords;
use App\Models\User;
use App\Models\UsernameHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function createUser(Request $request): JsonResponse {
        $data=$request->get('data');
        $data=Arr::only($data, array('email', 'name', 'birthDate', 'password', 'passwordVerify', 'username'));
        $validator = Validator::make(
            $data,
            [
                'name' => 'required',
                'username' => 'required|unique:users|min:3',
                'password' => 'required|min:8',
                'email' => 'required|email|unique:users',
                'birthDate' => 'required|date|before:-13 years',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }
        if(isset($data['password']) && isset($data['passwordVerify'])) {
            if ($data['password'] === $data['passwordVerify']) {
                $user = new User();
                $user->name = $data['name'];
                $user->username = $data['username'];
                $user->setPasswordAttribute($data['password']);
                $user->lang = App::currentLocale();
                $user->email = $data['email'];
                $user->email_verified_requisite = now();
                $user->birthDate = Carbon::parse($data['birthDate'])->format('Y-m-d');
                $user->email_verified_code = rand(100000, 999999);
                $user->save();
                $history=new UsernameHistory();
                $history->userID=$user->id;
                $history->username=$user->username;
                $history->save();
                Mail::send('emails.validation', ['user' => $user], function ($m) use ($user) {
                    $m->to($user->email, $user->name)->subject(__('createAccount.email.subject'));
                });
                $data=array();
                $data['data']=__("createAccount.success");
                $data['error']=array();
                $data['code']=0;
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=__("createAccount.error.passwordDifferent");
                $data['code']=-2;
            }
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=__("createAccount.error.nullPasswords");
            $data['code']=-3;
        }
        return response()->json($data);
    }

    public function activateAccount(Request $request): JsonResponse {
        $data=$request->get('data');
        $validator = Validator::make(
            $data,
            [
                'code' => 'required|min:6|max:6',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }
        $user=User::where('email_verified_code', '=', $data['code'])->where('email_verified_requisite', '>=', Carbon::now()->subHour())->where('email_verified_at', '=',null)->get();
        if($user->count()===0){
            $data['data']=array();
            $data['error']=array(__('createAccount.error.invalidCode'));
            $data['code']=-1;
        }else{
            $user=$user->first();
            $user->email_verified_at=now();
            $user->save();
            if($user->userToken===null){
                $user->userToken=Hash::make(now().$user->username);
            }
            if($user->remember_token===null){
                $user->remember_token=Hash::make(now().$user->username.Str::uuid());
            }
            $user->save();

            $data=array();
            $data['data']=['deviceID'=>DeviceIdController::getTokenDevice($user->id), 'sessionToken'=>$user->remember_token, 'info'=>__('login.error.success', ['name'=>$user->name]), 'username'=>$user->username];
            $data['error']=array();
            $data['code']=0;
        }
        return response()->json($data);
    }

    public function recoveryPassword(Request $request): JsonResponse {
        $data=$request->get('data');
        $validator = Validator::make(
            $data,
            [
                'email' => 'required|email',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }
        $user=User::where('email', '=', $data['email'])->get();
        if($user->count()===0){
            $data=array();
            $data['data']=array();
            $data['error']=__("recoveryPassword.error.emailInvalid");
            $data['code']=-2;
            return response()->json($data);
        }else{
            $recovery = new RecoveryPasswords();
            $recovery->userID=$user->first()->id;
            $recovery->token=Str::uuid();
            $recovery->save();
            $user=$user->first();
            Mail::send('emails.recovery', ['user' => $user, "recovery"=>$recovery], function ($m) use ($user) {
                $m->to($user->email, $user->name)->subject(__('createAccount.email.subject'));
            });
            $data=array();
            $data['error']=array();
            $data['data']=__("recoveryPassword.success");
            $data['code']=0;
            return response()->json($data);
        }
    }

    public function recoveryChangePassword(Request $request, $lang, $token): JsonResponse {
        $data=$request->get('data');
        $data['token']=$token;
        $validator = Validator::make(
            $data,
            [
                'password' => 'required|min:8',
                'passwordVerify' => 'required|min:8',
                'token'=>'required'
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }

        $process = RecoveryPasswords::with('userInfo')
                ->where('token', '=', $token)
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->where('used', '=', false)
                ->get();

        if($process->count()===0){
            $data=array();
            $data['data']=array();
            $data['error']=array(__('recoveryPasswordChange.error'));
            $data['code']=-1;
        }else{
            if($data['password']===$data['passwordVerify']){
                $userID=$process->first()->userInfo->id;
                $user=User::where('id', '=', $userID)->get()->first();
                $user->setPasswordAttribute($data['password']);
                $user->save();
                $process=$process->first();
                $process->used=true;
                $process->save();
                $data=array();
                $data['error']=array();
                $data['data']=array(__('recoveryPasswordChange.success'));
                $data['code']=0;
            }
        }
        return response()->json($data);
    }

    public function login(Request $request): JsonResponse {
        $data=$request->get('data');
        $user=User::orWhere('username', '=', $data['username'])->orWhere('email', '=', $data['username'])->where('locked', '=', 0)->get();
        if($user->count()===0){
            $data=array();
            $data['data']=array();
            $data['error']=__("login.error.notFound");
            $data['code']=-2;
        }else{
            $user=$user->first();
            if(Hash::check($data['password'], $user->password)){
                if($user->email_verified_at==null){
                    $data=array();
                    $data['data']=array();
                    $data['error']=__('login.error.accountNotVerified');
                    $data['code']=-3;
                }else{
                    if($user->userToken===null){
                        $user->userToken=Hash::make(now().$user->username);
                    }
                    if($user->remember_token===null){
                        $user->remember_token=Hash::make(now().$user->username.Str::uuid());
                    }
                    $user->save();

                    $data=array();
                    $data['data']=['deviceID'=>DeviceIdController::getTokenDevice($user->id), 'sessionToken'=>$user->remember_token, 'info'=>__('login.error.success', ['name'=>$user->name]), 'username'=>$user->username];
                    $data['error']=array();
                    $data['code']=0;
                }
            }else{
                $data=array();
                $data['data']=array();
                $data['error']=__('login.error.passwordWrong');
                $data['code']=-2;
            }
        }
        return response()->json($data);
    }

    public function editAccount(Request $request): JsonResponse {
        $user= $request->get('user');
        $data=$request->get('data');
        $validator = Validator::make(
            $data,
            [
                'username' => 'min:3',
                'description' => 'min:5',
                'name' => 'min:5'
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }else{
            if(isset($data['username'])) {
                $validation = User::where('username', '=', $data['username'])->where('id', '<>', $user->id)->get();
                if($validation->count()===0){
                    if(User::where('username', '=', $data['username'])->where('id', '=', $user->id)->get()->count()===0){
                        $user->username=$data['username'];
                        $history=new UsernameHistory();
                        $history->userID=$user->id;
                        $history->username=$user->username;
                        $history->save();
                    }
                }else{
                    $data=array();
                    $data['data']=array();
                    $data['error']=__('editAccount.error.username');
                    $data['code']=-2;
                    return response()->json($data);
                }
            }
            iF(isset($data['description'])){
                $user->description=$data['description'];
            }

            if(isset($data['name'])){
                $user->name=$data['name'];
            }

            $user->save();
            $data=array();
            $data['data']=__('editAccount.success');
            $data['error']=array();
            $data['code']=0;
            return response()->json($data);
        }
    }

    public function destroyAllSession(Request $request): JsonResponse {
        $user= $request->get('user');
        $user->userToken=Hash::make(now().$user->username);
        $user->remember_token=Hash::make(now().$user->username.Str::uuid());
        $user->save();

        $data=array();
        $data['data']=['userTokem'=>$user->userToken, 'sessionToken'=>$user->remember_token, 'info'=>__('destroySessions')];
        $data['error']=array();
        $data['code']=0;


        return response()->json($data);
    }

    public function deleteAccount(Request $request): JsonResponse {
        $user= $request->get('user');
        $delete = new DeleteAccount();
        $delete->username=$user->username;
        $delete->save();
        $user->delete();
        $data=array();
        $data['data']=__('delete');
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);
    }

    public function newActivatinCode(Request $request): JsonResponse {
        $data=$request->get('data');
        $data=Arr::only($data, array('email'));
        $validator = Validator::make(
            $data,
            [
                'email' => 'required|email',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }
        $verf=User::where('email', '=', $data['email'])->get();
        if($verf->count()===0){
            $data=array();
            $data['data']=__('newActivationCode.error.emailInvalid');
            $data['error']=array();
            $data['code']=-2;
        }else{
            $user=$verf->first();

            if($user->email_verified_at===null){
                $user->email_verified_code = rand(100000, 999999);
                $user->email_verified_requisite = now();
                $user->save();
                Mail::send('emails.validationRenew', ['user' => $user], function ($m) use ($user) {
                    $m->to($user->email, $user->name)->subject(__('createAccount.email.subject'));
                });

                $data=array();
                $data['data']=__('newActivationCode.success');
                $data['error']=array();
                $data['code']=0;
            }else{
                $data=array();
                $data['data']=__('newActivationCode.error.accountValid');
                $data['error']=array();
                $data['code']=-2;
            }


        }
        return response()->json($data);
    }

    public function me(Request $request): JsonResponse {
        $user= $request->get('user');
        $data=array();
        $data['data']=$user->append('totalFollowers', 'totalFollowings', 'totalPublications');
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);
    }

    public function getPhotoPorfile(Request $request, $lang, $username): StreamedResponse {
        $user=User::where('username','=', $username)->get();
        if($user->first()!==null){
            $user=$user->first();
            if($user->photoProfile!==null){
                if(Storage::disk()->exists("/images/profile/".$user->photoProfile)){
                    return Storage::response("/images/profile/".$user->photoProfile);
                }else{
                    return Storage::response("/images/default.png");
                }
            }else{
                return Storage::response("/images/default.png");
            }
        }else{
            return Storage::response("/images/default.png");
        }
    }

    public function setPhotoPorfile(Request $request): JsonResponse {

        if($request->hasFile('photo')) {
            $user= $request->get('user');
            $path = $request->file('photo')->store('images/profile');
            $image_resize = Image::make(storage_path('app/'.$path));
            $width=600;
            $imgWidth=$image_resize->getWidth();
            $ratio = $width/$imgWidth;
            $image_resize->resize($width, $image_resize->getHeight()*$ratio);
            $image_resize->save(storage_path('app/'.$path));
            if($user->photoProfile!==null){
                if(Storage::disk()->exists("/images/profile/".$user->photoProfile)) {
                    unlink(storage_path('app/images/profile/' . $user->photoProfile));
                }
            }
            $user->photoProfile=explode('/', $path)[count(explode('/', $path))-1];
            $user->save();
            $data=array();
            $data['data']=__("editAccount.setPhoto.success");
            $data['error']=array();
            $data['code']=0;
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('editAccount.setPhoto.error'));
            $data['code']=-1;
        }
        return response()->json($data);
    }

    public function removePhotoProfile(Request $request) {
        $user= $request->get('user');
        if($user->photoProfile!==null){
            if(Storage::disk()->exists("/images/profile/".$user->photoProfile)) {
                unlink(storage_path('app/images/profile/' . $user->photoProfile));
            }
        }
        $user->photoProfile=null;
        $user->save();
        $data=array();
        $data['data']=__("editAccount.remove.success");
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);

    }

    public function seeAccount(Request $request, $lang, $username){
        $userInfo=User::where('username', '=', $username)->get();
        if($userInfo->count()===1){
            if($request->get('user')===null){
                $user=$userInfo->first();
                if($user->private===false){
                    $data=array();
                    $data['data']=$user->where('username', '=', $username)->get(['name', 'username', 'description', 'photoProfile', 'verified'])->fisrt()->append('totalFollowers', 'totalFollowings');
                    $data['error']=array();
                    $data['code']=0;
                    return response()->json($data);
                }else{
                    $data=array();
                    $data['data']=$user->where('username', '=', $username)->get()->first()->append('totalFollowers', 'totalFollowings');
                    $data['error']=array();
                    $data['code']=0;
                    return response()->json($data);
                }
            }else{
                $session=$request->get('user');
                if(BlockController::verifyIsBlocked($session->id, $userInfo->first()->id)){
                    $data=array();
                    $data['data']=array();
                    $data['error']=array(__('user.notFound'));
                    $data['code']=-1;
                    return response()->json($data);
                }else{
                    $user=$userInfo->first();
                    if($user->private===false){
                        $data=array();
                        $data['data']=$user->where('username', '=', $username)->get(['name', 'username', 'description', 'photoProfile', 'verified'])->fisrt()->append('totalFollowers', 'totalFollowings', 'isFollow', 'followMe');
                        $data['error']=array();
                        $data['code']=0;
                        return response()->json($data);
                    }else{
                        if(FollowController::verifyIsFollowing($session->id, $user->id)){
                            $data=array();
                            $data['data']=$user->where('username', '=', $username)->get()->first()->append('totalFollowers', 'totalFollowings', 'isFollow', 'followMe');
                            $data['error']=array();
                            $data['code']=0;
                            return response()->json($data);
                        }else{
                            $data=array();
                            $data['data']=$user->where('username', '=', $username)->get()->first()->append('totalFollowers', 'totalFollowings', 'isFollow', 'followMe', 'isBlocked');
                            $data['error']=array();
                            $data['code']=0;
                            return response()->json($data);
                        }
                    }
                }
            }
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array(__('user.notFound'));
            $data['code']=-1;
            return response()->json($data);
        }
    }

    public function verifyUsernameAndEamil(Request $request) {
        $data=$request->get('data');
        $data=Arr::only($data, array('email', 'username'));
        $validator = Validator::make(
            $data,
            [
                'username' => 'required|unique:users|min:3',
                'email' => 'required|email|unique:users',
            ]
        );
        if($validator->fails()) {
            $data=array();
            $data['data']=array();
            $data['error']=$validator->errors()->toArray();
            $data['code']=-1;
            return response()->json($data);
        }else{
            $data=array();
            $data['data']=array();
            $data['error']=array();
            $data['code']=0;
            return response()->json($data);
        }
    }
}
