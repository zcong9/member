<?php

namespace Common\Exceptions;

use Common\Extend\Reponse;

class ValidateException extends \RuntimeException
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        if (is_array($message)) {
            $errInfo = $message;
            $message = $errInfo[1] ?: '未知错误';
            if ($code === 0) {
                $code = $errInfo[0] ?: 400;
            }
        }
        if (in_array(strtolower(MODULE_NAME), C('RESPONSE_JSON_MODULE'))) {
            echo Reponse::jsonStr($code, $message);
            exit;
        }
        parent::__construct($message, $code, $previous);
    }
}
