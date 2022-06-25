<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 18/11/2020
 * Time: 16:56
 */

namespace App\Repositories\Sql;


use App\Models\Sql\Channels;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Countries;
use App\Models\Sql\Features;
use App\Models\Sql\SubscriptionDetails;
use App\Models\Sql\SubscriptionDetailsShop;
use App\Repositories\BaseSqlRepository;

class SubscriptionCodeRepository extends BaseSqlRepository
{
    /**
     * @return string
     */
    public function getModel()
    {
        return CompanySubscriptionCode::class;
    }

    public function search($subscriptionCode=null, $companyId=null, $featureId=null, $channelCode=null, $countryCode=null)
    {
        $subscriptionCode = $subscriptionCode ? trim($subscriptionCode) : null;
        $companyId = $companyId ? intval($companyId) : null;
        $featureId = $featureId ? intval($featureId) : null;
        $channelCode = $channelCode ? trim($channelCode) : null;
        $countryCode = $countryCode ? trim($countryCode) : null;

        $model = $this->_model->newQuery()->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_FK_COMPANY_SUBSCRIPTION_CODE,
            '=',
            CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_ID
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            SubscriptionDetails::COL_FK_FEATURES
        )->leftJoin(
            SubscriptionDetailsShop::TABLE_NAME,
            SubscriptionDetailsShop::COL_FK_SUBSCRIPTION_DETAILS_ID,
            '=',
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID
        )->join(
            Channels::TABLE_NAME,
            Channels::COL_CHANNELS_ID,
            '=',
            Features::COL_FK_CHANNEL
        )->join(
            Countries::TABLE_NAME,
            Countries::COL_COUNTRIES_ID,
            '=',
            Channels::COL_FK_COUNTRY
        );
        if ($subscriptionCode) {
            $model->where(CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_VALUE, $subscriptionCode);
        }
        if ($companyId) {
            $model->where(CompanySubscriptionCode::COL_FK_COMPANY, $companyId);
        }
        if ($featureId) {
            $model->where(SubscriptionDetails::COL_FK_FEATURES, $featureId);
        }
        if ($channelCode) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if ($countryCode) {
            $model->where(Countries::COL_COUNTRIES_CODE, $countryCode);
        }
        return $model->get();
    }
}
