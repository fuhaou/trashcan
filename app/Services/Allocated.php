<?php

namespace App\Services;

use App\Library\ELogger;
use App\Models\Sql\Companies;
use App\Models\Sql\Features;
use App\Models\Sql\ShopUser;
use App\Repositories\Sql\AclRepository;
use App\Repositories\Sql\CompanyUserRepository;
use App\Repositories\Sql\PartnershipRepository;
use App\Repositories\Sql\ShopUserRepository;
use App\Repositories\Sql\SubscriptionDetailsRepository;
use App\Repositories\Sql\UserRepository;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;

class Allocated
{
    use CommonTrait;
    const CODE_ALLOCATE_SUCCESS = 2;
    const CODE_REMOVE_ALLOCATE_SUCCESS = 3;
    const CODE_ERROR_ALLOCATE = 0;
    const CODE_ERROR_REMOVE_ALLOCATE = 1;
    const CODE_ERROR_USER_IS_NOT_EXIST = 4;
    const CODE_ERROR_NOT_ADMIN_CREATE_BY = 5;
    const CODE_ERROR_USER_NOT_IN_COMPANY_PARTNER = 6;
    const CODE_ERROR_USER_NOT_ALLOCATE_SHOP = 7;
    const CODE_ERROR_USER_IS_ADMIN_SHOP = 8;
    const CODE_ERROR_USER_HAS_ALLOCATE_SHOP = 9;
    const CODE_ERROR_FEATURE_NOT_EXIST = 10;

    private $userIds;
    private $shopEid;
    private $adminId;
    private $featureCode;
    private $subscriptionDetails;
    private $shopUserState;

    public function __construct($shopEid, $adminId, $userIds = [], $featureCode = [])
    {
        $this->setShopEid($shopEid);
        $this->setUserIds($userIds);
        $this->setAdminId($adminId);
        $this->setFeatureCode($featureCode);
    }

    private function checkFeatureCode()
    {
        $type = true;
        $feature = [];
        $shopUserRepo = new ShopUserRepository();
        $shopUser = $shopUserRepo->getShopUserInfo($this->getAdminId(), $this->getShopEid(), ShopUserRepository::ROLE_ADMIN, null, true, ShopUserRepository::ALLOCATED);
        foreach ($shopUser as $item) {
            $feature[$item->{Features::COL_FEATURES_CODE}] = $item->{ShopUser::COL_FK_SUBSCRIPTION_DETAILS};
            $this->shopUserState[$item->{Features::COL_FEATURES_CODE}] = $item->{ShopUser::COL_SHOP_USER_STATE};
        }
        foreach ($this->getFeatureCode() as $code) {
            if (! isset($feature[$code])) {
                $type = false;
                break;
            }
        }
        $this->subscriptionDetails = $feature;

        return $type;
    }

    private function checkUserExist()
    {
        $userRepo = new UserRepository();
        $users = $userRepo->getUserByUserId($this->getUserIds());
        if (count($users) == count($this->getUserIds())) {
            return true;
        }

        return false;
    }

    private function checkUserInCompanyPartner()
    {
        $companyUserRepository = new CompanyUserRepository();
        $companyUser = $companyUserRepository->search(null, $this->getAdminId());
        $listCompany = [];
        if (empty($companyUser->toArray())) {
            return false;
        }
        foreach ($companyUser as $value) {
            $companyId = $value->{Companies::COL_COMPANIES_ID};
            array_push($listCompany, $companyId);
        }
        if (isset($companyId)) {
            $partnershipRepository = new PartnershipRepository();
            $partnerInfo = $partnershipRepository->search($companyId);
            foreach ($partnerInfo as $value) {
                $companyId = $value->{Companies::COL_COMPANIES_ID};
                array_push($listCompany, $companyId);
            }
        }
        $companyMember = $companyUserRepository->getCompanyByUser($this->getUserIds());
        foreach ($companyMember as $companyId) {
            if (! in_array($companyId, $listCompany)) {
                return false;
            }
        }

        return true;
    }

    private function checkUserIsAdminShop()
    {
        $shopUserRepository = new ShopUserRepository();
        foreach ($this->getUserIds() as $userId) {
            $shopUser = $shopUserRepository->getShopUserInfo(
                $userId,
                $this->getShopEid(),
                ShopUserRepository::ROLE_ADMIN,
                null,
                true,
                ShopUserRepository::ALLOCATED,
                array_keys($this->subscriptionDetails)
            );
            foreach ($shopUser as $value) {
                if (in_array($value->{Features::COL_FEATURES_CODE}, $this->getFeatureCode())) {
                    return false;
                }
            }
        }

        return true;
    }

