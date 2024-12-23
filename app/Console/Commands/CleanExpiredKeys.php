<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanExpiredKeys extends Command
{
    protected $signature = 'dkeys:clean-expired';
    protected $description = 'Clean expired D-Keys';

    public function handle()
    {
        $expiredKeys = DKey::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Cleaned {$expiredKeys} expired D-Keys");
        return 0;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:clean-expired-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // public function handle()
    // {
    //     //
    // }
}
