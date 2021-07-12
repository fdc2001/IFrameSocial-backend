<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $timezone=$request->header('frame-tz', 'UTC');

        if(in_array($timezone, DateTimeZone::listIdentifiers())) {
            Config::set('app.timezone', $timezone);

        }else{
            Config::set('app.timezone', "UTC");
        }

        $locale=$request->route()->parameter('locale');
        $langAvailable=array("pt-PT", "en");
        if($locale!==""){
            if(in_array($locale, $langAvailable)){

                App::setLocale($locale);
            }
        }
        return $next($request);
    }
}
