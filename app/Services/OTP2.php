<?php

namespace App\Services;

use App\Models\Sql\Channels;
use App\Models\Sql\Countries;
use App\Models\Sql\LastOtpMessage;
use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Repositories\Sql\LastOtpMessageRepository;
use App\Repositories\Sql\ShopCredentialRepository2;
use App\Repositories\Sql\ShopRepository;
use App\Traits\CommonTrait;
use Epsilo\Auth\LazadaAuth;
use Epsilo\Auth\OpenApi\Lazada;
use Epsilo\Auth\ShopeeAuth;
use Epsilo\Auth\TokopediaAuth;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Constants\CacheTag;
use Epsilo\Auth\ShopeeSolutionsAuth;
use Epsilo\Auth\ShopeeBrandPortalAuth;

class OTP2
{
    use CommonTrait;

    const CODE_TYPE = 1; // OK
    const CODE_SUCCESS_RESEND_OTP = 2;
    const CODE_SUCCESS_LOGIN = 3;
    const CODE_ERROR_RESEND_OTP = 4;
    const CODE_ERROR_LOGIN = 5;
    const CODE_ERROR_SHOP_INACTIVE = 6;
    const CODE_ERROR_SHOP_CREDENTIAL_NOT_FOUND = 7;
    const CODE_ERROR_SHOP_NOT_FOUND = 8;
    const CODE_ERROR_SHOP_SAVE_DATA = 9;
    const CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY = 10;
    const CODE_ERROR_COOKIE_LIVE = 11;
    const CODE_ERROR_REFRESH_OPENAPI = 12;
    const CODE_SUCCESS_REFRESH_OPENAPI = 13;
    const CODE_ERROR_OTP_LARGER_MAX_RETRY = 14;
    const CODE_ERROR_DECRYPT_STRING_PASSWORD = 16;
    const CODE_COOKIE_LIVE = 17;
    const CODE_ERROR_LAZADA_RESPONSE = 18;
    const CODE_NOT_OPEN_API = 19;

    const TIME_REFRESH_COOKIE_SHOPEE = 43200; //12*60*60 = 12h
    const TIME_REFRESH_COOKIE_LAZADA = 86400; //24*60*60 = 24h
    const TIME_REFRESH_OPENAPI_LAZADA = 2073600; //24*60*60*24 = 24d
    const TIME_REFRESH_COOKIE_TOKOPEDIA = 43200; //12*60*60 = 12h

    const RETRY_NONE = 1; // no retry
    const RETRY_PLUS = 2; // retry failed
    const RETRY_SUCCESS = 3; // retry ok

    /**
     * @var string
     */
    private $countryCode;
    private $userName;
    private $password;
    private $shopCredentialId;
    private $shopSId;
    private $channelCode;
    private $shopCredential;
    private $shopCredentialType;
    private $shopActive;
    private $shopCredentialActive;
    private $shopPhone;
    private $shopEid;
    private $type; // error or not. OK have value = CODE_TYPE
    private $retry;
    private $retryOTP;

    private $updateAt;
    private $token;

    public function __construct($shopCredentialId = null)
    {
        if (!$shopCredentialId) return;
        $this->shopCredentialId = $shopCredentialId;
        $this->getShopCredential();
        $this->getShop();
        $this->type = self::CODE_TYPE;
    }

