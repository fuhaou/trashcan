<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 11/11/2020
 * Time: 14:32.
 */

namespace App\Repositories\Sql;

use App\Models\Sql\Actions;
use App\Models\Sql\Features;
use App\Repositories\BaseSqlRepository;
use Illuminate\Support\Facades\DB;

class ActionRepository extends BaseSqlRepository
{
    public function getModel()
    {
        return Actions::class;
    }

    public function search($code = null)
    {
        $model = $this->_model->newQuery()->join(Features::TABLE_NAME, Actions::COL_FK_FEATURE, Features::COL_FEATURES_ID);
        if ($code) {
            $model->where(Actions::COL_ACTIONS_CODE, $code);
        }

        return $model->get();
    }
}
