<?php

namespace App\Console\Commands;

use App\Http\Controllers\InstagramController;
use App\Models\ConfigSocial;
use App\Models\ConfigSocialUser;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncSocial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SyncSocial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Routine to sync the all social networks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $configs=ConfigSocialUser::where('lastSync', '<=', Carbon::now()->subHour()->toDateTimeString())->get();
        /**
         * @var $row = Config of user
         */
        foreach ($configs as $row){
            switch ($row->socialID){
                case 1:{
                    $configuration = ConfigSocial::where('id', '=', 1)->first();
                    InstagramController::sync($row, $configuration);
                    $row->lastSync=now();
                    $row->save();
                    break;
                }
            }
        }
        return 0;
    }
}
