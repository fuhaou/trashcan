<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 17/12/2020
 * Time: 11:23.
 */

namespace App\Services;

use App\Helper\Arrays;
use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use App\Models\Sql\Channels;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Countries;
use App\Models\Sql\Features;
use App\Models\Sql\Permission;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Models\Sql\Users;
use App\Repositories\Sql\AclRepository;
use App\Repositories\Sql\PermissionRepository;
use App\Repositories\Sql\ShopRepository;
use App\Repositories\Sql\ShopUserRepository;
use App\Repositories\Sql\UserRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModeratorUser
{
    use ApiResponse;

    public function getListShopByUser($request)
    {
        $countryCode = $request->input('country_code', null);
        $channelCode = $request->input('channel_code', null);
        $featureCode = $request->input('feature_code', null);
        $shopName = $request->input('shop_name', null);
        if (! empty($countryCode)) {
            $countryCode = explode(',', $countryCode);
        }
        if (! empty($channelCode)) {
            $channelCode = explode(',', $channelCode);
        }
        if (! empty($featureCode)) {
            $featureCode = explode(',', $featureCode);
        }
        $shopRepository = new ShopRepository();
        $sorter = QuerySorter::withRequest($request);
        $sorter->addOrderBy(Shops::COL_SHOPS_EID, QuerySorter::DESC);
        $paginator = QueryPaginator::withRequest($request);
        $pager = $shopRepository->searchListShopByUser(null, $countryCode, $channelCode, $featureCode, $sorter, $paginator, $shopName);
        $data = $pager->getData();
        $userRepository = new UserRepository();
        $users = $userRepository->getAll();
        foreach ($users as $user) {
            $firstName = $user->{Users::COL_USERS_FIRST_NAME};
            $lastName = $user->{Users::COL_USERS_LAST_NAME};
            $dataUser[$user->{Users::COL_USERS_ID}] = $firstName.' '.$lastName;
            if (! $firstName) {
                $dataUser[$user->{Users::COL_USERS_ID}] = $lastName ? $lastName : '';
            }
            if (! $lastName) {
                $dataUser[$user->{Users::COL_USERS_ID}] = $firstName ? $firstName : '';
            }
        }
        foreach ($data as $item) {
            $createdBy = $item->{Shops::COL_SHOPS_CREATED_BY};
            $updatedBy = $item->{Shops::COL_SHOPS_UPDATED_BY};
            $features = $item->{Features::COL_FEATURES_NAME};
            $item->{Shops::COL_SHOPS_CREATED_BY} = isset($dataUser[$createdBy]) ? $dataUser[$createdBy] : '';
            $item->{Shops::COL_SHOPS_UPDATED_BY} = isset($dataUser[$updatedBy]) ? $dataUser[$updatedBy] : '';
            if (! empty($features)) {
                $features = explode(',', $features);
                $arr = [];
                foreach ($features as $feature) {
                    $temp = explode('*', $feature);
                    array_push($arr, [
                        Features::COL_FEATURES_CODE => $temp[0],
                        Features::COL_FEATURES_NAME => $temp[1],
                    ]);
                }
                $item->features = $arr;
            }
        }

        return $this->successWithPaginator($pager);
    }

    public function getListAcl($request)
    {
        $validator = Validator::make($request->all(), [
            'list_shop_eid' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorWithValidator($validator);
        }
        $listFeatureCode = $request->input('list_feature_code', null);
        $arrFeatureCode = $listFeatureCode ? explode(',', $listFeatureCode) : null;
        $listShopEid = $request->input('list_shop_eid', null);
        $arrShopEid = explode(',', $listShopEid);
        if (count($arrShopEid) > 20) {
            $this->error('Api only supports up to 20 shop per request.');
        }
        $shopUserRepository = new ShopUserRepository();
        $shopUserInfo = $shopUserRepository->search($arrShopEid, null, null, $arrFeatureCode, ShopUserRepository::ROLE_ADMIN);
        $assocShopEid = Arrays::buildArrayGroupBy(Shops::COL_SHOPS_EID, $shopUserInfo->toArray());
        $acl = [];
        foreach ($assocShopEid as $shopEid => $shopUserInfos) {
            $acl[$shopEid] = [];
            foreach ($shopUserInfos as $shopUserInfo) {
                $shopUserFeatureId = $shopUserInfo[Features::COL_FEATURES_ID];
                $featureCode = $shopUserInfo[Features::COL_FEATURES_CODE];
                $permissionRepository = new PermissionRepository();
                $listAcl = $permissionRepository->searchForModerator(null, $shopUserFeatureId);
                $aclService = new Acl();
                $arrAcl = Arrays::stdClassToArray($listAcl->toArray());
                $acl[$shopEid][$featureCode] = ! empty($arrAcl) ? $aclService->buildResponse($arrAcl) : [];
            }
        }

        return $this->success($acl);
    }

    public function getShopDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_eid' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->errorWithValidator($validator);
        }
        $shopEid = $request->input('shop_eid', null);
        $search = $request->input('search', null);
        $shopRepo = new ShopRepository();
        $shopInfo = $shopRepo->getInfoShop($shopEid);
        if ($shopInfo) {
            if ($shopInfo->{Shops::COL_SHOPS_IS_ACTIVE} == ShopRepository::IS_ACTIVE) {
                $userReposer = new UserRepository();
                $users = $userReposer->getAll();
                $dataUser = [];
                foreach ($users as $user) {
                    $dataUser[$user->{Users::COL_USERS_ID}] = $user->{Users::COL_USERS_FIRST_NAME}.' '.$user->{Users::COL_USERS_LAST_NAME};
                }
                $shopUser = new ShopUserRepository();
                $shopFeature = $shopUser->getFeatureShop($shopEid, $search);
                $dataFeatureName = [];
                $dataSubCodeName = [];
                $dataMember = [];
                $tempUser = [];
                $userPermission = [];
                foreach ($shopFeature as $item) {
                    $tempFeature = [];
                    if (! in_array($item->{ShopUser::COL_FK_USER}, $tempUser)) {
                        array_push($tempUser, $item->{ShopUser::COL_FK_USER});
                        $acl = new Acl();
                        $acl->setShopEid($shopEid);
                        $acl->setUserId($item->{ShopUser::COL_FK_USER});
                        $permissionData = $acl->getAcl();
                        if (! empty($permissionData)) {
                            foreach ($permissionData[$shopEid] as $code => $featurePermission) {
                                $tempPermission = [];
                                $temp = [];
                                foreach ($featurePermission as $permission) {
                                    if (! isset($userPermission[$item->{ShopUser::COL_FK_USER}][$permission[Permission::COL_FK_FEATURE]])) {
                                        $temp = [
                                            Features::COL_FEATURES_ID => $permission[Permission::COL_FK_FEATURE],
                                            Features::COL_FEATURES_NAME => $permission[Features::COL_FEATURES_NAME],
                                            Features::COL_FEATURES_CODE => $permission[Features::COL_FEATURES_CODE],
                                            ShopUser::COL_SHOP_USER_ROLE => $item->{ShopUser::COL_SHOP_USER_ROLE},
                                            'permission' => [],
                                        ];
                                    }
                                    array_push($tempPermission, [
                                        Permission::COL_PERMISSION_ID => $permission[Permission::COL_PERMISSION_ID],
                                        Permission::COL_PERMISSION_NAME => $permission[Permission::COL_PERMISSION_NAME],
                                        Permission::COL_PERMISSION_CODE => $permission[Permission::COL_PERMISSION_CODE],
                                    ]);
                                }
                                if (! empty($temp)) {
                                    $temp['permission'] = $tempPermission;
                                }
                                array_push($tempFeature, $temp);
                            }
                        }
                        array_push($dataMember, [
                            Users::COL_USERS_ID => $item->{Users::COL_USERS_ID},
                            Users::COL_USERS_FIRST_NAME => $item->{Users::COL_USERS_FIRST_NAME},
                            Users::COL_USERS_LAST_NAME => $item->{Users::COL_USERS_LAST_NAME},
                            Users::COL_USERS_EMAIL => $item->{Users::COL_USERS_EMAIL},
                            Features::COL_FEATURES_NAME => $tempFeature,
                        ]);
                    }
                    $dataFeatureName[$item->{Features::COL_FEATURES_ID}] = $item->{Features::COL_FEATURES_NAME};
                    $dataSubCodeName[$item->{CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_ID}] = $item->{CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_VALUE};
                }

                $data = [
                    Shops::COL_SHOPS_EID => $shopInfo->{Shops::COL_SHOPS_EID},
                    Shops::COL_SHOPS_NAME => $shopInfo->{Shops::COL_SHOPS_NAME},
                    Shops::COL_SHOPS_CREATED_BY => isset($dataUser[$shopInfo->{Shops::COL_SHOPS_CREATED_BY}]) ? $dataUser[$shopInfo->{Shops::COL_SHOPS_CREATED_BY}] : '',
                    Shops::COL_SHOPS_CREATED_AT => $shopInfo->{Shops::COL_SHOPS_CREATED_AT},
                    Shops::COL_SHOPS_UPDATED_BY => isset($dataUser[$shopInfo->{Shops::COL_SHOPS_UPDATED_BY}]) ? $dataUser[$shopInfo->{Shops::COL_SHOPS_UPDATED_BY}] : '',
                    Shops::COL_SHOPS_UPDATED_AT => $shopInfo->{Shops::COL_SHOPS_UPDATED_AT},
                    Countries::COL_COUNTRIES_NAME => $shopInfo->{Countries::COL_COUNTRIES_NAME},
                    Channels::COL_CHANNELS_NAME => $shopInfo->{Channels::COL_CHANNELS_NAME},
                    Features::COL_FEATURES_NAME => $dataFeatureName,
                    CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_VALUE => $dataSubCodeName,
                    'Membership' => $dataMember,
                ];

                return $this->success($data);
            } else {
                return $this->error('Shop inactive.');
            }
        }

        return $this->error('User not allocate shop.');
    }

    public function getForCheckAllShop()
    {
        $shopUserRepository = new ShopUserRepository();
        $shopUserInfo = $shopUserRepository->search(null, null, null, null, ShopUserRepository::ROLE_ADMIN);
        $assocShopEid = Arrays::buildArrayGroupBy(Shops::COL_SHOPS_EID, $shopUserInfo->toArray());
        $acl = [];
        foreach ($assocShopEid as $shopEid => $shopUserInfos) {
            $acl[$shopEid] = [];
            foreach ($shopUserInfos as $shopUserInfo) {
                $shopUserFeatureId = $shopUserInfo[Features::COL_FEATURES_ID];
                $featureCode = $shopUserInfo[Features::COL_FEATURES_CODE];
                $permissionRepository = new PermissionRepository();
                $listAcl = $permissionRepository->searchForModerator(null, $shopUserFeatureId);
                $aclService = new Acl();
                $arrAcl = Arrays::stdClassToArray($listAcl->toArray());
                $acl[$shopEid][$featureCode] = ! empty($arrAcl) ? $aclService->buildResponse($arrAcl, true) : [];
            }
        }


        return $this->success($acl);
    }
}
