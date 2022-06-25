<?php

namespace App\Console\Commands;

use App\Models\Sql\ShopCredential2;
use App\Models\Sql\Shops;
use App\Services\Credential2;
use App\Services\OTP2;
use App\Traits\CommonTrait;
use Illuminate\Console\Command;

class CheckCookieValid2 extends Command
{
    use CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:check-cookie-valid2 {{--shopEid=}} {{--channelCode=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check cookie invalid shop';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Since X hours from last time successfully update OTP: resend OTP
     *
     */
    public function handle()
    {
        $shopEid = $this->option('shopEid');
        $channelCode = $this->option('channelCode');
        $credential = new Credential2();
        $shops = $credential->getAllCredential($shopEid, ShopCredential2::IS_ACTIVE, $channelCode, [ShopCredential2::TYPE_SELLERCENTER, ShopCredential2::TYPE_MARKETING, ShopCredential2::TYPE_BRANDPORTAL]);
        $tempPhone = [];

        foreach ($shops as $shop) {
            $requestId = $this->getRequestId(true, $shop->{ShopCredential2::COL_FK_SHOP});
            $otp = new OTP2($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID});
            if ($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE} == ShopCredential2::TYPE_SELLERCENTER) {
                $phone = $shop->{Shops::COL_SHOP_PHONE};
                $code = null;
                $text = '';
                if (!in_array($phone, $tempPhone) || empty($phone)) {
                    $code = OTP2::CODE_COOKIE_LIVE;
                    if (!empty($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TOKEN})) {
                        $isValid = $otp->checkCookieValid();
                        if (!$isValid) {
                            $isUpdate = $this->updateCredentialWhenExpire($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, $shop->{ShopCredential2::COL_FK_SHOP}, $shop->{Shops::COL_SHOP_PHONE});
                            if ($isUpdate === true) {
                                $code = $otp->resendOtp();
                            } else if ($isUpdate == OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY) {
                                $code = OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY;
                            } else {
                                $code = OTP2::CODE_ERROR_SHOP_SAVE_DATA;
                            }
                        } else if (!$otp->forceUpdateCookie($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT})) {
                            $this->logInfo('Check cookie valid: force update cookie', ['shop_eid'=>$shop->{Shops::COL_SHOPS_EID}, 'phone'=>$shop->{Shops::COL_SHOP_PHONE}, 'credential_id'=>$shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, 'request_id' => $requestId, 'current' => time(), 'cred_updated_at' => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_UPDATED_AT}, 'IP' => get_public_ip() ?? '']);
                            $code = $otp->resendOtp(true);
                        }
                    } else {
                        $this->logInfo('Check cookie valid: cookie empty, call update credential when expired', ['shop_eid'=>$shop->{Shops::COL_SHOPS_EID}, 'phone'=>$shop->{Shops::COL_SHOP_PHONE}, 'credential_id'=>$shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, 'request_id' => $requestId]);
                        $isUpdate = $this->updateCredentialWhenExpire($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, $shop->{ShopCredential2::COL_FK_SHOP}, $shop->{Shops::COL_SHOP_PHONE});
                        if ($isUpdate) {
                            $code = $otp->resendOtp();
                        } else {
                            $code = OTP2::CODE_ERROR_SHOP_SAVE_DATA;
                        }
                    }
                    if (!empty($phone)){
                        array_push($tempPhone, $phone);
                    }
                } else {
                    if (empty($phone)) {
                        $text = 'Shop doesn\'t have phone';
                    } else {
                        $text = 'Account/Phone is checked before, skipped';
                    }
                }
            } else if ($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE} == ShopCredential2::TYPE_MARKETING) {
                $isValid = $otp->checkCookieValid();
                if (!$isValid) {
                    $isUpdate = $this->updateCredentialWhenExpire($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, $shop->{ShopCredential2::COL_FK_SHOP}, $shop->{Shops::COL_SHOP_PHONE});
                    if ($isUpdate === true) {
                        $code = $otp->resendOtp();
                    } else if ($isUpdate == OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY) {
                        $code = OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY;
                    } else {
                        $code = OTP2::CODE_ERROR_SHOP_SAVE_DATA;
                    }
                }
            } else if ($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE} == ShopCredential2::TYPE_BRANDPORTAL) {
                $isValid = $otp->checkCookieValid();
                if (!$isValid) {
                    $isUpdate = $this->updateCredentialWhenExpire($shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, $shop->{ShopCredential2::COL_FK_SHOP}, $shop->{Shops::COL_SHOP_PHONE});
                    if ($isUpdate === true) {
                        $code = $otp->resendOtp();
                    } else if ($isUpdate == OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY) {
                        $code = OTP2::CODE_ERROR_CREDENTIAL_LARGER_MAX_RETRY;
                    } else {
                        $code = OTP2::CODE_ERROR_SHOP_SAVE_DATA;
                    }
                }
            }

            $log_msg = 'Check cookie valid: ';
            if (is_null($code)) {
                $log_msg .= $text;
            } else {
                $log_msg .= $otp->getMessager($code);
            }
            $this->logInfo($log_msg, ['shop_eid'=>$shop->{Shops::COL_SHOPS_EID}, 'phone'=>$shop->{Shops::COL_SHOP_PHONE}, 'credential_id'=>$shop->{ShopCredential2::COL_SHOP_CREDENTIAL_ID}, 'type' => $shop->{ShopCredential2::COL_SHOP_CREDENTIAL_TYPE}, 'request_id' => $requestId]);
            echo 'Request Id: ' . $requestId . "\n";
        }
        return;
    }

    private function updateCredentialWhenExpire($shopCredentialId, $shopEid, $phone)
    {
        $credential = new Credential2();
        return $credential->updateCredentialExpire($shopCredentialId, null, $shopEid, $phone);
    }
}
