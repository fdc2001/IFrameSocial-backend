<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('externalID');
            $table->foreignId('user')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('socialID')->references('id')->on('config_socials')->onDelete('cascade');
            $table->string('type');
            $table->string("content");
            $table->string("thumbnail")->nullable();
            $table->longText("description")->nullable();
            $table->dateTime('publishDate');
            $table->foreignId('pubID')->nullable()->references('id')->on('publications')->onDelete('cascade');
            $table->integer('status');

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
        Schema::dropIfExists('publications');
    }
}
