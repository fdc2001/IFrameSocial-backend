<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetUser extends Model
{
    use HasFactory;
    protected $table = 'pet_user';

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function details() {
        return $this->hasOne(PetConfig::class, 'id', 'config_id')->with(['style','type']);
    }


}
