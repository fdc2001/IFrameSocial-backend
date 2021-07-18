<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('{locale}')->group(function () {

    Route::prefix('v1')->group(function () {

        Route::prefix('account')->group(function (){
            Route::post('/new', 'UserController@createUser');
            Route::post('/new/verify', 'UserController@verifyUsernameAndEamil');
            Route::post('/validate', 'UserController@activateAccount');
            Route::post('/validateNewCode', 'UserController@newActivatinCode');
            Route::post('/recovery', 'UserController@recoveryPassword');
            Route::post('/recovery/{token}', 'UserController@recoveryChangePassword');
            Route::post('/login', 'UserController@login');
            Route::post('/photo', 'UserController@setPhotoPorfile');

            Route::put('/profile', 'UserController@editAccount');

            Route::delete('/profile', 'UserController@deleteAccount');
            Route::delete('/destroyAllSession', 'UserController@destroyAllSession');
            Route::delete('/photo', 'UserController@removePhotoProfile');

            Route::get('/profile', 'UserController@me');

            Route::get('/profile/{username}', 'UserController@seeAccount');
            Route::get('/photo/{username}', 'UserController@getPhotoPorfile');

            Route::get('/follow', 'FollowController@listPending');
            Route::post('/follow/{id}', 'FollowController@accept');
            Route::put('/follow/{id}', 'FollowController@removeFollow');
            Route::get('/follow/{username}', 'FollowController@startFollow');
            Route::delete('/follow/{username}', 'FollowController@stopFollow');
        });

        Route::prefix('publication')->group(function(){
            Route::get('', 'PublicationController@myPublications');
            Route::get('/feed', 'PublicationController@feed');
            #Route::get('/media/getTumbnail/{id}/{auth?}', 'PublicationController@getMedia');
            Route::get('/media/thumbnail/{id}/{auth?}', 'PublicationController@getMediaThumb');
            Route::get('/media/{id}/{auth?}', 'PublicationController@getMedia');
        });

        Route::prefix('privacy')->group(function(){
            Route::get('/block/{user}', 'BlockController@blockUser');
            Route::delete('/block/{user}', 'BlockController@unblock');
        });

        Route::prefix('security')->group(function (){
            Route::get('/devices', 'DeviceIdController@getLoginDevices');
            Route::put('/devices/{id}', 'DeviceIdController@changeDeviceStatus');
        });

        Route::prefix('sync')->group(function (){
            Route::get('saveSession/{auth}/{route}', 'SocialController@saveSession');
            Route::get('instagram', 'InstagramController@register');
            Route::get('instagram/url', 'InstagramController@redirectToLogin')->name("instagram");
            Route::get('twitter', 'TwitterController@register');
            Route::get('twitter/url', 'TwitterController@redirectToLogin')->name("twitter");

            Route::get('configs', 'SocialController@getSocialsConfig');
        });

    });

});

