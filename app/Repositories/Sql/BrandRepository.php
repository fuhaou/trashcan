<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Actions;
use App\Models\Sql\Brands;
use App\Models\Sql\Features;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class BrandRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Brands::class;
    }

    public function search($code = null)
    {
        $model = $this->_model->newQuery();
        if ($code) {
            $model->where(Brands::COL_BRANDS_CODE, $code);
        }

        return $model->get();
    }
}
