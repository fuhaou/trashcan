<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 04/12/2020
 * Time: 11:46
 */

namespace App\Repositories\Sql;


use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use App\Models\Sql\Companies;
use App\Models\Sql\Partnership;
use App\Repositories\BaseSqlRepository;

class PartnershipRepository extends BaseSqlRepository
{
    const COMPANY_EPSILO_ID = 1;

    public function getModel()
    {
        return Partnership::class;
    }

    public function search($companyId, QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $model = $this->_model->newQuery()->join(
            Companies::TABLE_NAME,
            Companies::COL_COMPANIES_ID,
            '=',
            Partnership::COL_PARTNERSHIP_TO
        )->where(Partnership::COL_PARTNERSHIP_FROM, $companyId);
        if ($sorter) {
            $model = $sorter->applyQuery($model);
        }
        if (!$paginator) {
            return $model->get();
        }
        $paginator->applyQuery($model);
        return $paginator;
    }
}
