<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceId extends Model
{
    use HasFactory;
    protected $appends=['thisDevice', 'BrowserDescription', 'SystemDescription', 'DeviceDescription'];
    protected $hidden=['token'];


    function getThisDeviceAttribute(){
        $request=Request();
        $device=$request->get('deviceID');
        return $this->attributes['id']===$device;
    }

    function getBrowserDescriptionAttribute(){
        if($this->attributes['browser']==='Unknown Browser'){
            return __('device.unknown.browser');
        }else{
            return $this->attributes['browser'];
        }
    }

    function getSystemDescriptionAttribute(){
        if($this->attributes['system']==='Unknown OS Platform'){
            return __('device.unknown.system');
        }else{
            return $this->attributes['system'];
        }
    }

    function getDeviceDescriptionAttribute(){
        if($this->attributes['device']==='Computer'){
            return __('device.computer.device');
        }elseif ($this->attributes['device']==="Mobile"){
            return __('device.mobile.device');
        } else{
            return $this->attributes['device'];
        }
    }

    function getCreatedAtAttribute(): string {
        return Carbon::parse($this->attributes['created_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d h:i:s');
    }

    function getUpdatedAtAttribute(): string {
        return Carbon::parse($this->attributes['updated_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d h:i:s');
    }
}
