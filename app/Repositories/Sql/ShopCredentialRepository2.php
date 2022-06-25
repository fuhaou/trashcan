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
use App\Models\Sql\ShopCredential2;
use App\Models\Sql\ShopCredentialAction;
use App\Models\Sql\Shops;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class ShopCredentialRepository2 extends BaseSqlRepository
{
    public function getModel()
    {
        return ShopCredential2::class;
    }

    /**
     * @param $username
     * @param null $shopId
     * @param null $channelId
     * @param null $countryId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUsername($username, $shopId = null, $channelId = null, $countryId = null)
    {
        $username = trim($username);
        $shopId = $shopId ? intval($shopId) : null;
        $channelId = $channelId ? intval($channelId) : null;
        $countryId = $countryId ? intval($countryId) : null;
        $model = $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            Shops::COL_SHOPS_EID,
            '=',
            ShopCredential2::COL_FK_SHOP
        )->join(
            Channels::TABLE_NAME,
            Channels::COL_CHANNELS_ID,
            '=',
            Shops::COL_FK_CHANNEL
        )->whereJsonContains(
            sprintf(
                '%s->%s',
                ShopCredential2::COL_SHOP_CREDENTIAL_VALUE,
                'user_name'
            ),
            $username,
        );
        if ($shopId) {
            $model->where(Shops::COL_SHOPS_EID, $shopId);
        }
        if ($channelId) {
            $model->where(Shops::COL_FK_CHANNEL, $channelId);
        }
        if ($countryId) {
            $model->where(Channels::COL_FK_COUNTRY, $countryId);
        }

        return $model->first();
    }

    /**
     * @param array $shopEid
     * @param array $type
     */
    public function getCredentialShopByShopEid($shopEid, $type = [], $actionCode = null)
    {
        $model = $this->_model->newQuery()
            // ->join(
            //     Shops::TABLE_NAME,
            //     ShopCredential2::COL_FK_SHOP,
            //     Shops::COL_SHOPS_EID
            // )
            ->leftJoin(
                ShopCredentialAction::TABLE_NAME,
                ShopCredential2::COL_SHOP_CREDENTIAL_ID,
                ShopCredentialAction::COL_FK_SHOP_CREDENTIAL
            )
            ->leftJoin(
                Actions::TABLE_NAME,
                Actions::COL_ACTIONS_ID,
                ShopCredentialAction::COL_FK_ACTION
            )
            ->leftJoin(
                Features::TABLE_NAME,
                Features::COL_FEATURES_ID,
                Actions::COL_FK_FEATURE
            )
            // ->leftJoin(
            //     Channels::TABLE_NAME,
            //     Channels::COL_CHANNELS_ID,
            //     '=',
            //     Shops::TABLE_NAME . '.' . Shops::COL_FK_CHANNEL
            // )
            ->whereIn(ShopCredential2::COL_FK_SHOP, $shopEid)
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_IS_ACTIVE, ShopCredential2::IS_ACTIVE)
            ->select(
                ShopCredential2::COL_SHOP_CREDENTIAL_ID,
                ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN,
                ShopCredential2::COL_SHOP_CREDENTIAL_TYPE,
                ShopCredential2::COL_SHOP_CREDENTIAL_STATE,
                ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC,
                ShopCredential2::COL_FK_SHOP,
                ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_OTP,
                DB::raw(sprintf('JSON_UNQUOTE(JSON_EXTRACT(%s, "$.user_name")) as user_name', ShopCredential2::COL_SHOP_CREDENTIAL_VALUE)),
                DB::raw(sprintf('JSON_UNQUOTE(JSON_EXTRACT(%s, "$.password")) as password', ShopCredential2::COL_SHOP_CREDENTIAL_VALUE)),
                DB::raw(sprintf('JSON_UNQUOTE(JSON_EXTRACT(%s, "$.seller_id")) as seller_id', ShopCredential2::COL_SHOP_CREDENTIAL_VALUE)),
                ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT,
                Features::COL_FEATURES_ID,
                Features::COL_FEATURES_NAME,
                Features::COL_FEATURES_CODE,
                Actions::COL_ACTIONS_TYPE,
                // Channels::COL_CHANNELS_CONFIG,
                // Channels::COL_CHANNELS_CODE,
            );
        if (!empty($actionCode)) {
            $model->where(Actions::COL_ACTIONS_CODE, $actionCode);
        }
        if (!empty($type)) {
            $model->whereIn(ShopCredential2::COL_SHOP_CREDENTIAL_TYPE, $type);
        }

        return $model->get();
    }

    public function getShopNeedOTP()
    {
        return $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            ShopCredential2::COL_FK_SHOP,
            Shops::COL_SHOPS_EID
        )
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC, '>', 0)
            ->orWhere(function ($q) {
                $q->where(ShopCredential2::COL_SHOP_CREDENTIAL_STATE, ShopCredential2::STATE_NEED_OTP)
                    ->where(ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC, 0);
            })
            ->get();
    }

    public function getCredentialValueByShopEid($shopEid)
    {
        return $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            ShopCredential2::COL_FK_SHOP,
            Shops::COL_SHOPS_EID
        )->where(Shops::COL_SHOPS_EID, $shopEid)
            ->select(
                Shops::COL_SHOPS_SID,
                Shops::COL_SHOPS_NAME,
                ShopCredential2::COL_SHOP_CREDENTIAL_ID,
                ShopCredential2::COL_SHOP_CREDENTIAL_VALUE,
                ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN,
                ShopCredential2::COL_SHOP_CREDENTIAL_STATE,
                ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC
            )
            ->get();
    }

    public function getCredentialValueByShopEidAndType($shopEid, $typeArr)
    {
        return $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            ShopCredential2::COL_FK_SHOP,
            Shops::COL_SHOPS_EID
        )
        ->where(Shops::COL_SHOPS_EID, $shopEid)
        ->whereIn(ShopCredential2::COL_SHOP_CREDENTIAL_TYPE, $typeArr)
        ->select(
            Shops::COL_SHOPS_SID,
            Shops::COL_SHOPS_NAME,
            ShopCredential2::COL_SHOP_CREDENTIAL_ID,
            ShopCredential2::COL_SHOP_CREDENTIAL_VALUE,
            ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN,
            ShopCredential2::COL_SHOP_CREDENTIAL_STATE,
            ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC
        )
        ->get();
    }

    public function getAllCredential($shopEid = null, $isActive = ShopCredential2::IS_ACTIVE, $channelCode = null, $type = [], $joinCountries = false)
    {
        $model = $this->_model->newQuery()
            ->join(
                Shops::TABLE_NAME,
                ShopCredential2::COL_FK_SHOP,
                Shops::COL_SHOPS_EID
            )
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                Shops::COL_FK_CHANNEL
            );
        if ($joinCountries) {
            $model->join(
                Countries::TABLE_NAME,
                Countries::COL_COUNTRIES_ID,
                Channels::COL_FK_COUNTRY
            );
        }
        if (!empty($isActive)) {
            $model->where(ShopCredential2::COL_SHOP_CREDENTIAL_IS_ACTIVE, ShopCredential2::IS_ACTIVE)
                ->where(Shops::COL_SHOPS_IS_ACTIVE, ShopRepository::IS_ACTIVE);
        }
        if (!empty($shopEid)) {
            $model->where(Shops::COL_SHOPS_EID, $shopEid);
        }
        if (!empty($channelCode)) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if (!empty($type)) {
            $model->whereIn(ShopCredential2::COL_SHOP_CREDENTIAL_TYPE, $type);
        }
        return $model->get();
    }

    public function getShopNeedEmailOTP($email)
    {
        return $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            ShopCredential2::COL_FK_SHOP,
            Shops::COL_SHOPS_EID
        )->join(
            Channels::TABLE_NAME,
            Shops::COL_FK_CHANNEL,
            Channels::COL_CHANNELS_ID
        )->join(
            Countries::TABLE_NAME,
            Channels::COL_FK_COUNTRY,
            Countries::COL_COUNTRIES_ID
        )
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_OTP_EMAIL, $email)
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_STATE, ShopCredential2::STATE_NEED_OTP)
            ->orWhere(function ($q) use ($email) {
                $q->where(ShopCredential2::COL_SHOP_CREDENTIAL_OTP_STATUS, ShopCredential2::OTP_STATUS_NEED_OTP)
                    ->where(ShopCredential2::COL_SHOP_CREDENTIAL_OTP_EMAIL, $email);
            })
            ->get();
    }
}
