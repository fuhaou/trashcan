<?php

namespace App\Repositories\Sql;

use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Features;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use App\Models\Sql\Users;
use App\Repositories\BaseSqlRepository;

/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 19/11/2020
 * Time: 10:50.
 */
class ShopUserRepository extends BaseSqlRepository
{
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';

    const NOT_ALLOCATED = 0;
    const ALLOCATED = 1;

    const STATE_INIT = 'Init';
    const STATE_PULLED = 'Pulled';
    const STATE_GOOD = 'Good';
    const STATE_SYNCING = 'Syncing';
    const STATE_ERROR = 'Error';
    const STATE_WARNING = 'Warning';

    public function getModel()
    {
        return ShopUser::class;
    }

    /**
     * @param null $shopId
     * @param null $userId
     * @param null $subDetailsId
     * @param null $featureCode
     * @param null $role
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function search($shopId = null, $userId = null, $subDetailsId = null, $featureCode = null, $role = null)
    {
        $shopId = is_numeric($shopId) ? [intval($shopId)] : $shopId;
        $userId = is_numeric($userId) ? [intval($userId)] : $userId;
        $subDetailsId = is_numeric($subDetailsId) ? [intval($subDetailsId)] : $subDetailsId;
        $featureCode = is_string($featureCode) ? [$featureCode] : $featureCode;
        $role = $role ? trim($role) : null;
        $model = $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            Shops::COL_SHOPS_EID,
            '=',
            ShopUser::COL_FK_SHOP
        )->join(
            Users::TABLE_NAME,
            Users::COL_USERS_ID,
            '=',
            ShopUser::COL_FK_USER
        )->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
            '=',
            ShopUser::COL_FK_SUBSCRIPTION_DETAILS
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            SubscriptionDetails::COL_FK_FEATURES
        );
        if ($shopId) {
            $model->whereIn(Shops::COL_SHOPS_EID, $shopId);
        }
        if ($userId) {
            $model->whereIn(Users::COL_USERS_ID, $userId);
        }
        if ($subDetailsId) {
            $model->whereIn(SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID, $subDetailsId);
        }
        if ($featureCode) {
            $model->whereIn(Features::COL_FEATURES_CODE, $featureCode);
        }
        if ($role) {
            $model->where(ShopUser::COL_SHOP_USER_ROLE, $role);
        }

        return $model->get();
    }

    public function getByShopUserRole($shopEid, $userId, $role)
    {
        $shopEid = intval($shopEid);
        $userId = intval($userId);
        $role = trim($role);
        $model = $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            Shops::COL_SHOPS_EID,
            '=',
            ShopUser::COL_FK_SHOP
        )->join(
            Users::TABLE_NAME,
            Users::COL_USERS_ID,
            '=',
            ShopUser::COL_FK_USER
        )->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
            '=',
            ShopUser::COL_FK_SUBSCRIPTION_DETAILS
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            SubscriptionDetails::COL_FK_FEATURES
        )->where(
            [
                Shops::COL_SHOPS_EID => $shopEid,
                Users::COL_USERS_ID => $userId,
                ShopUser::COL_SHOP_USER_ROLE => $role,
            ]
        );

        return $model->first();
    }

    public function getShopUserInfo($userId = null, $ShopEid = null, $role = null, $subCodeDetailsId = null, $isGet = null, $isAllocate = null, $featureCode = null)
    {
        $featureCode = is_string($featureCode) ? [trim($featureCode)] : $featureCode;
        $subCodeDetailsId = is_numeric($subCodeDetailsId) ? [intval($subCodeDetailsId)] : $subCodeDetailsId;
        $model = $this->_model->newQuery()->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
            '=',
            ShopUser::COL_FK_SUBSCRIPTION_DETAILS
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            SubscriptionDetails::COL_FK_FEATURES
        );
        if (! empty($userId)) {
            $model->where(ShopUser::COL_FK_USER, $userId);
        }

        if (! empty($ShopEid)) {
            $model->where(ShopUser::COL_FK_SHOP, $ShopEid);
        }

        if (! empty($role)) {
            $model->where(ShopUser::COL_SHOP_USER_ROLE, $role);
        }

        if ($subCodeDetailsId) {
            $model->whereIn(ShopUser::COL_FK_SUBSCRIPTION_DETAILS, $subCodeDetailsId);
        }

        if ($featureCode) {
            $model->whereIn(Features::COL_FEATURES_CODE, $featureCode);
        }
        if (! empty($isAllocate)) {
            $model->where(ShopUser::COL_SHOP_USER_IS_ALLOCATED, $isAllocate);
        }

        if (! empty($isGet)) {
            return $model->get();
        }

        return $model->first();
    }

    public function getFeatureShop($shopEid, $search = null)
    {
        $model = $this->_model->newQuery()
            ->join(
                Users::TABLE_NAME,
                Users::COL_USERS_ID,
                ShopUser::COL_FK_USER
            )
            ->join(
                SubscriptionDetails::TABLE_NAME,
                SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
                ShopUser::COL_FK_SUBSCRIPTION_DETAILS
            )
            ->join(
                Features::TABLE_NAME,
                Features::COL_FEATURES_ID,
                SubscriptionDetails::COL_FK_FEATURES
            )
            ->join(
                CompanySubscriptionCode::TABLE_NAME,
                CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_ID,
                SubscriptionDetails::COL_FK_COMPANY_SUBSCRIPTION_CODE
            )
            ->where(ShopUser::COL_FK_SHOP, $shopEid)
            ->where(ShopUser::COL_SHOP_USER_IS_ALLOCATED, 1)
            ->where(Users::COL_USERS_IS_ACTIVE, 1)
            ->where(SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_IS_ACTIVE, 1)
            ->where(Features::COL_FEATURES_IS_ACTIVE, 1)
            ->where(CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_IS_ACTIVE, 1);
        if (! empty($search)) {
            $model->where(function ($query) use ($search) {
                $query->orWhere(Users::COL_USERS_FIRST_NAME, 'like', '%'.$search.'%')
                    ->orWhere(Users::COL_USERS_LAST_NAME, 'like', '%'.$search.'%')
                    ->orWhere(Users::COL_USERS_EMAIL, 'like', '%'.$search.'%');
            });
        }

        return $model->get();
    }

    public function searchShopUserAllocated($shopEid = null, $userId = null, $firstName = null, $lastName = null, $email = null, $isAllocate = null, $isActive = null)
    {
        $shopEid = is_numeric($shopEid) ? [intval($shopEid)] : $shopEid;
        $userId = is_numeric($userId) ? [intval($userId)] : $userId;
        $firstName = $firstName ? trim($firstName) : null;
        $lastName = $lastName ? trim($lastName) : null;
        $email = $email ? trim($email) : null;
        $model = $this->_model->newQuery()->join(
            Shops::TABLE_NAME,
            Shops::COL_SHOPS_EID,
            '=',
            ShopUser::COL_FK_SHOP
        )->join(
            Users::TABLE_NAME,
            Users::COL_USERS_ID,
            '=',
            ShopUser::COL_FK_USER
        )->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
            '=',
            ShopUser::COL_FK_SUBSCRIPTION_DETAILS
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            SubscriptionDetails::COL_FK_FEATURES
        );
        if ($shopEid) {
            $model->whereIn(Shops::COL_SHOPS_EID, $shopEid);
        }
        if ($userId) {
            $model->whereIn(Users::COL_USERS_ID, $userId);
        }
        if ($firstName) {
            $model->where(Users::COL_USERS_FIRST_NAME, 'like', '%'.$firstName.'%');
        }
        if ($lastName) {
            $model->where(Users::COL_USERS_LAST_NAME, 'like', '%'.$lastName.'%');
        }
        if ($email) {
            $model->where(Users::COL_USERS_EMAIL, 'like', '%'.$email.'%');
        }
        if ($isAllocate) {
            $model->where(ShopUser::COL_SHOP_USER_IS_ALLOCATED, $isAllocate);
        }
        if ($isActive != null) {
            $model->where(Users::COL_USERS_IS_ACTIVE, $isActive);
        }

        return $model->get();
    }

    public function updateShopUserState($arrShopUserId, $state)
    {
        return $this->_model->newQuery()->whereIn(ShopUser::COL_SHOP_USER_ID, $arrShopUserId)
            ->update([
                ShopUser::COL_SHOP_USER_STATE => $state,
            ]);
    }
}
