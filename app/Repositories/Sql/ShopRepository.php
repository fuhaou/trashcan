<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use App\Models\Sql\Channels;
use App\Models\Sql\Countries;
use App\Models\Sql\Features;
use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use App\Repositories\BaseSqlRepository;
use App\Services\OTP2;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Constants\CacheTag;

class ShopRepository extends BaseSqlRepository
{
    const IS_ACTIVE = 1;
    const IS_INACTIVE = 0;
    const IS_RESERVE = 1;
    const IS_UN_RESERVE = 0;

    const TYPE_OPENAPI = 'openapi';
    const TYPE_CRAWLER = 'crawler';

    public function getModel()
    {
        return Shops::class;
    }

    public function search($arrShopId = null, $userId = null, $shopUserRole = null, $brandId = null,
                           QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $userId = $userId ? intval($userId) : null;
        $shopUserRole = $shopUserRole ? trim($shopUserRole) : null;
        $brandId = $brandId ? intval($brandId) : null;
        $model = $this->_model->newQuery()->join(
            ShopUser::TABLE_NAME,
            ShopUser::COL_FK_SHOP,
            '=',
            Shops::COL_SHOPS_EID
        )
            ->where(Shops::COL_SHOPS_IS_RESERVE, self::IS_UN_RESERVE)
            ->where(ShopUser::COL_SHOP_USER_IS_ALLOCATED, ShopUserRepository::ALLOCATED);
        if ($arrShopId) {
            $model->whereIn(Shops::COL_SHOPS_EID, $arrShopId);
        }
        if ($userId) {
            $model->where(ShopUser::COL_FK_USER, $userId);
        }
        if ($shopUserRole) {
            $model->where(ShopUser::COL_SHOP_USER_ROLE, $shopUserRole);
        }
        if ($brandId) {
            $model->where(ShopUser::COL_FK_BRAND, $brandId);
        }

        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }

        if (!$paginator) {
            return $model->get();
        }

        $paginator->applyQuery($model);

