<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 13/11/2020
 * Time: 09:55.
 */

namespace App\Services;

use App\Helper\Arrays;
use App\Library\ELogger;
use App\Models\Sql\Channels;
use App\Models\Sql\Companies;
use App\Models\Sql\CompanySubscriptionCode;
use App\Models\Sql\Countries;
use App\Models\Sql\Features;
use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Models\Sql\ShopUser;
use App\Models\Sql\SubscriptionDetails;
use App\Models\Sql\SubscriptionDetailsShop;
use App\Models\Sql\Users;
use App\Repositories\Sql\ChannelRepository;
use App\Repositories\Sql\ShopCredentialRepository2;
use App\Repositories\Sql\ShopRepository;
use App\Repositories\Sql\ShopUserRepository;
use App\Repositories\Sql\SubscriptionCodeRepository;
use App\Repositories\Sql\UserRepository;
use App\Traits\ApiResponse;
use App\Traits\CommonTrait;
use Epsilo\Auth\LazadaAuth;
use Epsilo\Auth\LazadaBaAuth;
use Epsilo\Auth\LazadaSolutionAuth;
use Epsilo\Auth\OpenApi\Lazada;
use Epsilo\Auth\OpenApi\Shopee;
use Epsilo\Auth\ShopeeAuth;
use Epsilo\Auth\ShopeeSolutionsAuth;
use Epsilo\Auth\ShopeeBrandPortalAuth;
use Epsilo\Auth\TokopediaAuth;
use Illuminate\Support\Facades\Crypt;

class ShopAuthentication
{
    use ApiResponse,CommonTrait;

    protected $userId;

    protected $username;

    protected $password;

    protected $channelCode;

    protected $siteType; // sellercenter, marketing, brandportal ...

    protected $countryCode;

    protected $type;

    protected $sid;

    protected $shopName = 'Untitled Shop';

    protected $arrShopSubAccount = [];

    protected $subscriptionCode;

    protected $groupSubscriptionDetailsValid = [];

    protected $redirectAuthUrl = '';

    protected $cookies;

    protected $sellerId;

    protected $phone;


    const TYPE_MAIN = 'main_account';
    const TYPE_SUB = 'sub_account';

    /**
     * @var ChannelRepository
     */
    private $channelRepository;

    /**
     * @var ShopRepository
     */
    private $shopRepository;

    /**
     * @var SubscriptionCodeRepository
     */
    private $subscriptionCodeRepository;

    /**
     * @var ShopUserRepository
     */
    private $shopUserRepository;

    /**
     * @var ShopCredentialRepository2
     */
    private $shopCredentialsRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getChannelCode()
    {
        return $this->channelCode;
    }

    /**
     * @param mixed $channelCode
     */
    public function setChannelCode($channelCode)
    {
        $channelCode = strtoupper($channelCode);
        $this->channelCode = $channelCode;
    }

    /**
     * @return mixed
     */
    public function getSiteType()
    {
        return $this->siteType;
    }

