<?php

namespace App\Console\Commands\Once;

use Epsilo\Auth\LazadaAuth;
use Epsilo\Auth\ShopeeAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Epsilo\Auth\TokopediaAuth;

/**
 * Not used anymore because we move from shop_credential table to shop_credential_2 table
 * @deprecated
 */
class GetShopSid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'once:get-shop-sid {--type=}}';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $type = $this->option('type');
        $userAdmin = [
            [11, "Colgate Palmolive", "bei_xi_teh@colpal.com", 200, "Aa123456"],
            [12, "Danone", "Esha.SABINA@danone.com", 201, "dW0rB0lG2wR9wD9c"],
            [4, "DKSH", "shotinutt.c@dksh.com", 1026],
            [1, "Epsilo", "quang@epsilo.io", 326],
            [13, "Estée Lauder", "ngtran@vn.estee.com", 202, "uZ9fP3jZ8nM1sU8f"],
            [14, "FrieslandCampina", "tammy.fransli@frieslandcampina.com", 203, "aJ7sG7jG7qM4cK5i"],
            [6, "L'Oréal", "suemin.kim@loreal.com", 685],
            [18, "Medela", "devanshi.verma@medela.com", 204, "wE7lP1oC3nG5zL8h"],
            [16, "P&G", "madhavan.a@pg.com", 205, "qW3vO1tJ4wR2gS6c"],
            [2, "Reckitt Benckiser", "Frank.Ng@rb.com", 915],
            [8, "Rohto Mentholatum", "thuy.ntm@rohto.com.vn", 1078],
            [10, "Unilever", "Huynh-Khuong.Duy@unilever.com", 902],
            [15, "VTA", "hai.phamdangvinh@viettinhanh.com.vn", 206, "qO2oV7oR2xP0sW5b"],
            [17, "Zenyum", "jingyi@zenyum.com", 207, "jV2aP8pQ1hQ6fH0f"]
        ];
        $sqlUserScript = 'INSERT INTO users(users_id, users_first_name, users_last_name, users_email, users_password, users_phone, users_ip, users_device, users_avatar, users_remember_token, fk_company_register_code, users_is_active, users_email_verified_at, users_last_login_at, users_created_at, users_updated_at) VALUES';
        $sqlCompanyUserScript = 'INSERT INTO company_user ( fk_company, fk_user, company_user_role, company_user_is_active, company_user_created_at, company_user_updated_at) VALUES';
        $dataCompanyUser = [];
        $dataCompanyId = [];
        foreach ($userAdmin as $user) {
            $dataCompanyUser[$user[1]] = $user[3];
            $dataCompanyId[$user[1]] = $user[0];
            if (isset($user[4])) {
                $sqlUserScript .= '(' . $user[3] . ', "' . $user[2] . '", "", "' . $user[2] . '", md5("' . $user[4] . '"), null, null,null, null, null, ' . $user[0] . ', 1, null, ' . time() . ', ' . time() . ', ' . time() . '),';
            }
            $sqlCompanyUserScript .= '( ' . $user[0] . ', ' . $user[3] . ', "root", 1, ' . time() . ', ' . time() . '),';
        }
        $sqlUserScript = substr($sqlUserScript, 0, -1);
        $sqlUserScript .= ';' . "\n";
        $sqlCompanyUserScript = substr($sqlCompanyUserScript, 0, -1);
        $sqlCompanyUserScript .= ';' . "\n";
        $path = storage_path('app/public/shops.xlsx');
        if (file_exists($path)) {
            $sqlShopScript = 'INSERT INTO shops (shops_eid, shops_name, shops_sid, fk_channel, shops_is_active, shop_otp_message, shop_phone, shop_allowed_pull, shop_allowed_pull_reason, shop_allowed_push, shop_allowed_push_reason, shops_states, shops_created_at, shops_created_by, shops_updated_by, shops_updated_at, shops_is_reserve) VALUES ';
            $sqlShopCredentialScript = 'INSERT INTO shop_credential ( fk_shop, shop_credential_value, shop_credential_cookie, shop_credential_cookie_state, shop_credential_cookie_updated_at, shop_credential_open_api, shop_credential_open_api_updated_at, shop_credential_is_active, shop_credential_retry_get_credential_sc, shop_credential_last_retry, shop_credential_created_at, shop_credential_updated_at) VALUES ';
            $sqlShopUserScript = 'INSERT INTO shop_user ( fk_shop, fk_user, fk_brand, shop_user_role, shop_user_is_allocated, fk_subscription_details, shop_user_created_at, shop_user_created_by, shop_user_updated_at, shop_user_updated_by) VALUES ';
            $sqlUpdateLazadaScript = 'SET foreign_key_checks = 0;'."\n";
            $options = [
                'api_domain_simulation' => config('passport.api_domain_simulation'),
                'link_shop' => true,
            ];
            (new FastExcel())->import($path, function ($row) use ($type, &$sqlShopScript, &$sqlShopCredentialScript, &$sqlShopUserScript, $dataCompanyUser, $dataCompanyId, $options, &$sqlUpdateLazadaScript) {
                if ($type == 1) {
                    $options['shop_sid'] = $row["ShopSid"];
                    if (empty($row["seller_id"])) {
                        switch (strtoupper($row["channel_name"])) {
                            case CHANNEL_SHOPEE:
                                echo $row["shop_channel_id"] . "\n";
                                $response = ShopeeAuth::auth($row["username"], $row["pw"], $row["country_code"], '', $options);
                                $response = json_decode($response, true);
                                if ($response['success']) {
                                    echo $row["fk_shop_master"] . '--' . $response['shop_sid'] . "\n";
                                    echo $response['cookie_string'] . "\n";
                                    echo 'shop_sid: ' . $response['shop_sid'] . "\n";
                                    echo 'seller_id: ' . $response['seller_id'] . "\n";
                                    echo "===============================\n";
                                } else {
                                    print_r([$response, strtoupper($row["channel_name"]), $row["fk_shop_master"]]);
                                }
                                break;
                            case CHANNEL_LAZADA:
                                echo $row["shop_channel_id"] . "\n";
                                $response = LazadaAuth::auth($row["username"], $row["pw"], $row["country_code"], '', $options);
                                $response = json_decode($response, true);
                                if ($response['success']) {
                                    echo $row["fk_shop_master"] . '--' . $response['shop_sid'] . "\n";
                                    echo $response['cookie_string'] . "\n";
                                    echo 'account_type: ' . $response['account_type'] . "\n";
                                    echo 'seller_id: ' . $response['seller_id'] . "\n";
                                    echo "===============================\n";
                                } else {
                                    print_r([$response, strtoupper($row["channel_name"]), $row["fk_shop_master"]]);
                                }
                                break;
                            case CHANNEL_TOKOPEDIA:
                                echo $row["shop_channel_id"] . "\n";
                                $response = TokopediaAuth::auth($row["username"], $row["pw"], $row["country_code"], '', $options);
                                $response = json_decode($response, true);
                                if ($response['success']) {
                                    echo $row["fk_shop_master"] . '--' . $response['shop_sid'] . "\n";
                                    echo $response['cookie_string'] . "\n";
                                    echo 'seller_id: ' . $response['seller_id'] . "\n";
                                    echo "===============================\n";
                                } else {
                                    print_r([$response, strtoupper($row["channel_name"]), $row["fk_shop_master"]]);
                                }
                                break;
                            default:
                                break;
                        }
                    }
                } else if ($type == 2) {
                    $userAdminId = isset($dataCompanyUser[$row['Company']]) ? $dataCompanyUser[$row['Company']] : null;
                    $companyId = isset($dataCompanyId[$row['Company']]) ? $dataCompanyId[$row['Company']] : null;
                    if (strtoupper($row["channel_name"]) == CHANNEL_LAZADA) {
                        $sqlUpdateLazadaScript .= 'set @shops_eid = ' . $row['fk_shop_master'] . ';
UPDATE shops SET shops_eid = @shops_eid WHERE shops_eid = ' . $row['ShopSid'] . ';
UPDATE shop_credential SET fk_shop = @shops_eid WHERE fk_shop = (select shops_eid from shops where shops_sid =  ' . $row['ShopSid'] . ');
UPDATE shop_user SET fk_shop = @shops_eid WHERE fk_shop = (select shops_eid from shops where shops_sid =  ' . $row['ShopSid'] . ');
UPDATE shop_brand SET fk_shop = @shops_eid WHERE fk_shop = (select shops_eid from shops where shops_sid =  ' . $row['ShopSid'] . ');'."\n";


                    }
                    if (!empty($row['ShopSid']) && !empty($userAdminId) && !empty($companyId) && strtoupper($row["channel_name"]) == CHANNEL_SHOPEE) {
                        if (!empty($row['seller_id'])) {
                            $sellerId = ',"seller_id": ' . $row['seller_id'];
                        } else {
                            $sellerId = '';
                        }
                        $phone = !empty($row['shop_channel_phone']) ? $row['shop_channel_phone'] : 'null';
                        $sqlShopScript .= '(' . $row['fk_shop_master'] . ', "' . $row['shop_channel_name'] . '", ' . $row['ShopSid'] . ', ' . $row['fk_channel'] . ', 1, null, ' . $phone . ', 1, null, 1, null, "init", ' . $row['Created_at'] . ', ' . $userAdminId . ', ' . $userAdminId . ', ' . $row['Created_at'] . ', 0),';

                        if (!empty($row['access_token'])) {
                            $openApi = '\'{"access_token":"' . $row['access_token'] . '" , "refresh_token":"' . $row['refresh_token'] . '"}\'';
                        } else {
                            $openApi = 'null';
                        }

                        $sqlShopCredentialScript .= '(' . $row['fk_shop_master'] . ', \' {"password": "' . $row['pw'] . '", "user_name": "' . $row['username'] . '"' . $sellerId . '} \', null, 1, null ,' . $openApi . ' , null, 1, 0, null, ' . time() . ', null),';
                        $features = explode(',', $row['feature']);
                        foreach ($features as $feature) {
                            $subscriptionDetailsId = DB::table('features')
                                ->join('subscription_details', 'fk_features', 'features_id')
                                ->join('company_subscription_code', 'company_subscription_code_id', 'fk_company_subscription_code')
                                ->where('features_code', trim($feature))
                                ->where('fk_channel', $row['fk_channel'])
                                ->where('fk_company', $companyId)
                                ->pluck('subscription_details_id')->first();
                            if ($subscriptionDetailsId) {
                                $sqlShopUserScript .= '( ' . $row['fk_shop_master'] . ', ' . $userAdminId . ', null, "admin", 1, ' . $subscriptionDetailsId . ', ' . time() . ',  ' . $userAdminId . ', null, null),';
                            }
                        }
                    }
                }
            });

            $sqlShopScript = substr($sqlShopScript, 0, -1);
            $sqlShopScript .= ';' . "\n";
            $sqlShopCredentialScript = substr($sqlShopCredentialScript, 0, -1);
            $sqlShopCredentialScript .= ';' . "\n";
            $sqlShopUserScript = substr($sqlShopUserScript, 0, -1);
            $sqlShopUserScript .= ';' . "\n";
            $sqlUpdateLazadaScript .= 'SET foreign_key_checks = 1;'. "\n";
            echo $sqlUserScript;
            echo '=====================================done User========================================' . "\n";
            echo $sqlCompanyUserScript;
            echo '=====================================done Company User========================================' . "\n";
            echo $sqlShopScript;
            echo '=====================================done Shop========================================' . "\n";
            echo $sqlShopCredentialScript;
            echo '=====================================done Shop Credential========================================' . "\n";
            echo $sqlShopUserScript;
            echo '=====================================done Shop User========================================' . "\n";
            echo $sqlUpdateLazadaScript;
            echo '=====================================done Update Shop Lazada========================================' . "\n";
        } else {
            echo "The file $path does not exist";
        }
        return;
    }
}