        return $paginator;
    }

    public function searchListShopByUser($userId = null, $countryCode = [], $channelCode = [], $featureCode = [], QuerySorter $sorter = null, QueryPaginator $paginator = null, $shopName = null)
    {
        $userId = $userId ? intval($userId) : null;
        $model = $this->_model->newQuery()
            ->join(
                ShopUser::TABLE_NAME,
                ShopUser::COL_FK_SHOP,
                '=',
                Shops::COL_SHOPS_EID
            )
            ->join(
                SubscriptionDetails::TABLE_NAME,
                SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
                '=',
                ShopUser::COL_FK_SUBSCRIPTION_DETAILS
            )
            ->join(
                Features::TABLE_NAME,
                Features::COL_FEATURES_ID,
                '=',
                SubscriptionDetails::COL_FK_FEATURES
            )
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                '=',
                sprintf('%s.%s', Shops::TABLE_NAME, Shops::COL_FK_CHANNEL)
            )
            ->join(
                Countries::TABLE_NAME,
                Countries::COL_COUNTRIES_ID,
                '=',
                Channels::COL_FK_COUNTRY
            )
            ->where(Shops::COL_SHOPS_IS_RESERVE, self::IS_UN_RESERVE)
            ->where(Shops::COL_SHOPS_IS_ACTIVE, self::IS_ACTIVE)
            ->where(ShopUser::COL_SHOP_USER_IS_ALLOCATED, ShopUserRepository::ALLOCATED)
            ->select(
                Shops::COL_SHOPS_EID,
                Shops::COL_SHOPS_NAME,
                Shops::COL_SHOPS_SID,
                Shops::COL_SHOPS_IS_ACTIVE,
                Shops::COL_SHOP_ALLOWED_PULL,
                Shops::COL_SHOP_ALLOWED_PUSH,
                Shops::COL_SHOPS_STATES,
                Shops::COL_SHOPS_CREATED_BY,
                Shops::COL_SHOPS_CREATED_AT,
                Shops::COL_SHOPS_UPDATED_BY,
                Shops::COL_SHOPS_UPDATED_AT,
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_NAME,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_EXCHANGE,
                DB::raw('GROUP_CONCAT(DISTINCT (CONCAT(' . Features::COL_FEATURES_CODE . ',"*", ' . Features::COL_FEATURES_NAME . ')) ) AS ' . Features::COL_FEATURES_NAME)
            )->groupBy(Shops::COL_SHOPS_EID);

        if ($userId) {
            $model->where(ShopUser::COL_FK_USER, $userId);
        }
        if (!empty($shopName)) {
            $model->where(Shops::COL_SHOPS_NAME, 'like', '%' . $shopName . '%');
        }

        if ($countryCode) {
            $model->whereIn(Countries::COL_COUNTRIES_CODE, $countryCode);
        }

        if ($channelCode) {
            $model->whereIn(Channels::COL_CHANNELS_CODE, $channelCode);
        }

        if ($featureCode) {
            $model->whereIn(Features::COL_FEATURES_CODE, $featureCode);
        }

        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }

        if (!$paginator) {
            return $model->get();
        }

        $paginator->applyQuery($model);

        return $paginator;
    }

    /**
     * @param $shopName
     * @param $userId
     * @param $sid
     * @param $channelId
     * @param $credential
     * @param $cookies
     * @param $isReserve
     * @param $phone
     * @param $withoutTransaction
     * @return array
     */
    public function createShopInfo($shopName, $userId, $sid, $channelId, $credential, $isReserve, $cookies = null, $phone=null, $withoutTransaction = false)
    {
        $anonymous = function() use ($shopName, $userId, $sid, $channelId, $credential, $isReserve, $cookies, $phone) {
            $shopInsert = [
                Shops::COL_SHOPS_NAME => $shopName,
                Shops::COL_FK_CHANNEL => $channelId,
                Shops::COL_SHOPS_IS_RESERVE => $isReserve,
                Shops::COL_SHOPS_CREATED_BY => $userId,
                Shops::COL_SHOPS_CREATED_AT => time(),
                Shops::COL_SHOPS_UPDATED_BY => $userId,
                Shops::COL_SHOPS_UPDATED_AT => time(),
            ];
            if ($isReserve == self::IS_RESERVE) {
                $shopInsert[Shops::COL_SHOPS_IS_ACTIVE] = self::IS_INACTIVE;
            }
            if (!empty($phone)) {
                $shopInsert[Shops::COL_SHOP_PHONE] = $phone;
            }
            $shop = Shops::firstOrCreate(
                [
                    Shops::COL_SHOPS_SID => $sid,
                ],
                $shopInsert
            );

            $shopCredentials = [
                ShopCredential2::COL_FK_SHOP => $shop->shops_eid,
                ShopCredential2::COL_SHOP_CREDENTIAL_VALUE => json_encode($credential),
                ShopCredential2::COL_SHOP_CREDENTIAL_TYPE => ShopCredential2::TYPE_SELLERCENTER,
                ShopCredential2::COL_SHOP_CREDENTIAL_CREATED_AT => time(),
                ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT => time(),
            ];
            if ($cookies) {
                $shopCredentials[ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN] = $cookies;
                $shopCredentials[ShopCredential2::COL_SHOP_CREDENTIAL_STATE] = ShopCredential2::STATE_SUCCESS;
            } else {
                $shopCredentials[ShopCredential2::COL_SHOP_CREDENTIAL_STATE] = ShopCredential2::STATE_NOT_LOGIN;
            }
            $shopCredentialsId = ShopCredential2::query()->updateOrCreate(
                [
                    ShopCredential2::COL_FK_SHOP => $shop->shops_eid,
                    ShopCredential2::COL_SHOP_CREDENTIAL_TYPE => ShopCredential2::TYPE_SELLERCENTER,
                ],
                $shopCredentials
            );
            $idAfterInsert = ['shop_eid' => $shop->shops_eid, 'shop_credentials_id' => $shopCredentialsId->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}];
            DB::commit();
            Cache::tags(CacheTag::SHOP)->flush(); // when create new shop, flush this cache
    
            return $idAfterInsert;
        };

        $result = null;
        if ($withoutTransaction) {
            $result = call_user_func($anonymous);
        } else {
            $result = DB::transaction($anonymous);
        }

        return $result;
    }

    public function saveCredential($shop_eid, $credential, $type, $cookie) {
        $shopCredential = [
            ShopCredential2::COL_FK_SHOP => $shop_eid,
            ShopCredential2::COL_SHOP_CREDENTIAL_VALUE => json_encode($credential),
            ShopCredential2::COL_SHOP_CREDENTIAL_TYPE => $type,
            ShopCredential2::COL_SHOP_CREDENTIAL_CREATED_AT => time(),
            ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT => time(),
            ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN => $cookie,
            ShopCredential2::COL_SHOP_CREDENTIAL_STATE => ShopCredential2::STATE_SUCCESS,
        ];

        ShopCredential2::query()->insert($shopCredential);
    }

    /**
     * @param $sid
     * @return \Illuminate\Database\Concerns\BuildsQueries|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|mixed|object|null
     */
    public function getBySid($sid, $isActive = null)
    {
        $model = $this->_model->newQuery()->where(
            Shops::COL_SHOPS_SID,
            $sid
        );
        if ($isActive) {
            $model->where(Shops::COL_SHOPS_IS_ACTIVE, self::IS_ACTIVE);
        }

        return $model->first();
    }

    /**
     * @param $phone
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getCredentialShopByPhone($phone)
    {
        $timeUpdate = time() - OTP2::TIME_REFRESH_COOKIE_SHOPEE;
        $model = $this->_model->newQuery()->join(
            ShopCredential2::TABLE_NAME,
            ShopCredential2::COL_FK_SHOP,
            Shops::COL_SHOPS_EID
        )
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                Shops::COL_FK_CHANNEL
            )
            ->join(
                Countries::TABLE_NAME,
                Channels::COL_FK_COUNTRY,
                Countries::COL_COUNTRIES_ID
            )
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_STATE, ShopCredential2::STATE_NEED_OTP)
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_TYPE, ShopCredential2::TYPE_SELLERCENTER)
            ->where(ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC, '>=', 0)
            ->where(Shops::COL_SHOP_PHONE, $phone)
            ->orWhere(function ($q) use ($phone, $timeUpdate) {
                $q->where(ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT, '<=', $timeUpdate)
                    ->where(Shops::COL_SHOP_PHONE, $phone);
            })->where(Shops::COL_SHOPS_IS_ACTIVE, self::IS_ACTIVE);

        return $model->get();
    }

    /**
     * @param null $shopEid
     * @param null $shopName
     * @param null $channelCode
     * @param null $isActive
     * @param QuerySorter|null $sorter
     * @param QueryPaginator|null $paginator
     * @return QueryPaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getListShop($shopEid = null, $shopName = null, $channelCode = null, $countryCode = null, $isActive = null,
                                QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $model = $this->_model->newQuery()
            ->join(
                ShopUser::TABLE_NAME,
                ShopUser::COL_FK_SHOP,
                '=',
                Shops::COL_SHOPS_EID
            )
            ->join(
                SubscriptionDetails::TABLE_NAME,
                SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
                '=',
                ShopUser::COL_FK_SUBSCRIPTION_DETAILS
            )
            ->join(
                Features::TABLE_NAME,
                Features::COL_FEATURES_ID,
                '=',
                SubscriptionDetails::COL_FK_FEATURES
            )
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                '=',
                sprintf('%s.%s', Shops::TABLE_NAME, Shops::COL_FK_CHANNEL)
            )
            ->join(
                Countries::TABLE_NAME,
                Countries::COL_COUNTRIES_ID,
                '=',
                Channels::COL_FK_COUNTRY
            )
            ->select(
                Shops::COL_SHOPS_EID,
                Shops::COL_SHOPS_NAME,
                Shops::COL_SHOPS_SID,
                Shops::COL_SHOPS_IS_ACTIVE,
                Shops::COL_SHOP_ALLOWED_PULL,
                Shops::COL_SHOP_ALLOWED_PUSH,
                Shops::COL_SHOPS_STATES,
                Shops::COL_SHOPS_CREATED_AT,
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_NAME,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_EXCHANGE,
                DB::raw('GROUP_CONCAT( CONCAT(' . Features::COL_FEATURES_CODE . ',"*", ' . Features::COL_FEATURES_NAME . ',"*", ' . ShopUser::COL_SHOP_USER_STATE . ') ) AS ' . Features::COL_FEATURES_NAME)
            )
            ->where(Shops::COL_SHOPS_IS_RESERVE, self::IS_UN_RESERVE)
            ->where(ShopUser::COL_SHOP_USER_ROLE, ShopUser::ROLE_ADMIN)
            ->groupBy(Shops::COL_SHOPS_EID);
        if ($shopEid) {
            $model->where(Shops::COL_SHOPS_EID, $shopEid);
        }
        if ($shopName) {
            $model->where(Shops::COL_SHOPS_NAME, 'like', '%' . $shopName . '%');
        }
        if ($channelCode) {
            $model->where(Channels::COL_CHANNELS_CODE, $channelCode);
        }
        if ($countryCode) {
            $model->where(Countries::COL_COUNTRIES_CODE, $countryCode);
        }
        if ($isActive != null) {
            $model->where(Shops::COL_SHOPS_IS_ACTIVE, $isActive);
        }
        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }

        if (!$paginator) {
            return $model->get();
        }

        $paginator->applyQuery($model);

        return $paginator;
    }

    /**
     * @param $shopEid
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getInfoShop($shopEid, $userId = null)
    {
        $model = $this->_model->newQuery()
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                Shops::COL_FK_CHANNEL
            )
            ->join(
                Countries::TABLE_NAME,
                Channels::COL_FK_COUNTRY,
                Countries::COL_COUNTRIES_ID
            )
            ->select(
                Shops::TABLE_NAME . '.*',
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_NAME,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_EXCHANGE,
            )->where(Shops::COL_SHOPS_EID, $shopEid)
            ->where(Shops::COL_SHOPS_IS_RESERVE, self::IS_UN_RESERVE);
        if (!empty($userId)) {
            $model->join(
                ShopUser::TABLE_NAME,
                ShopUser::COL_FK_SHOP,
                Shops::COL_SHOPS_EID
            )->where(ShopUser::COL_FK_USER, $userId);
        }

        return $model->first();
    }

    public function getShopByShopEid($shopEid)
    {
        return $this->_model->newQuery()
            ->join(
                Channels::TABLE_NAME,
                Channels::COL_CHANNELS_ID,
                Shops::COL_FK_CHANNEL
            )
            ->join(
                Countries::TABLE_NAME,
                Channels::COL_FK_COUNTRY,
                Countries::COL_COUNTRIES_ID
            )
            ->select(
                Shops::TABLE_NAME . '.*',
                Channels::COL_CHANNELS_ID,
                Channels::COL_CHANNELS_CODE,
                Channels::COL_CHANNELS_NAME,
                Channels::COL_CHANNELS_CONFIG,
                Countries::COL_COUNTRIES_ID,
                Countries::COL_COUNTRIES_CODE,
                Countries::COL_COUNTRIES_NAME,
                Countries::COL_COUNTRIES_TIMEZONE,
                Countries::COL_COUNTRIES_FORMAT_RIGHT,
                Countries::COL_COUNTRIES_EXCHANGE,
            )->where(Shops::COL_SHOPS_EID, $shopEid)->first();
    }

    public function updateChildStates($shopEid, $shopState, $arrShopUserId, $childStates)
    {
        $response = null;
        try {
            DB::beginTransaction();
            $shopUserRepository = new ShopUserRepository();
            $updateChildState = $shopUserRepository->updateShopUserState($arrShopUserId, $childStates);
            if ($updateChildState) {
                $response = $this->update(
                    $shopEid,
                    [
                        Shops::COL_SHOPS_STATES => $shopState,
                    ]
                );
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
        }

        return $response;
    }
}
