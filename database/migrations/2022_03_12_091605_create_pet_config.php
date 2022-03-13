<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePetConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pet_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->references('id')->on('pet_type');
            $table->foreignId('style_id')->references('id')->on('pet_style');
            $table->string('race');
            $table->string("rarity");
            $table->integer("existing");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pet_config');
    }
}
