<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSocial extends Model
{
    use HasFactory;

    function getCreatedAtAttribute(){
        return Carbon::parse($this->attributes['created_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    function getUpdatedAtAttribute(){
        return Carbon::parse($this->attributes['updated_at'], 'UTC')->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