    private function checkUserHasAllocate()
    {
        $shopUserRepository = new ShopUserRepository();
        foreach ($this->getUserIds() as $userId) {
            $shopUser = $shopUserRepository->getShopUserInfo(
                $userId,
                $this->getShopEid(),
                ShopUserRepository::ROLE_MEMBER,
                $this->subscriptionDetails,
                true,
                ShopUserRepository::ALLOCATED,
                array_keys($this->subscriptionDetails)
            );
            foreach ($shopUser as $value) {
                if (in_array($value->{Features::COL_FEATURES_CODE}, $this->getFeatureCode())) {
                    return false;
                }
            }
        }

        return true;
    }

    private function checkAdminCreateBy($userCreateBy)
    {
        if ($userCreateBy == $this->getAdminId()) {
            return true;
        }

        return false;
    }

    private function setShopEid($shopEid)
    {
        $this->shopEid = $shopEid;
    }

    private function getShopEid()
    {
        return $this->shopEid;
    }

    private function setUserIds($userIds)
    {
        $this->userIds = $userIds;
    }

    private function getUserIds()
    {
        return $this->userIds;
    }

    private function setFeatureCode($featureCode)
    {
        $this->featureCode = $featureCode;
    }

    private function getFeatureCode()
    {
        return $this->featureCode;
    }

    private function setAdminId($adminId)
    {
        $this->adminId = $adminId;
    }

    private function getAdminId()
    {
        return $this->adminId;
    }

    public function save()
    {
        if (! $this->checkUserInCompanyPartner()) {
            return self::CODE_ERROR_USER_NOT_IN_COMPANY_PARTNER;
        }
        if (! $this->checkUserExist()) {
            return self::CODE_ERROR_USER_IS_NOT_EXIST;
        }
        if (! $this->checkFeatureCode()) {
            return self::CODE_ERROR_FEATURE_NOT_EXIST;
        }
        if (! $this->checkUserIsAdminShop()) {
            return self::CODE_ERROR_USER_IS_ADMIN_SHOP;
        }
        if (! $this->checkUserHasAllocate()) {
            return self::CODE_ERROR_USER_HAS_ALLOCATE_SHOP;
        }
        $subscriptionDetailIds = $this->subscriptionDetails;
        $type = self::CODE_ERROR_ALLOCATE;
        DB::beginTransaction();
        try {
            $shopUser = new ShopUserRepository();
            $dataInputs = [];
            foreach ($this->getUserIds() as $userId) {
                foreach ($this->getFeatureCode() as $code) {
                    $subCodeDetailsId = $subscriptionDetailIds[$code];
                    $shopUserMember = $shopUser->getShopUserInfo($userId, $this->getShopEid(), ShopUserRepository::ROLE_MEMBER, $subCodeDetailsId);
                    if (! empty($shopUserMember)) {
                        $shopUserMember->update([
                                ShopUser::COL_SHOP_USER_IS_ALLOCATED => ShopUserRepository::ALLOCATED,
                                ShopUser::COL_SHOP_USER_UPDATED_AT => time(),
                                ShopUser::COL_SHOP_USER_UPDATED_BY => $this->getAdminId(),
                            ]
                        );
                    } else {
                        ShopUser::insert([
                            ShopUser::COL_FK_SHOP => $this->getShopEid(),
                            ShopUser::COL_FK_USER => $userId,
                            ShopUser::COL_FK_SUBSCRIPTION_DETAILS => $subCodeDetailsId,
                            ShopUser::COL_SHOP_USER_STATE => $this->shopUserState[$code],
                            ShopUser::COL_SHOP_USER_IS_ALLOCATED => ShopUserRepository::ALLOCATED,
                            ShopUser::COL_SHOP_USER_ROLE => ShopUserRepository::ROLE_MEMBER,
                            ShopUser::COL_SHOP_USER_CREATED_AT => time(),
                            ShopUser::COL_SHOP_USER_CREATED_BY => $this->getAdminId(),
                        ]);
                    }

                    array_push($dataInputs, [
                        ShopUser::COL_FK_SHOP => $this->getShopEid(),
                        ShopUser::COL_FK_USER => $userId,
                        ShopUser::COL_FK_SUBSCRIPTION_DETAILS => $subCodeDetailsId,
                        ShopUser::COL_SHOP_USER_IS_ALLOCATED => ShopUserRepository::ALLOCATED,
                        ShopUser::COL_SHOP_USER_ROLE => ShopUserRepository::ROLE_MEMBER,
                        ShopUser::COL_SHOP_USER_CREATED_AT => time(),
                        ShopUser::COL_SHOP_USER_CREATED_BY => $this->getAdminId(),
                        ShopUser::COL_SHOP_USER_UPDATED_AT => time(),
                        ShopUser::COL_SHOP_USER_UPDATED_BY => $this->getAdminId(),
                    ]);
                }
            }
            DB::commit();
            $this->logInfo('Passport_Log_Allocated_Shop_User', $dataInputs);
            $type = self::CODE_ALLOCATE_SUCCESS;
        } catch (\Exception $e) {
            $this->logError('Passport_Log_Rollback_Allocated_Shop_User', $dataInputs ?? []);
            DB::rollback();
        }

        return $type;
    }

