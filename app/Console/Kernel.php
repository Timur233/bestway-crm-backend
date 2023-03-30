<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('fetch:orders')
        //      ->daily()
        //      ->sendOutputTo('/path/to/log/fetch_orders.log')
        //      ->emailOutputTo('admin@example.com')
        //      ->before(function () {
        //          // Действия, выполняемые перед выполнением команды
        //      });

        $schedule->call(function () {
            exec('php ' . env('ARTISAN_PATH') . '/artisan fetch:orders');
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
