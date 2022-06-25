<?php

namespace App\Repositories;

use App\Library\QueryPaginator;
use App\Library\QuerySorter;
use Illuminate\Support\Facades\DB;

class PostRepository
{
    public function search($title, QuerySorter $sorter = null, QueryPaginator $paginator = null)
    {
        $query = Db::table('posts')->where('title', 'like', "%{$title}%");

        if ($sorter) {
            $query = $sorter->applyQuery($query);
        }

        if (! $paginator) {
            return $query->get();
        }

        $paginator->applyQuery($query);

        return $paginator;
    }
}
