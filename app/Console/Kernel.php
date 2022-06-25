<?php

namespace App\Console;

use App\Console\Commands\CheckCookieValid2;
use App\Console\Commands\CheckAccountCookieValid;
use App\Console\Commands\CheckOpenApiTokenValid2;
// use App\Console\Commands\Once\EncryptionCredentialValue;
// use App\Console\Commands\Once\GetShopSid;
use App\Console\Commands\Once\MigrateUserTable;
use App\Console\Commands\Once\RemoveCompanyUserShop;
use App\Console\Commands\RefreshCredentialShop2;
use App\Console\Commands\UpdateShopName;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        RefreshCredentialShop2::class,
        MigrateUserTable::class,
        // GetShopSid::class,
        // EncryptionCredentialValue::class,
        CheckCookieValid2::class,
        CheckAccountCookieValid::class,
        CheckOpenApiTokenValid2::class,
        RemoveCompanyUserShop::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(CheckCookieValid2::class, ['--channelCode' => 'SHOPEE'])->cron('15,45 * * * *'); // At every 15,45 minute
        $schedule->command(CheckCookieValid2::class, ['--channelCode' => 'LAZADA'])->cron('5,35 * * * *'); // At every 5,35 minute
        $schedule->command(CheckCookieValid2::class, ['--channelCode' => 'TOKOPEDIA'])->cron('10,40 * * * *'); // At every 10,40 minute

        // lazada account brand portal + marketing solutions
        $schedule->command(CheckAccountCookieValid::class, ['--channelCode' => 'LAZADA'])->hourlyAt(3); // At minute 3 every hour

        // command to update name
        $schedule->command(UpdateShopName::class, ['channelCode' => 'SHOPEE'])->weeklyOn(1, '5:00'); // every week on Monday at 5:00
        $schedule->command(UpdateShopName::class, ['channelCode' => 'LAZADA'])->weeklyOn(1, '6:00'); // every week on Monday at 6:00
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
