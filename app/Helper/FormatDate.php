<?php

namespace App\Helper;

use App\Models\Sql\CompanyRegisterCode;
use App\Models\Sql\CompanySubscriptionCode;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\String_;

class FormatDate
{
    public static function dateTime($time)
    {
        return date('d-m-Y H:i A', $time);
    }

}
