<?php
namespace App\Repositories\Sql;

use App\Models\Sql\AllocateHistory;
use App\Repositories\BaseSqlRepository;

/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 19/11/2020
 * Time: 10:50
 */

class AllocateHistoryRepository extends BaseSqlRepository
{
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';
    const TYPE_ADD = 'add';
    const TYPE_REMOVE = 'remove';

    public function getModel()
    {
        return AllocateHistory::class;
    }

}
