<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('companies')->truncate();
        DB::table('company_register_code')->truncate();
        DB::table('channels')->truncate();
        DB::table('actions')->truncate();
        DB::table('countries')->truncate();
        DB::table('features')->truncate();
        DB::table('permission')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->call([
            \Database\Seeders\CountriesSeeder::class,
            \Database\Seeders\ChannelsSeeder::class,
            \Database\Seeders\GroupFeatureSeeder::class,
            \Database\Seeders\FeatureSeeder::class,
            \Database\Seeders\ActionSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\CompanySeeder::class,
            \Database\Seeders\AfterPermissionSeeder::class,
            \Database\Seeders\After2DeletePermissionSeeder::class,
            \Database\Seeders\After3PermissionSeeder::class,
            \Database\Seeders\After4PermissionSeeder::class,
        ]);

    }
}
