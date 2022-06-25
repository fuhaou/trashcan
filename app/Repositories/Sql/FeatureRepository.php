<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Actions;
use App\Models\Sql\Channels;
use App\Models\Sql\Countries;
use App\Models\Sql\Features;
use App\Models\Sql\GroupFeatures;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class FeatureRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Features::class;
    }

    public function search($featureCode = null, $channelCode = null, $countryCode = null,$groupFeature=null)
    {
        $model = $this->_model->newQuery()
            ->join(GroupFeatures::TABLE_NAME, GroupFeatures::COL_GROUP_FEATURES_ID, Features::COL_FK_GROUP_FEATURE)
            ->join(Channels::TABLE_NAME, Channels::COL_CHANNELS_ID, Features::COL_FK_CHANNEL)
            ->join(Countries::TABLE_NAME, Channels::COL_FK_COUNTRY, Countries::COL_COUNTRIES_ID)
            ->select(
                GroupFeatures::COL_GROUP_FEATURES_ID,
                GroupFeatures::COL_GROUP_FEATURES_CODE,
                GroupFeatures::COL_GROUP_FEATURES_NAME,
                Features::COL_FEATURES_ID,
                Features::COL_FEATURES_CODE,
                Features::COL_FEATURES_NAME,
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_NAME,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_CODE
            );
        if ($channelCode) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if ($featureCode) {
            $model->where(Features::COL_FEATURES_CODE, $featureCode);
        }
        if ($countryCode) {
            $model->where(Countries::COL_COUNTRIES_CODE, $countryCode);
        }
        if ($groupFeature) {
            $model->where(GroupFeatures::COL_GROUP_FEATURES_CODE, $groupFeature);
        }

        return $model->get();
    }
}
