<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Stories 24 saat sonra otomatik silinsin
        $schedule->command('model:prune', [
            '--model' => [\App\Models\Story::class],
        ])->daily();

        // İstersen her saat başı da çalıştırabilirsin (daha hızlı silinir)
        // $schedule->command('model:prune', ['--model' => [\App\Models\Story::class]])->hourly();
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