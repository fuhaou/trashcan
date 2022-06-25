<?php

namespace App\Helper;

use App\Models\Sql\CompanyRegisterCode;
use App\Models\Sql\CompanySubscriptionCode;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\String_;

class Code
{
    const SECONDS = 900; //15'

    public static function verifyRegister()
    {
        return sprintf('%06d', mt_rand(1, 999999));
    }

    public static function registrationCode()
    {
        $loop = 0;
        $length = 10;
        do {
            $registerCode = sprintf('R%s', Str::random($length));
            $registerCodeInfo = CompanyRegisterCode::query()
                ->where(CompanyRegisterCode::COL_COMPANY_REGISTER_CODE_VALUE, '=', $registerCode)
                ->first();
            $loop++;
            if ($loop >= 10) {
                $length++;
            }
        } while ($registerCodeInfo);

        return $registerCode;
    }

    public static function subscriptionCode($companyId)
    {
        $loop = 0;
        $length = 10;
        do {
            $subscriptionCode = sprintf('S%s', Str::random($length));
            $subscriptionCodeInfo = CompanySubscriptionCode::query()
                ->where(CompanySubscriptionCode::COL_COMPANY_SUBSCRIPTION_CODE_VALUE, $subscriptionCode)
                ->where(CompanySubscriptionCode::COL_FK_COMPANY, $companyId)
                ->first();
            $loop++;
            if ($loop >= 10) {
                $length++;
            }
        } while ($subscriptionCodeInfo);

        return $subscriptionCode;
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generate_request_id($prefix = '', $lastfix = '')
    {
        return ($prefix ? $prefix : '').date('YmdHis').self::generateRandomString(6).($lastfix ? $lastfix : '');
    }

    public static function get_class_name($class)
    {
        if ($pos = strrpos($class, '\\')) {
            return substr($class, $pos + 1);
        }
        return $class;
    }
}
