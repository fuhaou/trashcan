<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfterFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('features')->where('features_name', 'Tokopedia - Top Ads')->update([
            'features_name' => 'Tokopedia - Product Ads',
            'features_code' => 'M_TOK_PA',
        ]);
        DB::table('features')->where('features_name', 'Tokopedia - Headline Ads')->update([
            'features_code' => 'M_TOK_HA',
        ]);
    }
}
