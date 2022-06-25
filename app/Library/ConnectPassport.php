<?php

namespace App\Library;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

class ConnectPassport
{
    use ApiResponse;

    private $token = '';

    public function getToKen()
    {
        return ! empty($this->token) ? $this->token : $this->__callGetToken();
    }

    private function __callGetToken()
    {
        $data = [
            'email' => config('passport.credential.email'),
            'password' => config('passport.credential.password'),
        ];
        $url = config('passport.url.api').'/auth/login';
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        /** @var CurlResponse $response */
        $response = Curl::doPost($url, json_encode($data), null, $headers)->getJson(true);
        if ($response && $response['code'] == 1) {
            return $response['data']['token'] ?? '';
        }

        return '';
    }

    private function __callApi($url, $data, $method = 'GET')
    {
        $token = $this->getToKen();
        $headers = [];
        $headers[] = 'Authorization: Bearer '.$token;
        $headers[] = 'Content-Type: application/json';
        switch ($method) {
            case 'POST':
                $response = Curl::doPost($url, json_encode($data), null, $headers);
                break;
            default:
                $response = Curl::doGet($url, null, $data, $headers);
                break;
        }

        return $response->getJson(true);
    }

    public function login($username, $password, $remember, $device, $ip)
    {
        $url = config('passport.url.api').'/v2/user/login';
        $param = [
            'email' => $username,
            'password' => $password,
            'remember' => $remember,
            'device' => $device,
            'ip' => $ip,
        ];
        $responseRaw = $this->__callApi($url, $param, 'POST');
        if (isset($responseRaw['code'])) {
            $response = $this->__rebuildResponse($responseRaw['data']);
        }

        return $response ?? [];
    }

    public function logout($username, $sessionKey)
    {
        $url = config('passport.url.api').'/v2/user/logout';
        $param = [
            'email' => $username,
            'key' => $sessionKey,
        ];

        return $this->__callApi($url, $param, 'GET');
    }

    public function getShop($userId, $shopId = null, $channelId = null, $countryId = null, $limit = 100)
    {
        $url = config('passport.url.api').'/v1/shops';
        $param = [
            'user' => $userId,
            'storeId' => $shopId,
            'channel' => $channelId,
            'country' => $countryId,
            'limit' => $limit,
        ];
        $responseRaw = $this->__callApi($url, $param, 'GET');
        if (isset($responseRaw['code'])) {
            $response = [];
            foreach ($responseRaw['data']['data'] ?? [] as $key => $value) {
                $response[$key] = $this->__rebuildResponse($value);
            }
        }

        return $response ?? [];
    }

    public function getUserById($userId)
    {
        $url = config('passport.url.api').'/v1/users/'.$userId;
        $responseRaw = $this->__callApi($url, [], 'GET');
        if (isset($responseRaw['code'])) {
            return isset($responseRaw['data']) ? $this->__rebuildResponse($responseRaw['data']) : [];
        }

        return [];
    }

    public function getAcl($userId, $listShopId)
    {
        $url = config('passport.url.api').'/v1/acl';
        $param = [
            'userId' => $userId,
            'listShopId' => $listShopId,
        ];
        $responseRaw = $this->__callApi($url, $param, 'GET');
        if (isset($responseRaw['code'])) {
            $response = [];
            foreach ($responseRaw['data'] ?? [] as $key => $value) {
                $response[$key] = $this->__rebuildResponse($value);
            }
        }

        return $response ?? [];
    }

    private function __rebuildResponse($data)
    {
        $mappingKey = [
            'fk_user' => 'user_id',
            'shop_channel_id' => 'shop_eid',
            'shop_channel_name' => 'shop_name',
            'shop_channel_user_tab' => 'shop_user_tab',
            'shop_channel_allowed_pull' => 'shop_allowed_pull',
            'shop_channel_allowed_push' => 'shop_allowed_push',
            'fk_tool' => 'feature_code',
            'fk_channel' => 'channel_id',
            'fk_tool_channel' => 'group_feature_code',
            'fk_config_active' => 'config_active',
            'fk_organization' => 'company_id',
            'fk_country' => 'country_id',
        ];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $mappingKey)) {
                $data[$mappingKey[$key]] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }
}
