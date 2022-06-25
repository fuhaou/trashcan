<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Channels;
use App\Models\Sql\Countries;
use App\Repositories\BaseSqlRepository;

class CountryRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Countries::class;
    }

    public function search($countryCode = null, $channelCode = null, $isActive = null)
    {
        $model = $this->_model->newQuery()
            ->join(Channels::TABLE_NAME, Countries::COL_COUNTRIES_ID, Channels::COL_FK_COUNTRY)
            ->select(
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_IS_ACTIVE,
                Countries::COL_COUNTRIES_EXCHANGE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_TIMEZONE_TIME_PADDING,
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_NAME,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_IS_ACTIVE,
                Channels::COL_CHANNELS_IS_OFFLINE,
            );
        if ($channelCode) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if ($isActive) {
            $model->where(Countries::COL_COUNTRIES_IS_ACTIVE, $isActive);
        }
        if ($countryCode) {
            $model->where(Countries::COL_COUNTRIES_CODE, $countryCode);
        }

        return $model->get();
    }

    public function getCountryByCode($countryCode)
    {
        return $this->_model->newQuery()->where(Countries::COL_COUNTRIES_CODE, $countryCode)->first();
    }

}
