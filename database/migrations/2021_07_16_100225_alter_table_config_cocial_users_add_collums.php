<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableConfigCocialUsersAddCollums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('config_social_users', function (Blueprint $table) {
            $table->string('tokenSecret')->after('accessToken')->default("");
            $table->string('username')->after('socialID')->default("");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('config_social_users', function (Blueprint $table) {
            $table->dropColumn('tokenSecret');
            $table->dropColumn('username');
        });
    }
}
