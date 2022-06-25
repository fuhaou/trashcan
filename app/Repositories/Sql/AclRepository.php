<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 08/12/2020
 * Time: 15:01.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Acl;
use App\Models\Sql\Features;
use App\Models\Sql\Permission;
use App\Models\Sql\ShopUser;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class AclRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Acl::class;
    }

    public function search($shopUserId = null, $permissionId = null, $featureCode = null)
    {
        $inherit = DB::table(Permission::TABLE_NAME, 'p1')
            ->select(Permission::COL_PERMISSION_CODE)
            ->whereRaw('permission.permission_inherit = p1.permission_id');
        $shopUserId = is_numeric($shopUserId) ? [intval($shopUserId)] : $shopUserId;
        $featureCode = is_string($featureCode) ? [trim($featureCode)] : $featureCode;
        $model = $this->_model->newQuery()->join(
            Permission::TABLE_NAME,
            Permission::COL_PERMISSION_ID,
            '=',
            Acl::COL_FK_PERMISSION
        )->join(
            Features::TABLE_NAME,
            Features::COL_FEATURES_ID,
            '=',
            Permission::COL_FK_FEATURE
        )->join(
            ShopUser::TABLE_NAME,
            ShopUser::COL_SHOP_USER_ID,
            '=',
            Acl::COL_FK_SHOP_USER
        )->select(
            [
                Permission::TABLE_NAME.'.*',
                Features::TABLE_NAME.'.*',
                Acl::TABLE_NAME.'.*',
                ShopUser::TABLE_NAME.'.*',
            ]
        )->selectSub(
            $inherit, 'permission_inherit_code'
        );
        if ($shopUserId) {
            $model->whereIn(ShopUser::COL_SHOP_USER_ID, $shopUserId);
        }
        if ($permissionId) {
            $model->where(Permission::COL_PERMISSION_ID, $permissionId);
        }
        if ($featureCode) {
            $model->whereIn(Features::COL_FEATURES_CODE, $featureCode);
        }

        return $model->get();
    }

    /**
     * @param array $arrShopUserId
     * @param array $aclInsert
     * @return bool
     */
    public function modifyAcl(array $arrShopUserId, array $aclInsert): bool
    {
        try {
            DB::beginTransaction();
            $this->_model->newQuery()->whereIn(Acl::COL_FK_SHOP_USER, $arrShopUserId)->delete();
            $this->_model->newQuery()->insert($aclInsert);
            DB::commit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * @param array $arrShopUserId
     * @return mixed
     */
    public function deleteAcl(array $arrShopUserId)
    {
        return $this->_model->newQuery()->whereIn(Acl::COL_FK_SHOP_USER, $arrShopUserId)->delete();
    }
}
