<?php


namespace TpCheck;


use Throwable;

class ParamException extends \Exception
{

    public function __construct($message = "请求参数验证错误", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
