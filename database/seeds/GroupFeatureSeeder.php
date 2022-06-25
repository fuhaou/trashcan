<?php

namespace Database\Seeders;

use App\Models\Sql\GroupFeatures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['Marketing', 'M'],
            ['Operation', 'O'],
            ['Insight', 'I']
        ];
        $arr = DB::table('group_features')->pluck(GroupFeatures::COL_GROUP_FEATURES_NAME, GroupFeatures::COL_GROUP_FEATURES_CODE);
        $temp = [];
        foreach ($data as $item) {
            if (!isset($arr[$item[1]])) {
                $arr = [
                    GroupFeatures::COL_GROUP_FEATURES_NAME => $item[0],
                    GroupFeatures::COL_GROUP_FEATURES_CODE => $item[1],
                    GroupFeatures::COL_GROUP_FEATURES_IS_ACTIVE => 1,
                    GroupFeatures::COL_GROUP_FEATURES_CREATED_AT => time(),
                    GroupFeatures::COL_GROUP_FEATURES_UPDATED_AT => time(),
                ];
                array_push($temp, $arr);
            }
        }
        if (!empty($temp)) {
            DB::table('group_features')->insert($temp);
        }
    }
}
