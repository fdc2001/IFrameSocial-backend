<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory;
    protected $hidden=['externalID'];
    protected $appends=['social'];


    function getUserAttribute(){
        return User::where('id', '=', $this->attributes['user'])->first()->username;
    }

    function getsocialAttribute(){
        return ConfigSocial::where('id', '=', $this->attributes['socialID'])->first()->name;
    }

    function getCreatedAtAttribute(){
        return Carbon::parse($this->attributes['created_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    function getUpdatedAtAttribute(){
        return Carbon::parse($this->attributes['updated_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
