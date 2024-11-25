<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Exécute le nettoyage toutes les heures
        $schedule->call(function () {
            $controller = new DKeyController();
            $controller->cleanExpiredKeys();
        })->hourly();
        
        // Ou toutes les minutes pour un nettoyage plus fréquent
        // ->everyMinute();
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
