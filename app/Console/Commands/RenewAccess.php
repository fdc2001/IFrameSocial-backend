<?php

namespace App\Console\Commands;

use App\Http\Controllers\InstagramController;
use Illuminate\Console\Command;

class RenewAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RenewAccess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renew Access from socials networks';

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
        (new InstagramController())->renewAccess();
        return 0;
    }
}
