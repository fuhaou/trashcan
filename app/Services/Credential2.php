<?php

namespace App\Services;

use App\Models\Sql\Channels;
use App\Models\Sql\Features;
use App\Models\Sql\ShopCredential2;
use App\Repositories\Sql\ShopCredentialRepository2;
use App\Repositories\Sql\ShopRepository;
use App\Library\ELogger;

class Credential2
{
    private $actionCode;
    private $shopEid;
    private $shop;
    private $channelCode;

    public function __construct($shopEid = null, $actionCode = null)
    {
        if (!$shopEid) {
            return;
        }
        $this->setShopEid($shopEid);
        $this->setActionCode($actionCode);
        $this->getShop();
    }

    public function getShop()
    {
        $shop = new ShopRepository();
        $shop = $shop->getInfoShop($this->getShopEid());
        if (!empty($shop)) {
            $this->channelCode = $shop->{Channels::COL_CHANNELS_CODE};
            $this->shop = $shop;
        }
    }

    public function setShopEid($shopEid)
    {
        $this->shopEid = $shopEid;
    }

    public function getShopEid()
    {
        return $this->shopEid;
    }

    public function setActionCode($actionCode)
    {
        $this->actionCode = $actionCode;
    }

    public function getActionCode()
    {
        return $this->actionCode;
    }

    public function getDataCredentialShop($type)
    {
        $shopCredentialRepository = new ShopCredentialRepository2();
        $arrType = [];
        if ($type != null) {
            $arrType = [$type];
        }
        $credential = $shopCredentialRepository->getCredentialShopByShopEid([$this->getShopEid()], $arrType, $this->getActionCode());

        if ($credential) {
            $temp = [];
            foreach ($credential as $item) {
                $output = [
                    ShopCredential2::COL_SHOP_CREDENTIAL_ID => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_ID},
                    'user_name' => $item->user_name,
                    'seller_id' => $item->seller_id,
                    'credentials_state' => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_STATE},
                    'retry' => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC},
                    ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT},
                    Features::COL_FEATURES_ID => $item->{Features::COL_FEATURES_ID},
                    Features::COL_FEATURES_NAME => $item->{Features::COL_FEATURES_NAME},
                    Features::COL_FEATURES_CODE => $item->{Features::COL_FEATURES_CODE},
                    'credentials_type' => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE},
                    'credentials_value' => $item->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN},
                ];
                array_push($temp, $output);
            }
            return $temp;
        }
        return [];
    }

    public function updateCredentialExpire($shopCredentialId, $isReset = null, $shopEid = null, $phone = null)
    {
        $shopCredentialRepository = new ShopCredentialRepository2();
        $shopCredential = $shopCredentialRepository->find($shopCredentialId);
        if (!empty($shopCredential)) {
            $input = [
                ShopCredential2::COL_SHOP_CREDENTIAL_STATE => ShopCredential2::STATE_NEED_OTP,
                ShopCredential2::COL_SHOP_CREDENTIAL_OTP_STATUS => ShopCredential2::OTP_STATUS_NEED_OTP,
                ShopCredential2::COL_SHOP_CREDENTIAL_LAST_FAIL_MESSAGE => 'cookie_invalid',
                ShopCredential2::COL_SHOP_CREDENTIAL_LAST_RETRY => time(),
                ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT => time(),
            ];
            if (!empty($isReset)) {
                $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC] = 0;
                // $input[ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN] = null;
                $input[ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_OTP] = 0;
            }
            if ($shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC} <= OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY || !empty($isReset)) {
                $credentialRepository = new ShopCredentialRepository2();
                $model = $credentialRepository->update($shopCredentialId, $input);
                Elogger::info('Save shop credential ok (updateCredentialExpire)', ['id' => $shopCredentialId, 'input' => $input, 'phone' => $phone, 'shopEid' => $shopEid, 'reset' => $isReset, 'retry' => $shopCredential->{ShopCredential2::COL_SHOP_CREDENTIAL_RETRY_GET_CREDENTIAL_SC}]);
                if (!empty($model)) {
                    return true;
                }
                return false;
            } else {
                return OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY;
            }
        }
        return false;
    }

    public function getShopCredentialNeedOTP()
    {
        $credentialRepository = new ShopCredentialRepository2();

        return $credentialRepository->getShopNeedOTP();
    }

    public function getAllCredential($shopEid, $isActive = ShopCredential2::IS_ACTIVE, $channelCode = null, $type = [], $joinCountries = false)
    {
        $credentialRepository = new ShopCredentialRepository2();

        return $credentialRepository->getAllCredential($shopEid, $isActive, $channelCode, $type, $joinCountries);
    }
}
