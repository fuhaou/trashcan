<?php

namespace App\Library;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RawDataStorage
{
    public static $connection = null;

    public static function getInstance()
    {
        if (self::$connection == null) {
            self::$connection = DB::connection('mongodb');
        }

        return self::$connection;
    }

    /**
     * Store raw data or log request, log response to mongoDB.
     *
     * @param  string  $collectionName
     * @param  array  $data
     * @return void
     */
    public static function store(string $collectionName, array $data)
    {
        return self::getInstance()->collection($collectionName)->insert($data);
    }
}
