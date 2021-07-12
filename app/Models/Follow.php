<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;
    protected $table="follow";
    protected $appends=['userName', 'followName'];

    public function userInfo() {
        return $this->hasOne(User::class, 'id', 'user');
    }
    public function followInfo() {
        return $this->hasOne(User::class, 'id', 'follower');
    }

    public function getUserNameAttribute() {
        return $this->userInfo->name;
    }

    public function getFollowNameAttribute() {
        return $this->followInfo->name;
    }

}
