<?php
/**
 * Created by PhpStorm.
 * User: tuonglv
 * Date: 10/26/2020.
 */

namespace App\Library;

use Carbon\Carbon;
use Fluent\Logger\FluentLogger;
use Illuminate\Support\Facades\Log;

class FileStorage
{
    public static $logger = null;

    public static function getInstance()
    {
        if (self::$logger == null) {
            Log::info('connecting fluentd', config('fluent'));
            self::$logger = new FluentLogger(config('fluent')['host'], config('fluent')['port']);
        }

        return self::$logger;
    }

    public static function store(string $fileName, array $data)
    {
        if (! $fileName or ! $data) {
            Log::warning('file_name and data must not empty', ['file_name' => $fileName, 'data' => $data]);

            return false;
        }
        $fileName = 'data_file.'.strtolower($fileName);
        $data['@timestamp'] = Carbon::now()->toIso8601String();

        return self::getInstance()->post($fileName, $data);
    }
}