    /**
     * @param mixed $siteType
     */
    public function setSiteType($siteType)
    {
        $siteType = strtolower($siteType);
        $this->siteType = $siteType;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param mixed $country
     */
    public function setCountryCode($country)
    {
        $this->countryCode = $country;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getCookie()
    {
        return $this->cookies;
    }

    public function setSid($sid)
    {
        $this->sid = $sid;
    }

    public function getSid()
    {
        return $this->sid;
    }

    /**
     * @param $subscriptionCode
     */
    public function setSubscriptionCode($subscriptionCode)
    {
        $this->subscriptionCode = $subscriptionCode;
    }

    public function getRedirectAuthUrl()
    {
        return $this->redirectAuthUrl;
    }

    public function __construct()
    {
        $this->channelRepository = new ChannelRepository();
        $this->shopRepository = new ShopRepository();
        $this->subscriptionCodeRepository = new SubscriptionCodeRepository();
        $this->userRepository = new UserRepository();
        $this->shopUserRepository = new ShopUserRepository();
        // new shop_credential_2
        $this->shopCredentialsRepository = new ShopCredentialRepository2();
    }

    public function shopeeRelogin($cookie)
    {
        $rawAuthShopee = new ShopeeAuth();
        $response = $rawAuthShopee->secondLogin($cookie, $this->getPassword(), $this->getCountryCode());

        if (isset($response)) {
            return json_decode($response, true);
        } else {
            ELogger::error('The lib auth with sc not response.', [
                'channel' => 'SHOPEE',
                'country' => $this->countryCode,
                'cookie' => $cookie,
            ]);
            return $this->error('The lib auth with sc not response.');
        }
    }

    public function checkLoginAndDetermineType($otp = null)
    {
        $response= [];
        switch ($this->channelCode) {
            case 'LAZADA':
                if ($this->siteType == ShopCredential2::TYPE_MARKETING) {
                    $rawAuthLazada = new LazadaSolutionAuth();
                    $options = [
                        'api_domain_simulation' => config('passport.api_domain_simulation'),
                    ];
                    $response = $rawAuthLazada->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp, $options);
                } elseif ($this->siteType == ShopCredential2::TYPE_BRANDPORTAL) {
                    $rawAuthLazada = new LazadaBaAuth();
                    $options = [
                        'api_domain_simulation' => config('passport.api_domain_simulation'),
                        'link_shop' => false,
                    ];
                    $response = $rawAuthLazada->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp, $options);
                } else {
                    $rawAuthLazada = new LazadaAuth();
                    $options = [
                        'api_domain_simulation' => config('passport.api_domain_simulation'),
                        'link_shop' => true,
                    ];
                    $response = $rawAuthLazada->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp, $options);
                }
                break;
            case 'SHOPEE':
                if ($this->siteType == ShopCredential2::TYPE_MARKETING) {
                    $rawAuthShopee = new ShopeeSolutionsAuth();
                    $response = $rawAuthShopee->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp);
                } elseif ($this->siteType == ShopCredential2::TYPE_BRANDPORTAL) {
                    $rawAuthShopee = new ShopeeBrandPortalAuth();
                    $response = $rawAuthShopee->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp);
                } else {
                    $options = [
                        'shop_sid' => $this->sid
                    ];
                    $rawAuthShopee = new ShopeeAuth();
                    $response = $rawAuthShopee->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp, $options);
                }
                break;
            case 'TOKOPEDIA':
                $rawAuthToko = new TokopediaAuth();
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                    'link_shop' => true,
                ];
                $response = $rawAuthToko->auth($this->getUsername(), $this->getPassword(), $this->getCountryCode(), $otp, $options);
                break;
            default:
                break;
        }

        if (isset($response)) {
            $result = json_decode($response, true);
            if ($result['success'] == 1) {
                if ($this->channelCode == 'LAZADA') {
                    $this->type = self::TYPE_MAIN;
                } else {
                    $this->type = $result['account_type'];
                }
                $this->sid = $result['shop_sid'] ?? '';
                $this->shopName = $result['shop_name'] ?? '';
                $this->arrShopSubAccount = $result['shop_list'] ?? '';
                $this->cookies = $result['cookie_string'];
                $this->sellerId = $result['seller_id'] ?? null;
            } else {
                $this->logInfo('CheckLoginResponseFail', $response);
            }
            return $result;
        } else {
            $this->logError('TheLibAuthWithSCNotResponse.', [
                'user_id' => $this->userId,
                'channel' => $this->channelCode,
                'country' => $this->countryCode,
                'shop_account' => $this->getUsername(),
            ]);
            return $this->error('The lib auth with sc not response.');
        }
    }

    /**
     * only for Lazada brand portal + Lazada marketing solutions
     */
    public function checkAccountCookieValid($cookie, $acc) {
        if ($this->channelCode != 'LAZADA') {
            return false; // not suppported
        }
        switch ($this->siteType) {
            case ShopCredential2::TYPE_BRANDPORTAL:
                $rawAuthLazada = new LazadaBaAuth();
                $response = json_decode($rawAuthLazada->checkCookieValid($cookie, $this->getCountryCode()), true);
                if ($response['success'] == true) {
                    // if ($response['data']['data']['email'] == $acc) {
                        return true;
                    // }
                }
                break;
            case ShopCredential2::TYPE_MARKETING:
                $rawAuthLazada = new LazadaSolutionAuth();
                $response = json_decode($rawAuthLazada->checkCookieValid($cookie, $this->getCountryCode()), true);
                if ($response['success'] == true) {
                    $email = $response['data']['data']['email'] ?? null;
                    if (strtolower($email) == strtolower($acc)) {
                        return true;
                    }
                }
                break;
        }
        return false; // wrong or not supported
    }

    public function validateSubscriptionCode()
    {
        $companyUserInfo = $this->userRepository->getCompanyInfo($this->userId);
        if (empty($companyUserInfo->toArray())) {
            return 'Subscription code invalid for this shop.';
        }
        $subscriptionInfo = $this->subscriptionCodeRepository->search(
            $this->subscriptionCode,
            $companyUserInfo->{Companies::COL_COMPANIES_ID} ?? null,
            null,
            $this->channelCode,
            $this->countryCode
        );
        if (empty($subscriptionInfo->toArray())) {
            $this->logError('Subscription code invalid for this shop.',[
                $this->subscriptionCode,
                $companyUserInfo->{Companies::COL_COMPANIES_ID} ?? null,
                null,
                $this->channelCode,
                $this->countryCode]);
            return 'Subscription code invalid for this shop.';
        }
        $groupSubscriptionDetails = Arrays::buildArrayGroupBy(
            SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_ID,
            Arrays::stdClassToArray($subscriptionInfo)
        );

        $this->groupSubscriptionDetailsValid = $groupSubscriptionDetails;
        foreach ($groupSubscriptionDetails as $subscriptionDetailsId => $subscriptions) {
            foreach ($subscriptions as $value) {
                $featureName = $value[Features::COL_FEATURES_NAME];
                $channelCode = $value[Channels::COL_CHANNELS_CODE];
                $countryCode = $value[Countries::COL_COUNTRIES_CODE];
                $errorKey = $featureName.'-'.$channelCode.'-'.$countryCode;
                if ($value[SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_EXPIRE_TIME] < time()) {
                    unset($this->groupSubscriptionDetailsValid[$subscriptionDetailsId]);
                    $errorMessage[$errorKey][] = "Subscription Code no longer valid for $featureName. Please check again or try with another Subscription Code.";
                }
                $companyId = $value[CompanySubscriptionCode::COL_FK_COMPANY];
                $listUserUnderCompany = $this->userRepository->search($companyId);
                $arrUserUnderCompanyId = array_column(
                    Arrays::stdClassToArray($listUserUnderCompany),
                    Users::COL_USERS_ID
                );
                $shopUserInfo = $this->shopUserRepository->search(
                    null,
                    $arrUserUnderCompanyId,
                    $subscriptionDetailsId,
                    null,
                    ShopUserRepository::ROLE_ADMIN
                );
                $totalShopExisted = count($shopUserInfo);
                if ($value[SubscriptionDetails::COL_SUBSCRIPTION_DETAILS_QUOTA_SHOP] < $totalShopExisted) {
                    unset($this->groupSubscriptionDetailsValid[$subscriptionDetailsId]);
                    $errorMessage[$errorKey][] = "Reached link shop limit on the feature $featureName of the Subscription code. Please try with another code.";
                }
                $sid = $value[SubscriptionDetailsShop::COL_SUBSCRIPTION_DETAILS_SHOP_SID] ?? null;
                if ($sid && $sid != $this->sid) {
                    unset($this->groupSubscriptionDetailsValid[$subscriptionDetailsId]);
                    $errorMessage[$errorKey][] = "Subscription code invalid on the feature $featureName for this shop. Please check & try again with another code.";
                }
            }
        }
        if (isset($errorMessage) && count($errorMessage) == count($groupSubscriptionDetails)) {
            return $errorMessage;
        }

        return true;
    }

    public function shopAndCredentialsIsNew()
    {
        // 1. Check shop existed with sid from channel?
        $shopInfo = $this->shopRepository->getBySid($this->sid, ShopRepository::IS_ACTIVE);
        if (! $shopInfo) {
            return true;
        }
        // 2. Shop available, check relationship between shop, user, feature?
        $shopEid = $shopInfo->{Shops::COL_SHOPS_EID};
        $hasLinked = 0;
        $featureNameList = null;
        $numberFeature = count($this->groupSubscriptionDetailsValid);
        foreach ($this->groupSubscriptionDetailsValid as $subscriptionDetailsId => $subscriptionInfo) {
            $shopUser = $this->shopUserRepository->search($shopEid, $this->userId, $subscriptionDetailsId);
            if (! empty($shopUser->toArray())) {
                unset($this->groupSubscriptionDetailsValid[$subscriptionDetailsId]);
                $hasLinked++;
            } else {
                if ($this->type == self::TYPE_MAIN) {
                    // Add other admin for shop
                    if ($featureNameList) {
                        $featureNameList .= ', '.current($subscriptionInfo)[Features::COL_FEATURES_NAME];
                    } else {
                        $featureNameList = current($subscriptionInfo)[Features::COL_FEATURES_NAME];
                    }
                    $addOtherAdmin = $this->shopUserRepository->create(
                        [
                            ShopUser::COL_FK_SHOP => $shopEid,
                            ShopUser::COL_FK_SUBSCRIPTION_DETAILS => $subscriptionDetailsId,
                            ShopUser::COL_FK_USER => $this->userId,
                            ShopUser::COL_SHOP_USER_ROLE => ShopUserRepository::ROLE_ADMIN,
                            ShopUser::COL_SHOP_USER_CREATED_BY => $this->userId,
                            ShopUser::COL_SHOP_USER_CREATED_AT => time(),
                        ]
                    );
                }
            }
        }
        if (isset($addOtherAdmin)) {
            return "You have become new admin of $this->shopName on $featureNameList";
        }
        if ($hasLinked == $numberFeature) {
            return 'You already are the admin of this shop.';
        }
        // 3. Check credentials existed?
        $shopCredentials = $this->shopCredentialsRepository->getByUsername($this->username, $shopEid);
        if (! $shopCredentials) {
            return true;
        } else {
            $this->logError('The account of the shop already existed.',[$this->username, $shopEid]);
            return 'The account of the shop already existed.';
        }
    }

    public function __mainAccountConnect($channelConfig, $shopCredentialsId, $shopEid)
    {
        $state = [
            'shop_eid' => $shopEid,
            'shop_credentials_id' => $shopCredentialsId,
            'feature' => array_keys($this->groupSubscriptionDetailsValid),
        ];
        switch ($this->channelCode) {
            case 'LAZADA':
                $appKey = $channelConfig['app_key'];
                $secretKey = $channelConfig['secret_key'];
                $handleUrl = config('passport.url.api').$channelConfig['url_callback'];
                $openApiLazada = new Lazada($appKey, $secretKey);
                $this->redirectAuthUrl = $openApiLazada->getOauthUrl($handleUrl, $state);
                return true;
            case 'SHOPEE':
                $appKey = $channelConfig['key'];
                $partnerId = $channelConfig['partner_id'];
                $openApiShopee = new Shopee($appKey, $partnerId);
                $handleUrl = config('passport.url.api').$channelConfig['url_callback'];
                $this->redirectAuthUrl = $openApiShopee->getOauthUrl($handleUrl, $state);

                return true;
            default:
                return false;
        }
    }

    private function __subAccountConnect($idAfterInsert)
    {
        $response = null;
        foreach ($idAfterInsert as $value) {
            $shopEid = $value['shop_eid'];
            foreach ($this->groupSubscriptionDetailsValid as $subscriptionDetailsId => $subscriptionInfo) {
                $shopUser = $this->shopUserRepository->search($shopEid, $this->userId, $subscriptionDetailsId);
                if (empty($shopUser->toArray())) {
                    $response = $this->shopUserRepository->create(
                        [
                            ShopUser::COL_FK_SHOP => $shopEid,
                            ShopUser::COL_FK_SUBSCRIPTION_DETAILS => $subscriptionDetailsId,
                            ShopUser::COL_FK_USER => $this->userId,
                            ShopUser::COL_SHOP_USER_ROLE => ShopUserRepository::ROLE_ADMIN,
                            ShopUser::COL_SHOP_USER_CREATED_BY => $this->userId,
                            ShopUser::COL_SHOP_USER_CREATED_AT => time(),
                        ]
                    );
                }
            }
        }
        if (! $response) {
            return false;
        }

        return true;
    }

    public function connectWithChannel()
    {
        $channelInfo = $this->channelRepository->getByCodeAndCountryCode($this->channelCode, $this->countryCode);
        $channelConfig = json_decode($channelInfo->{Channels::COL_CHANNELS_CONFIG}, true);
        switch ($this->type) {
            case self::TYPE_MAIN:
                $idAfterInsert = $this->shopRepository->createShopInfo(
                    $this->shopName,
                    $this->userId,
                    $this->sid,
                    $channelInfo->{Channels::COL_CHANNELS_ID},
                    [
                        'user_name' => $this->username,
                        'password' => Crypt::encryptString($this->password),
                        'seller_id' => $this->sellerId ?? null,
                    ],
                    ShopRepository::IS_RESERVE,
                    $this->cookies
                );

                return $this->__mainAccountConnect(
                    $channelConfig,
                    $idAfterInsert['shop_credentials_id'],
                    $idAfterInsert['shop_eid']
                );
            case self::TYPE_SUB:
                $idAfterInsert = [];
                foreach ($this->arrShopSubAccount as $shopSubAccount) {
                    $response = $this->shopRepository->createShopInfo(
                        $shopSubAccount['shop_name'],
                        $this->userId,
                        $shopSubAccount['shop_sid'],
                        $channelInfo->{Channels::COL_CHANNELS_ID},
                        [
                            'user_name' => $this->username,
                            'password' => Crypt::encryptString($this->password),
                            'seller_id' => $this->sellerId ?? null,
                        ],
                        ShopRepository::IS_UN_RESERVE,
                        $this->sid == $shopSubAccount['shop_sid'] ? $this->cookies : null,
                        $this->phone,
                    );
                    $idAfterInsert[] = $response;
                }

                return $this->__subAccountConnect($idAfterInsert);

        }

        return false;
    }
}
