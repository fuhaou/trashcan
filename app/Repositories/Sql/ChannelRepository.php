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
use Illuminate\Support\Facades\DB;

class ChannelRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Channels::class;
    }

    /**
     * @param null|string $channelCode
     * @param null|int $countryCode
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($channelCode = null, $countryCode = null, $isActive = null)
    {
        $model = $this->_model->newQuery()
            ->join(Countries::TABLE_NAME, Countries::COL_COUNTRIES_ID, Channels::COL_FK_COUNTRY)
            ->select(
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_NAME,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_IS_ACTIVE,
                Channels::COL_CHANNELS_IS_OFFLINE,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_IS_ACTIVE,
                Countries::COL_COUNTRIES_EXCHANGE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_TIMEZONE_TIME_PADDING
            );
        if ($channelCode) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if ($isActive) {
            $model->where(Channels::COL_CHANNELS_IS_ACTIVE, $isActive);
        }
        if ($countryCode) {
            $model->where(Countries::COL_COUNTRIES_CODE, $countryCode);
        }
        return $model->get();
    }

    public function getByCodeAndCountryCode($channelCode, $countryCode)
    {
        $model = $this->_model->newQuery()->join(
            Countries::TABLE_NAME,
            Countries::COL_COUNTRIES_ID,
            Channels::COL_FK_COUNTRY
        )->where(
            [
                Channels::COL_CHANNELS_CODE => $channelCode,
                Countries::COL_COUNTRIES_CODE => $countryCode
            ]
        );
        return $model->first();
    }
}
