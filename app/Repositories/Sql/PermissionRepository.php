<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 10/12/2020
 * Time: 09:59.
 */

namespace App\Repositories\Sql;

use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use App\Models\Sql\Features;
use App\Models\Sql\Permission;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class PermissionRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Permission::class;
    }

    public function search($permissionCode = null, $featureId = null)
    {
        $inherit = DB::table(Permission::TABLE_NAME, 'p1')
            ->select(Permission::COL_PERMISSION_CODE)
            ->whereRaw('permission.permission_inherit = p1.permission_id');
        $permissionCode = $permissionCode ? trim($permissionCode) : null;
        $featureId = is_numeric($featureId) ? [intval($featureId)] : $featureId;
        $model = DB::table(Permission::TABLE_NAME, 'permission')->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            Permission::COL_FK_FEATURE
        )->select(
            [
                Permission::TABLE_NAME.'.*',
                Features::TABLE_NAME.'.*',
            ]
        )->selectSub(
            $inherit, 'permission_inherit_code'
        );
        if ($permissionCode) {
            $model->where(Permission::COL_PERMISSION_CODE, $permissionCode);
        }
        if ($featureId) {
            $model->whereIn(Features::COL_FEATURES_ID, $featureId);
        }

        return $model->get();
    }

    /**
     * @param $shopEid
     * @param $userId
     * @param null $permissionCode
     * @param null $featureId
     * @param QuerySorter|null $sorter
     * @param QueryPaginator|null $paginator
     * @return QueryPaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function getByShopUser($shopEid, $userId, $permissionCode=null, $featureId=null, QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $inherit = DB::table(Permission::TABLE_NAME, 'p1')
            ->select(Permission::COL_PERMISSION_CODE)
            ->whereRaw('permission.permission_inherit = p1.permission_id');
        $permissionCode = $permissionCode ? trim($permissionCode) : null;
        $featureId = is_numeric($featureId) ? [intval($featureId)] : $featureId;
        $model = $this->_model->newQuery()->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            Permission::COL_FK_FEATURE
        )->join(
            SubscriptionDetails::TABLE_NAME,
            SubscriptionDetails::COL_FK_FEATURES,
            '=',
            Features::COL_FEATURES_ID
        )->join(
            ShopUser::TABLE_NAME,
            ShopUser::COL_FK_SUBSCRIPTION_DETAILS,
            '=',
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID
        )->select(
            [
                Permission::TABLE_NAME.'.*',
                Features::TABLE_NAME.'.*',
            ]
        )->selectSub(
            $inherit, 'permission_inherit_code'
        )->where(
            [
                ShopUser::COL_FK_SHOP => $shopEid,
                ShopUser::COL_FK_USER => $userId,
                ShopUser::COL_SHOP_USER_IS_ALLOCATED => ShopUserRepository::ALLOCATED,
            ]
        )->groupBy(Permission::COL_PERMISSION_ID);

        if ($permissionCode) {
            $model->where(Permission::COL_PERMISSION_CODE, $permissionCode);
        }

        if ($featureId) {
            $model->whereIn(Features::COL_FEATURES_ID, $featureId);
        }

        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }

        if (! $paginator) {
            return $model->get();
        }

        $paginator->applyQuery($model);

        return $paginator;
    }

    public function searchForModerator($permissionCode = null, $featureId = null)
    {
        $inherit = DB::table(Permission::TABLE_NAME, 'p1')
            ->select(Permission::COL_PERMISSION_CODE)
            ->whereRaw('permission.permission_inherit = p1.permission_id');
        $permissionCode = $permissionCode ? trim($permissionCode) : null;
        $featureId = is_numeric($featureId) ? [intval($featureId)] : $featureId;
        $model = DB::table(Permission::TABLE_NAME, 'permission')->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            Permission::COL_FK_FEATURE
        )->select(
            [
                Permission::TABLE_NAME.'.*',
                Features::TABLE_NAME.'.*',
            ]
        )->selectSub(
            $inherit, 'permission_inherit_code'
        )->where(Permission::TABLE_NAME.'.'.Permission::COL_PERMISSION_CODE, 'like', '%'.'_VIEW_'.'%');
        if ($permissionCode) {
            $model->where(Permission::TABLE_NAME.'.'.Permission::COL_PERMISSION_CODE, $permissionCode);
        }
        if ($featureId) {
            $model->whereIn(Features::COL_FEATURES_ID, $featureId);
        }

        return $model->get();
    }

    public function getByCodeAndFeature($permissionCode, $featureId)
    {
        $permissionCode = trim($permissionCode);
        $model = $this->_model->newQuery()->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            Permission::COL_FK_FEATURE
        )->where(
            [
                Permission::COL_FK_FEATURE => $featureId,
                Permission::COL_PERMISSION_CODE => $permissionCode,
            ]
        );

        return $model->first();
    }
}