    public function getShopCredential()
    {
        $shopCredential = new ShopCredentialRepository2();
        $shopCredential = $shopCredential->find($this->shopCredentialId);
        if (!empty($shopCredential)) {
            $value = json_decode($shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_VALUE}, true);
            $this->userName = $value['user_name'];
            $this->password = $value['password'];
            $this->shopCredential = $shopCredential;
            $this->shopCredentialType = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE};
            $this->retry = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC};
            $this->retryOTP = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_OTP};
            $this->shopCredentialActive = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_IS_ACTIVE};
            $this->updateAt = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT};
            if ($this->shopCredentialType == ShopCredential2::TYPE_OPENAPI) {
                $this->token = json_decode($shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN}, true); // array
            } else {
                $this->token = $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN};
            }
        } else {
            $this->type = self::CODE_ERROR_SHOP_CREDENTIAL_NOT_FOUND;
        }
    }

    public function getShop()
    {
        $shopCredential = $this->shopCredential;
        if (!empty($shopCredential)) {
            $shopRepository = new ShopRepository();
            $shop = $shopRepository->getInfoShop($shopCredential->{ShopCredential2::COL_FK_SHOP});

            if (!empty($shop)) {
                $this->countryCode = $shop->{Countries::COL_COUNTRIES_CODE};
                $this->shopSId = $shop->{Shops::COL_SHOPS_SID};
                $this->channelCode = $shop->{Channels::COL_CHANNELS_CODE};
                $this->shopActive = $shop->{Shops::COL_SHOPS_IS_ACTIVE};
                $this->shopPhone = $shop->{Shops::COL_SHOP_PHONE};
                $this->shopEid = $shop->{Shops::COL_SHOPS_EID};
            } else {
                $this->type = self::CODE_ERROR_SHOP_NOT_FOUND;
                $this->logError('OTP getShop error', ['shopCredentialId' => $this->shopCredentialId, 'shop' => $shop]);
            }
        }
    }

    public function getCountry()
    {
        return $this->countryCode;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getShopSId()
    {
        return $this->shopSId;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getPassword()
    {
        try {
            return Crypt::decryptString($this->password);
        } catch (DecryptException $e) {
            $this->type = self::CODE_ERROR_DECRYPT_STRING_PASSWORD;
        }
        return '';
    }

    public function checkType()
    {
        if ($this->type == self::CODE_TYPE) {
            return true;
        }
        return false;
    }

    public function checkShopActive()
    {
        if ($this->shopActive == ShopRepository::IS_ACTIVE && $this->shopCredentialActive == ShopCredential2::IS_ACTIVE) {
            return true;
        }
        return false;
    }

    public function checkCredenialRetry()
    {
        if ($this->retry >= MAX_RETRY_CRED) {
            return false;
        }
        return true;
    }

    public function checkOTPRetry()
    {
        if ($this->retryOTP >= MAX_RETRY_CRED) {
            return false;
        }
        return true;
    }

    public function forceUpdateCookie($updateAt)
    {
        $now = time();
        switch ($this->channelCode) {
            case CHANNEL_SHOPEE:
                if (($updateAt + self::TIME_REFRESH_COOKIE_SHOPEE) <= $now) {
                    return false;
                }
                break;
            case CHANNEL_LAZADA:
                if (($updateAt + self::TIME_REFRESH_COOKIE_LAZADA) <= $now) {
                    return false;
                }
                break;
            case CHANNEL_TOKOPEDIA:
                if (($updateAt + self::TIME_REFRESH_COOKIE_TOKOPEDIA) <= $now) {
                    return false;
                }
                break;
            default:
                break;
        }

        return true;
    }

    public function forceUpdateOpenApiLazada($updateAt)
    {
        $now = time();
        if (($updateAt + self::TIME_REFRESH_OPENAPI_LAZADA) <= $now) {
            return false;
        }
        return true;
    }

    /**
     * @param null $token : null is clear, 0 is 'not touch'
     * @param null $hidden : null is clear, 0 is 'not touch'
     * @param null $state
     * @param array $retryOTP
     * @param string $last_fail_msg
     * @param boolean $otpStatus
     * @return bool
     */
    public function saveSuccess($token, $hidden, $state = null, $last_fail_msg = null, $retryOTP = self::RETRY_NONE, $otpStatus = false)
    {
        $input = [
            ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT => time(),
        ];
        if ($token !== 0) {
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN] = $token;
        }
        if ($hidden !== 0) {
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN_HIDDEN] = $hidden;
        }
        if (!empty($token)) {
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC] = 0;
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_OTP] = 0;
        }
        if (!empty($state)) {
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_STATE] = $state;
        }
        if ($state == ShopCredential2::STATE_NEED_OTP || $otpStatus) {
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_OTP_STATUS] = ShopCredential2::OTP_STATUS_NEED_OTP;
        }
        if ($state == ShopCredential2::STATE_SUCCESS) {
            $last_fail_msg = null; // clear this if success
            $input[ShopCredential2::COL_SHOP_CREDENTIAL_OTP_STATUS] = ShopCredential2::OTP_STATUS_SUCCESS;
        }
        $input[ShopCredential2::COL_SHOP_CREDENTIAL_LAST_FAIL_MESSAGE] = $last_fail_msg;
        DB::beginTransaction();
        try {
            $credentialRepository = new ShopCredentialRepository2();
            $shopCredential = $this->shopCredential;
            if (!empty($shopCredential)) {
                if ($retryOTP == self::RETRY_NONE) {
                    if ($state == ShopCredential2::STATE_NEED_OTP) {
                        if ($this->checkCredenialRetry()) {
                            $input[ShopCredential2::COL_SHOP_CREDENTIAL_LAST_RETRY] = time();
                            $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC] = intval($this->retry) + 1;
                        }
                    }
                } else if ($retryOTP == self::RETRY_PLUS) {
                    if ($this->checkOTPRetry()) {
                        $input[ShopCredential2::COL_SHOP_CREDENTIAL_LAST_RETRY_OTP] = time();
                        $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_OTP] = intval($this->retryOTP) + 1;
                    }
                }
            }
            $credentialRepository->update($this->shopCredentialId, $input);
            $this->logInfo('Save shop credential ok', ['id' => $this->shopCredentialId, 'input' => $input, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid], 1, 'saveSuccess-' . $this->shopCredentialId);
            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            $this->logError('Save shop credential failed', ['msg' => $e->getMessage(), 'input' => $input, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid, 'ShopCredentialId' => $this->shopCredentialId], 1, 'saveFail-' . $this->shopCredentialId);
            return self::CODE_ERROR_SHOP_SAVE_DATA;
        }

        return self::CODE_TYPE;
    }

    /**
     * @param null $otp
     * @return bool
     */
    public function loginOtp($otp)
    {
        $userName = $this->getUserName();
        $password = $this->getPassword();

        if (!$this->checkType()) {
            return $this->type;
        }
        if (!$this->checkShopActive()) {
            return self::CODE_ERROR_SHOP_INACTIVE;
        }

        $type = self::CODE_ERROR_LOGIN;
        $response = [];
        $options = [];
        switch ($this->channelCode) {
            case CHANNEL_SHOPEE:
                $response = ShopeeAuth::auth($userName, $password, $this->getCountry(), $otp, ['shop_sid' => $this->getShopSId()]);
                $response = json_decode($response, true);
                if ($response['success']) {
                    $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                } else {
                    $this->logInfo('Shopee login failed (otp login)', ['shopEid' => $this->shopEid, 'otp' => $otp, 'phone' => $this->shopPhone, 'IP' => get_public_ip(), 'response' => $response], 1, 'shopeelogin-' . $this->shopEid);
                    if (!$this->checkCookieValid()) {
                        $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message']);
                    } else {
                        $type = $this->saveSuccess(0, 0, null, null, self::RETRY_NONE);
                    }
                }
                break;
            case CHANNEL_LAZADA:
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                ];
                $response = LazadaAuth::auth($userName, $password, $this->getCountry(), $otp, $options);
                $response = json_decode($response, true);
                if ($response['success']) {
                    $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                } else {
                    $this->logInfo('Lazada login failed (otp login)', ['shopEid' => $this->shopEid, 'otp' => $otp, 'IP' => get_public_ip(), 'response' => $response], 1, 'lazadalogin-' . $this->shopEid);
                    if (!$this->checkCookieValid()) {
                        $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message']);
                    } else {
                        $type = $this->saveSuccess(0, 0, null, null, self::RETRY_NONE);
                    }
                }
                break;
            case CHANNEL_TOKOPEDIA:
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                ];
                $response = TokopediaAuth::auth($userName, $password, $this->getCountry(), $otp, $options);
                $response = json_decode($response, true);
                if ($response['success']) {
                    $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                } else {
                    $this->logInfo('Toko login failed (otp login)', ['shopEid' => $this->shopEid, 'otp' => $otp, 'IP' => get_public_ip(), 'response' => $response], 1, 'tokologin-' . $this->shopEid);
                    if (!$this->checkCookieValid()) {
                        $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, 'check_cookie_failed_after_otp');
                    } else {
                        $type = $this->saveSuccess(0, 0, null, null, self::RETRY_NONE);
                    }
                }
                break;
            default:
                break;
        }
        $this->logInfo('End login otp', ['type' => $type, 'IP' => get_public_ip(), 'country' => $this->getCountry(), 'otp' => $otp, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid, $options, $response], 1, 'loginOtp-' . $this->channelCode . '-' . $this->shopCredentialId);

        if ($type == self::CODE_TYPE) {
            Cache::tags(CacheTag::CREDENTIAL.$this->shopEid)->flush();
            return self::CODE_SUCCESS_LOGIN;
        }
        return $type;
    }

    public function resendOtp($force = false)
    {
        $userName = $this->getUserName();
        $password = $this->getPassword();

        if (!$this->checkType()) {
            return $this->type;
        }
        if (!$this->checkShopActive()) {
            return self::CODE_ERROR_SHOP_INACTIVE;
        }
        if (!$this->checkCredenialRetry()) {
            return self::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY;
        }
        if (!$this->checkOTPRetry()) {
            return self::CODE_ERROR_OTP_LARGER_MAX_RETRY;
        }
        $type = self::CODE_ERROR_RESEND_OTP;
        $response = [];
        $options = [];
        $force ? $otpStatus = true : $otpStatus = false;
        switch ($this->channelCode) {
            case CHANNEL_SHOPEE:
                if ($this->shopCredentialType == ShopCredential2::TYPE_SELLERCENTER) {
                    $response = ShopeeAuth::auth($userName, $password, $this->getCountry(), '', ['shop_sid' => $this->getShopSId()]);
                    $response = json_decode($response, true);
                    if ($response['success']) {
                        $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                    } else {
                        $this->logInfo('Shopee login failed', ['shopEid' => $this->shopEid, 'phone' => $this->shopPhone, 'IP' => get_public_ip(), 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'shopeelogin-' . $this->shopEid);
                        if (!$this->checkCookieValid()) {
                            $type = $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message'], self::RETRY_PLUS);
                        } else {
                            $type = $this->saveSuccess(0, 0, null, null, self::RETRY_PLUS, $otpStatus);
                        }
                    }
                } else if ($this->shopCredentialType == ShopCredential2::TYPE_MARKETING) {
                    $response = ShopeeSolutionsAuth::auth($userName, $password, $this->getCountry(), '', ['shop_sid' => $this->getShopSId()]);
                    $response = json_decode($response, true);
                    if ($response['success']) {
                        $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                    } else {
                        $this->logInfo('Shopee Marketing login failed', ['shopEid' => $this->shopEid, 'phone' => $this->shopPhone, 'IP' => get_public_ip(), 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'shopeemarketinglogin-' . $this->shopEid);
                        if (!$this->checkCookieValid()) {
                            $type = $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message'], self::RETRY_PLUS);
                        } else {
                            $type = $this->saveSuccess(0, 0, null, null, self::RETRY_PLUS);
                        }
                    }
                } else if ($this->shopCredentialType == ShopCredential2::TYPE_BRANDPORTAL) {
                    $response = ShopeeBrandPortalAuth::auth($userName, $password, $this->getCountry(), '', ['shop_sid' => $this->getShopSId()]);
                    $response = json_decode($response, true);
                    if ($response['success']) {
                        $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                    } else {
                        $this->logInfo('Shopee Brandportal login failed', ['shopEid' => $this->shopEid, 'phone' => $this->shopPhone, 'IP' => get_public_ip(), 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'shopeebrandportallogin-' . $this->shopEid);
                        if (!$this->checkCookieValid()) {
                            $type = $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message'], self::RETRY_PLUS);
                        } else {
                            $type = $this->saveSuccess(0, 0, null, null, self::RETRY_PLUS);
                        }
                    }
                }
                break;
            case CHANNEL_LAZADA:
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                ];
                $response = LazadaAuth::auth($userName, $password, $this->getCountry(), '', $options);
                $response = json_decode($response, true);
                if ($response['success']) {
                    $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                } else {
                    $this->logInfo('Lazada login failed', ['shopEid' => $this->shopEid, 'IP' => get_public_ip(), 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'lazadalogin-' . $this->shopEid);
                    if (!$this->checkCookieValid()) {
                        $type = $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, $response['message'], self::RETRY_PLUS);
                    } else {
                        $type = $this->saveSuccess(0, 0, null, null, self::RETRY_PLUS);
                    }
                }
                if (!$this->forceUpdateOpenApiLazada($this->updateAt)) {
                    $this->refreshOpenApiLazada();
                }
                break;
            case CHANNEL_TOKOPEDIA:
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                ];
                $response = TokopediaAuth::auth($userName, $password, $this->getCountry(), '', $options);
                $response = json_decode($response, true);
                if ($response['success']) {
                    $type = $this->saveSuccess($response['cookie_string'], null, ShopCredential2::STATE_SUCCESS);
                } else {
                    $this->logInfo('Toko login failed', ['shopEid' => $this->shopEid, 'IP' => get_public_ip(), 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'tokologin-' . $this->shopEid);
                    if (!$this->checkCookieValid()) {
                        $type = $this->saveSuccess(0, 0, ShopCredential2::STATE_NEED_OTP, 'check_cookie_failed', self::RETRY_PLUS);
                    } else {
                        $type = $this->saveSuccess(0, 0, null, null, self::RETRY_PLUS, $otpStatus);
                    }
                }
                break;
            default:
                break;
        }
        $this->logInfo('End resend otp', ['type' => $type, 'IP' => get_public_ip(), 'country' => $this->getCountry(), 'options' => $options, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid, 'response' => $response, 'force' => $force, 'retry' => $this->retry, 'retryOTP' => $this->retryOTP], 1, 'resendOtp-' . $this->channelCode . '-' . $this->shopCredentialId);
        if ($type == self::CODE_TYPE) {
            Cache::tags(CacheTag::CREDENTIAL.$this->shopEid)->flush();
            return self::CODE_SUCCESS_RESEND_OTP;
        }
        return $type;
    }

    public function saveMessage($message)
    {
        if (!is_array($message)) {
            return false;
        }
        $phone = $message['phone'];
        $otp = $message['otp'];
        $lastMessage = new LastOtpMessageRepository();
        $isExist = $lastMessage->getOtpByPhone($phone);
        if (!empty($isExist)) {
            $lastMessage->updateByPhone($phone, [
                LastOtpMessage::COL_LAST_OTP_MESSAGE_OTP => $otp,
                LastOtpMessage::COL_LAST_OTP_MESSAGE_MESSAGE => $message,
                LastOtpMessage::COL_LAST_OTP_MESSAGE_UPDATED_AT => time(),
            ]);
        } else {
            $lastMessage->create(
                [
                    LastOtpMessage::COL_LAST_OTP_MESSAGE_OTP => $otp,
                    LastOtpMessage::COL_LAST_OTP_MESSAGE_PHONE => $phone,
                    LastOtpMessage::COL_LAST_OTP_MESSAGE_MESSAGE => json_encode($message),
                    LastOtpMessage::COL_LAST_OTP_MESSAGE_CREATED_AT => time(),
                    LastOtpMessage::COL_LAST_OTP_MESSAGE_UPDATED_AT => time(),
                ]
            );
        }
        return true;
    }

    public function getOtpByPhone($phone)
    {
        $lastMessage = new LastOtpMessageRepository();
        $optData = $lastMessage->getOtpByPhone($phone);
        $otp = null;
        $lastTime = $optData->{LastOtpMessage::COL_LAST_OTP_MESSAGE_UPDATED_AT} ?? null;
        if ($lastTime && $lastTime >= time() - 30 * 60) { // within 30 minutes
            $otp = $optData->{LastOtpMessage::COL_LAST_OTP_MESSAGE_OTP};
        }
        return $otp;
    }

    public function checkCookieValid($cookie = null, $channelCode = null, $countryCode = null)
    {
        if (empty($countryCode)) {
            $countryCode = $this->getCountry();
        }
        if (empty($channelCode)) {
            $channelCode = $this->channelCode;
        }
        if (empty($cookie)) {
            $cookie = $this->getToken();
        }
        $response = [];
        switch ($channelCode) {
            case CHANNEL_SHOPEE:
                if ($this->shopCredentialType == ShopCredential2::TYPE_MARKETING) {
                    $response = ShopeeSolutionsAuth::checkCookieValid($cookie);
                    $response = json_decode($response, true);
                } elseif ($this->shopCredentialType == ShopCredential2::TYPE_BRANDPORTAL) {
                    $response = ShopeeBrandPortalAuth::checkCookieValid($cookie);
                    $response = json_decode($response, true);
                } else {
                    $response = ShopeeAuth::checkCookieValid($cookie, $countryCode);
                    $response = json_decode($response, true);
                }
                break;
            case CHANNEL_LAZADA:
                $response = LazadaAuth::checkCookieValid($cookie, $countryCode);
                $response = json_decode($response, true);
                break;
            case CHANNEL_TOKOPEDIA:
                $options = [
                    'api_domain_simulation' => config('passport.api_domain_simulation'),
                ];
                $response = TokopediaAuth::checkCookieValid($cookie, $countryCode, $options);
                $response = json_decode($response, true);
                break;
            default:
                break;
        }
        $type = $response['success'] ?? false;

        $this->logInfo('End', ['cookie' => $cookie, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid, 'country_code' => $countryCode, 'response' => $response], 1, 'Check-Cookie-Valid-Need-refresh-cookie-' . $this->channelCode . '-' . $this->shopCredentialId);
        return $type;
    }

    /**
     * SHOPEE only
     */
    public function checkShopIdValid($cookie = null, $countryCode = null)
    {
        if (empty($countryCode)) {
            $countryCode = $this->getCountry();
        }
        if (empty($cookie)) {
            $cookie = $this->getToken();
        }
        if ($this->channelCode != 'SHOPEE') {
            // check Shopee only
            return false;
        }
        $response = ShopeeAuth::checkShopIdValid($cookie, $countryCode, $this->getShopSId());
        $response = json_decode($response, true);

        $type = $response['success'] ?? false;

        $this->logInfo('End', ['cookie' => $cookie, 'phone' => $this->shopPhone, 'shopEid' => $this->shopEid, 'country_code' => $countryCode, 'response' => $response], 1, 'Check-ShopId-Valid-' . $this->channelCode . '-' . $this->shopCredentialId);
        return $type;
    }

    /**
     * LAZADA only
     */
    public function checkOpenApiTokenValid()
    {
        if (empty($this->token)) {
            return 'Token is empty';
        }
        if (empty(trim($this->countryCode))) {
            return 'Country code is empty';
        }

        $response = LazadaAuth::checkOpenApiValid($this->token, $this->countryCode);
        return json_decode($response, true);
    }

    public function getMessager($code)
    {
        $message = [
            self::CODE_SUCCESS_RESEND_OTP => 'Send OTP success.',
            self::CODE_SUCCESS_LOGIN => 'Login shop success.',
            self::CODE_ERROR_RESEND_OTP => 'Send OTP error. Please try again later.',
            self::CODE_ERROR_LOGIN => 'Login shop error. Please try again later.',
            self::CODE_ERROR_SHOP_INACTIVE => 'Shop inactive. Please try again later.',
            self::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY => 'Credential larger max retry. Please try again later.',
            self::CODE_ERROR_OTP_LARGER_MAX_RETRY => 'OTP larger max retry. Please try again later.',
            self::CODE_ERROR_SHOP_CREDENTIAL_NOT_FOUND => 'Shop not found. Please try again later.',
            self::CODE_ERROR_SHOP_SAVE_DATA => 'Save error. Please try again later.',
            self::CODE_ERROR_COOKIE_LIVE => 'Shop credential cookie live',
            self::CODE_SUCCESS_REFRESH_OPENAPI => 'Shop credential refresh open api success',
            self::CODE_ERROR_REFRESH_OPENAPI => 'Shop credential refresh open api fail. Please try again later.',
            self::CODE_ERROR_DECRYPT_STRING_PASSWORD => 'Decrypt password credential error',
            self::CODE_COOKIE_LIVE => 'Cookie live',
            self::CODE_NOT_OPEN_API => 'Token is not open api',
            self::CODE_ERROR_LAZADA_RESPONSE => 'Response lazada error',
        ];

        return $message[$code];
    }

    public function refreshOpenApiLazada()
    {
        if (!$this->checkType()) {
            return $this->type;
        }
        if ($this->shopCredentialType != ShopCredential2::TYPE_OPENAPI) {
            return self::CODE_NOT_OPEN_API;
        }
        $type = $this->type;

        $hidden = json_decode($this->shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN_HIDDEN}, true);
        $appkey = $this->token['app_key'];
        $secretKey = $this->token['secret_key'];
        $apiUrl = $hidden['api_url'];
        $refreshToken = $hidden['refresh_token'] ?? null;
        if (empty($refreshToken)){
            $this->logInfo('Error', ['$token_hidden' => $hidden], 1, 'refreshOpenApiLazada-' . $this->shopCredentialId);
            return $type;
        }

        $lazada = new Lazada($appkey, $secretKey, $apiUrl);
        $response = $lazada->refreshToken($refreshToken);
        if (isset($response['code']) && $response['code'] != 0) {
            $type = self::CODE_ERROR_REFRESH_OPENAPI;
        } else {
            if (!empty($response['refresh_token']) && !empty($response['access_token'])) {
                $hidden['refresh_token'] = $response['refresh_token'];
                $this->token['access_token'] = $response['access_token'];
                $type = $this->saveSuccess($this->token, $hidden, ShopCredential2::STATE_SUCCESS);
            } else {
                $type = self::CODE_ERROR_LAZADA_RESPONSE;
            }
        }
        $this->logInfo('End refreshOpenApiLazada', ['type' => $type, $response], 1, 'refreshOpenApiLazada-' . $this->shopCredentialId);
        if ($type == self::CODE_TYPE) {
            Cache::tags(CacheTag::CREDENTIAL.$this->shopEid)->flush();
            $type = self::CODE_SUCCESS_REFRESH_OPENAPI;
        }

        return $type;
    }
}
