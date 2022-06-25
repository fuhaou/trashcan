<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 18/11/2020
 * Time: 16:56
 */

namespace App\Repositories\Sql;

use App\Models\Sql\SubscriptionDetailsShop;
use App\Repositories\BaseSqlRepository;

class SubscriptionDetailShopRepository extends BaseSqlRepository
{
    /**
     * @return string
     */
    public function getModel()
    {
        return SubscriptionDetailsShop::class;
    }

}
