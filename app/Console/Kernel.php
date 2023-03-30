<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

function proc_exec() {
    $output = [];
    $exitCode = 0;
    $command = 'php /path/to/artisan fetch:orders';

    exec($command, $output, $exitCode);

    return $exitCode;
}

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
        if (proc_exec() !== 0) {
            // обработка ошибок
        } else {
            // обработка вывода
        }

        // $schedule->command('fetch:orders');
        // $schedule->command('fetch:orders')->everyFiveMinutes();
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
