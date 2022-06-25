<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 19/11/2020
 * Time: 16:04.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Companies;
use App\Repositories\BaseSqlRepository;

class CompanyRepository extends BaseSqlRepository
{
    const MODERATOR_CODE = '0000';

    public function getModel()
    {
        return Companies::class;
    }
}
