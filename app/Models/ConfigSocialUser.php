<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSocialUser extends Model
{
    use HasFactory;
    protected $appends=['socialName'];
    protected $hidden=['accessToken', 'tokenSecret', 'userID'];

    public function socialConfig() {
        return $this->hasOne(ConfigSocial::class, 'id', 'socialID');
    }

    function getSocialNameAttribute(){
        return ConfigSocial::where('id', '=', $this->attributes['socialID'])->first()->name;
    }

    function getCreatedAtAttribute(){
        return Carbon::parse($this->attributes['created_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    function getUpdatedAtAttribute(){
        return Carbon::parse($this->attributes['updated_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
