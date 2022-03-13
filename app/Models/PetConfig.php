<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetConfig extends Model
{
    use HasFactory;
    protected $table="pet_config";
    protected $hidden = ['config_id','user_id'];

    public function style() {
        return $this->hasOne(PetStyle::class, 'id', 'style_id');
    }

    public function type() {
        return $this->hasOne(PetType::class, 'id', 'type_id');
    }
}
