<?php

namespace App\Models;

use App\Http\Controllers\BlockController;
use App\Http\Controllers\FollowController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_code',
        'userToken',
        'photoProfile',
        'birthDate',
        'updated_at',
        'locked',
        'email_verified_requisite',
        'email_verified_at',
        'email'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    #protected $appends=['totalFollowers'];

    public function RecoverPassword(): HasMany {
        return $this->hasMany(RecoveryPasswords::class, 'UserID', 'id');
    }

    public function setPasswordAttribute($password){

        $this->attributes['password'] = Hash::make($password);

    }

    public function Devices(): HasMany {
        return $this->hasMany(DeviceId::class, 'user', 'id');
    }

    public function followers(): HasMany {
        return $this->hasMany(Follow::class, 'follower', 'id')->where('accepted', '=', 1);
    }

    public function followings(): HasMany {
        return $this->hasMany(Follow::class, 'user', 'id')->where('accepted', '=', 1);
    }


    public function publications(): HasMany {
        return $this->hasMany(Publication::class, 'user', 'id')->where('status', '=', 1);
    }

    public function pets(): HasMany {
        return $this->hasMany(PetUser::class, 'user_id', 'id')->with(['details']);
    }

    function getPetsAttribute(){
        return $this->pets()->get();
    }

    function getTotalPublicationsAttribute(){
        return $this->publications()->get()->count();
    }

    function getPublicationsAttribute(){
        return $this->publications()->get()->take(10);
    }

    function getTotalFollowingsAttribute() {
        return $this->followings()->get()->count();
    }

    function getTotalFollowersAttribute() {
        return $this->followers()->get()->count();
    }

    function getCreatedAtAttribute(){
        return Carbon::parse($this->attributes['created_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    function getUpdatedAtAttribute(){
        return Carbon::parse($this->attributes['updated_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    function getIsFollowAttribute(){
        $request=Request();
        $session=$request->get('user');
        return FollowController::verifyIsFollowing($session->id, $this->attributes['id']);
    }

    function getIsBlockedAttribute(){
        $requesr=Request();
        $session=$requesr->get('user');
        return BlockController::verifyIsBlocked($this->attributes['id'], $session->id);
    }

    function getFollowMeAttribute(){
        $requesr=Request();
        $session=$requesr->get('user');
        return FollowController::verifyIsFollowMe($session->id, $this->attributes['id']);
    }
}
