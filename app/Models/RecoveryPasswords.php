<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoveryPasswords extends Model
{
    use HasFactory;
    protected $table="password_resets";
    protected $dates = ['created_at'];


    public function userInfo() {
        return $this->hasOne(User::class, 'id', 'userID');
    }


}
