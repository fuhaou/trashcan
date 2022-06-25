<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 08/12/2020
 * Time: 14:20.
 */

namespace App\Services;

use App\Helper\Arrays;
use App\Models\Sql\Features;
use App\Models\Sql\Permission;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Repositories\Sql\AclRepository;
use App\Repositories\Sql\PermissionRepository;
use App\Repositories\Sql\ShopUserRepository;
use App\Traits\CommonTrait;

class Acl
{
    use CommonTrait;
    protected $userId;

    protected $shopEid = null;

    protected $featureCode = null;

    /**
     * @param $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param $shopId
     */
    public function setShopEid($shopId)
    {
        $this->shopEid = $shopId;
    }

    /**
     * @param $featureCode
     */
    public function setFeatureCode($featureCode)
    {
        $this->featureCode = $featureCode;
    }

    /**
     * @param false $allShop
     * @return array|false
     */
    public function getAcl($allShop = false)
    {
        if (! $this->userId) {
            return false;
        }
        $shopUserRepository = new ShopUserRepository();
        $shopUserInfo = $shopUserRepository->search($this->shopEid, $this->userId, null, $this->featureCode);
        if (empty($shopUserInfo)) {
            return false;
        }
        $assocShopEid = Arrays::buildArrayGroupBy(Shops::COL_SHOPS_EID, $shopUserInfo->toArray());
        $acl = [];
        foreach ($assocShopEid as $shopEid => $shopUserInfos) {
            $acl[$shopEid] = [];
            foreach ($shopUserInfos as $shopUserInfo) {
                $shopUserRole = $shopUserInfo[ShopUser::COL_SHOP_USER_ROLE];
                $featureCode = $shopUserInfo[Features::COL_FEATURES_CODE];
                $arrShopUserId = array_column($shopUserInfos, ShopUser::COL_SHOP_USER_ID);
                switch ($shopUserRole) {
                    case ShopUserRepository::ROLE_MEMBER:
                        $aclRepository = new AclRepository();
                        $listAcl = $aclRepository->search($arrShopUserId, null, $featureCode);
                        break;
                    case ShopUserRepository::ROLE_ADMIN:
                        $shopUserFeatureId = $shopUserInfo[Features::COL_FEATURES_ID];
                        $permissionRepository = new PermissionRepository();
                        $listAcl = $permissionRepository->search(null, $shopUserFeatureId);
                        break;
                }
                if (! $allShop) {
                    $currAcl = isset($listAcl) ? $this->buildResponse(Arrays::stdClassToArray($listAcl->toArray())) : [];
                } else {
                    $currAcl = isset($listAcl) ? $this->buildResponse(Arrays::stdClassToArray($listAcl->toArray()), true) : [];
                }
                $acl[$shopEid][$featureCode] = $currAcl;
            }
        }

        return $acl;
    }

    public function buildResponse($data, $allShop = false): array
    {
        if ($allShop) {
            $mappingKey = [
                'permission_code' => 'permission_code',
            ];
        } else {
            $mappingKey = [
                'permission_id' => 'permission_id',
                'permission_name' => 'permission_name',
                'permission_code' => 'permission_code',
                'features_name' => 'features_name',
                'features_code' => 'features_code',
                'fk_feature' => 'fk_feature',
                'permission_inherit_code' => 'permission_inherit_code',
            ];
        }
        $allShopData = [];
        foreach ($data as $index => $listAcl) {
            foreach ($listAcl as $key => $value) {
                if (array_key_exists($key, $mappingKey)) {
                    if ($allShop) {
                        array_push($allShopData, $value);
                    } else {
                        $data[$index][$mappingKey[$key]] = $value;
                    }
                } else {
                    unset($data[$index][$key]);
                }
            }
        }

        return count($allShopData) > 0 ? $allShopData : $data;
    }

    public function modifyAcl($listAcl)
    {
        $aclRepository = new AclRepository();
        foreach ($listAcl as $userId => $arrPermissionCode) {
            $shopUserRepository = new ShopUserRepository();
            $shopUserList = $shopUserRepository->search($this->shopEid, $userId, null, null, ShopUserRepository::ROLE_MEMBER);
            if (empty($shopUserList->toArray())) {
                $this->logInfo('Member have not allocate in shop', [
                    'shop_eid' => $this->shopEid,
                    'member' => $userId,
                ]);

                return 'Member have not allocate in shop';
            }
            if (count($arrPermissionCode) == 0) {
                $deleted = $aclRepository->deleteAcl(array_column($shopUserList->toArray(), ShopUser::COL_SHOP_USER_ID));
                if ($deleted) {
                    return true;
                } else {
                    $this->logError('Can not delete all acl of member', [
                        'member' => array_column($shopUserList->toArray(), ShopUser::COL_SHOP_USER_ID),
                    ]);

                    return 'An error occurred, please try again';
                }
            }
            $notInFeature = 0;
            foreach ($shopUserList as $shopUser) {
                $isAllocated = $shopUser->{ShopUser::COL_SHOP_USER_IS_ALLOCATED};
                if ($isAllocated == ShopUserRepository::ALLOCATED) {
                    $shopUserId = $shopUser->{ShopUser::COL_SHOP_USER_ID};
                    $shopUserFeatureId = $shopUser->{Features::COL_FEATURES_ID};
                    $shopUserHasAcl[] = $shopUserId;
                    foreach ($arrPermissionCode as $permissionCode) {
                        $permissionRepository = new PermissionRepository();
                        $permissionInfo = $permissionRepository->getByCodeAndFeature($permissionCode, $shopUserFeatureId);
                        if (empty($permissionInfo)) {
                            $notInFeature++;
                        } else {
                            $aclInsert[] = [
                                \App\Models\Sql\Acl::COL_FK_SHOP_USER => $shopUserId,
                                \App\Models\Sql\Acl::COL_FK_PERMISSION => $permissionInfo->{Permission::COL_PERMISSION_ID},
                                \App\Models\Sql\Acl::COL_ACL_CREATED_BY => $this->userId,
                                \App\Models\Sql\Acl::COL_ACL_CREATED_AT => time(),
                            ];
                        }
                    }
                }
            }
            if (isset($aclInsert) && count($aclInsert) < count($arrPermissionCode)) {
                $this->logInfo('Some permission codes not in the feature of user access.', [
                    'shop_eid' => $this->shopEid,
                    'arr_permission_code' => $arrPermissionCode,
                ], 1,'ACL_MODIFY');

                return 'Some permission codes not in the feature of user access.';
            }
        }
        if (isset($shopUserHasAcl) && isset($aclInsert)) {
            return $aclRepository->modifyAcl($shopUserHasAcl, $aclInsert);
        }

        return 'An error occurred, please try again';
    }
}
