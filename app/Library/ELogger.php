<?php

namespace App\Library;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Ytake\LaravelFluent\FluentHandler;

class ELogger
{
    const LOG_GROUP_DEFAULT = 'default';
    const LOG_GROUP_CURL = 'log_curl';

    public static function critical($message, $context, $logGroup = self::LOG_GROUP_DEFAULT)
    {
        $context['_log_group'] = $logGroup;
        $context['context'] = $context;
        Log::critical($message, $context);
    }

    public static function error($message, $context, $logGroup = self::LOG_GROUP_DEFAULT)
    {
        $context['_log_group'] = $logGroup;
        $context['context'] = $context;
        Log::error($message, $context);
    }

    public static function warning($message, $context, $logGroup = self::LOG_GROUP_DEFAULT)
    {
        $context['_log_group'] = $logGroup;
        $context['context'] = $context;
        Log::warning($message, $context);
    }

    public static function info($message, $context, $logGroup = self::LOG_GROUP_DEFAULT)
    {
        $context['_log_group'] = $logGroup;
        $context['context'] = $context;
        Log::info($message, $context);
    }

    public static function debug($message, $context, $logGroup = self::LOG_GROUP_DEFAULT)
    {
        $context['_log_group'] = $logGroup;
        $context['context'] = $context;
        Log::debug($message, $context);
    }
}

class FluentLoggerCustom extends FluentHandler
{
    protected function write(array $record): void
    {
        $tag = strtolower($this->populateTag($record));
        $context = $this->getContext($record['context']);
        $logGroup = ELogger::LOG_GROUP_DEFAULT;
        if ($context and is_array($context) and ! empty($context['_log_group'])) {
            $logGroup = strtolower($context['_log_group']);
        }
        $message = $record['message'];
        $message = substr($message, 0, 2000);
        $contextStr = substr(json_encode($context), 0, 40000);
        $this->logger->post(
            $tag,
            [
                'message' => $message,
                'context' => $contextStr,
                'log_level' => strtolower($record['level_name']),
                'log_group' => $logGroup,
                'duration' => $context['duration'] ?? 0,
                '@timestamp' => Carbon::now('UTC')->toIso8601String(),
            ]
        );
    }
}
