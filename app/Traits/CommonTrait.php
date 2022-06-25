<?php


namespace App\Traits;


use App\Helper\Code;
use App\Library\ELogger;

trait CommonTrait
{
    public function getRequestId($forceRefresh = false, $shopEid = null)
    {
        if ($forceRefresh) {
            if (!empty($shopEid)){
                $code = Code::generate_request_id().'-'.$shopEid;
            }else{
                $code = Code::generate_request_id();
            }

            return app()->instance('Pid', $code);
        }

        return app('Pid');
    }

    /**
     * @param $message
     * @param $context
     * @param string $logGroup
     * @param string $level Log Level: emergency,alert,critical,error,warning,notice,info,debug
     */
    public function debug($message, $context = [], $logGroup = '', $level = 'debug')
    {
        if (!$logGroup) {
            $class = get_called_class();
            $tmp = strrpos($class, '\\');
            $logGroup = $tmp !== false ? substr($class, $tmp + 1) : $class;
        }
        if (!$context) {
            $context = ['data' => ''];
        }
        if (!is_array($context)) {
            $context = ['data' => $context];
        }
        $requestId = $this->getRequestId();
        if (!in_array($level, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])) {
            $level = 'debug';
        }
        ELogger::{$level}("[#{$requestId}] $message", $context, $logGroup);
    }

    /**
     * @param $message
     * @param array $context
     * @param int $backtrackIndex
     * @param string $logGroup
     */
    public function logInfo($message, $context = [], $backtrackIndex = 1, $logGroup = '')
    {
        $bts = debug_backtrace();
        $bt = $bts[$backtrackIndex];
        $caller = Code::get_class_name($bt['class']) . '::' . $bt['function'];
        $this->debug("$caller > $message", $context, $logGroup, 'info');
    }

    /**
     * @param $message
     * @param array $context
     * @param int $backtrackIndex
     * @param string $logGroup
     */
    public function logError($message, $context = [], $backtrackIndex = 1, $logGroup = '')
    {
        $bt = debug_backtrace()[$backtrackIndex];
        $caller = Code::get_class_name($bt['class']) . '::' . $bt['function'];
        $this->debug("$caller > $message", $context, $logGroup, 'error');
    }


}
