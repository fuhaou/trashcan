<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // debug sql, uncomment to use
        // app('db')->listen(
        //     function (
        //         $query
        //     ) {
        //         if ($query->connectionName != 'mongodb') {
        //             $sql = str_replace(array('%', '?'), array('%%', '%s'), $query->sql);
        //             foreach ($query->bindings as $index => $bind) {
        //                 if ($bind instanceof \DateTime) {
        //                     $query->bindings[$index] = $bind->format('Y-m-d H:i:s');
        //                 }
        //             }
        //             $sql = vsprintf($sql, $query->bindings);

        //             dump($sql);
        //         }
        // });
    }
}
