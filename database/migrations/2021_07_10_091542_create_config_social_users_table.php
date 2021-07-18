<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigSocialUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config_social_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('socialID')->references('id')->on('config_socials')->onDelete('cascade');
            $table->longText('accessToken')->nullable();
            $table->longText('userID')->nullable();
            $table->dateTime('expireDate')->nullable();
            $table->boolean('automatePublications');
            $table->boolean('automateRenewAccess');
            $table->dateTime('lastSync')->nullable();
            $table->string('error')->nullable();
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
        Schema::dropIfExists('config_social_users');
    }
}