    public function remove()
    {
        if (! $this->checkUserExist()) {
            return self::CODE_ERROR_USER_IS_NOT_EXIST;
        }

        $type = self::CODE_ERROR_REMOVE_ALLOCATE;
        DB::beginTransaction();
        $dataLog = [];
        try {
            foreach ($this->getUserIds() as $userId) {
                $shopUser = new ShopUserRepository();
                $shopUser = $shopUser->getShopUserInfo($userId, $this->getShopEid(), ShopUserRepository::ROLE_MEMBER, null, true);
                if ($shopUser) {
                    $dataShopUserId = [];
                    foreach ($shopUser as $item) {
                        if ($this->checkAdminCreateBy($item->{ShopUser::COL_SHOP_USER_CREATED_BY})) {
                            $inputs = [
                                ShopUser::COL_SHOP_USER_IS_ALLOCATED => ShopUserRepository::NOT_ALLOCATED,
                                ShopUser::COL_SHOP_USER_UPDATED_AT => time(),
                                ShopUser::COL_SHOP_USER_UPDATED_BY => $this->getAdminId(),
                            ];
                            $item->update($inputs);
                            array_push($dataLog,
                                [
                                    ShopUser::COL_SHOP_USER_ID => $item->{ShopUser::COL_SHOP_USER_ID},
                                    ShopUser::COL_FK_USER => $userId,
                                    ShopUser::COL_FK_SHOP => $this->getShopEid(),
                                ]
                            );
                            array_push($dataShopUserId, $item->{ShopUser::COL_SHOP_USER_ID});
                            $type = self::CODE_REMOVE_ALLOCATE_SUCCESS;
                        } else {
                            $type = self::CODE_ERROR_NOT_ADMIN_CREATE_BY;
                            DB::rollback();
                            break;
                        }
                    }
                    if ($type == self::CODE_REMOVE_ALLOCATE_SUCCESS) {
                        $aclRepository = new AclRepository();
                        $aclRepository->deleteAcl($dataShopUserId);
                        DB::commit();
                        $this->logInfo('Passport_Log_Remove_Allocated_Shop_User', $dataLog);
                    }
                } else {
                    $type = self::CODE_ERROR_USER_NOT_ALLOCATE_SHOP;
                    DB::rollback();
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
        }

        return $type;
    }

    public function getMessager($code)
    {
        $message = [
            self::CODE_ALLOCATE_SUCCESS => 'Member added shop success.',
            self::CODE_REMOVE_ALLOCATE_SUCCESS => 'Member removed shop success.',
            self::CODE_ERROR_ALLOCATE => 'Member added shop error. Please try again later.',
            self::CODE_ERROR_REMOVE_ALLOCATE => 'Member removed shop error. Please try again later.',
            self::CODE_ERROR_USER_IS_NOT_EXIST => 'User not exist. Please try again later.',
            self::CODE_ERROR_NOT_ADMIN_CREATE_BY => 'Failed to remove the member. Only can remove the member that under the same admin.',
            self::CODE_ERROR_USER_NOT_IN_COMPANY_PARTNER => 'User not in company partner. Please try again later.',
            self::CODE_ERROR_USER_NOT_ALLOCATE_SHOP => 'User not allocate shop. Please try again later.',
            self::CODE_ERROR_USER_IS_ADMIN_SHOP => 'User is admin shop. Please try again later.',
            self::CODE_ERROR_USER_HAS_ALLOCATE_SHOP => 'User has allocate shop. Please try again later.',
            self::CODE_ERROR_FEATURE_NOT_EXIST => 'Some features you want to allocate to members that are not on the list are implemented. Please try again later.',
        ];

        return $message[$code];
    }
}
