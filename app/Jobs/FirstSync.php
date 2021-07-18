<?php

namespace App\Jobs;

use App\Http\Controllers\InstagramController;
use App\Http\Controllers\TwitterController;
use App\Models\ConfigSocial;
use App\Models\ConfigSocialUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FirstSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var ConfigSocialUser
     */
    private $config;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ConfigSocialUser $config)
    {
        $this->config=$config;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $configUser=$this->config;
        $configuration=ConfigSocial::where('id', '=', $configUser->socialID)->first();
        if($configuration->name==="Instagram"){
            InstagramController::sync($configUser, $configuration);
            $configUser->lastSync=now();
            $configUser->save();
        }else if($configuration->name === "Twitter"){
            TwitterController::sync($configUser, $configuration);
            $configUser->lastSync=now();
            $configUser->save();
        }
    }
}

