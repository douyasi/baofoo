<?php

namespace Douyasi\Baofoo;

/**
 * Class BaofooException 宝付异常类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 */
class BaofooException extends \Exception
{

    public function __construct($message = '', $code = 0, $previous = null)
    {
        if (!empty($message) && is_string($message)) {
            parent::__construct($message, $code, $previous);
        } else {
            $msg = isset(self::$errorMessages[$code]) ? self::$errorMessages[$code] : 'error code : '.$code .' !';
            parent::__construct($msg, $code, $previous);
        }
    }

    const ALIPAY_GET_BANKCARD_INFO_FAILURE = 666666001;
    const BAOFOO_GET_RSA_INFO_ERROR        = 666666101;
    const BAOFOO_LOADING_CONFIG_ERROR      = 666666102;
    const CURL_POST_DATA_ERROR             = 666666901;
    const CURL_NOT_INSTALLED               = 666666902;

    public static $errorMessages = [
        self::ALIPAY_GET_BANKCARD_INFO_FAILURE => '获取银行卡信息失败',
        self::BAOFOO_GET_RSA_INFO_ERROR        => '获取宝付证书相关数据异常',
        self::BAOFOO_LOADING_CONFIG_ERROR      => '加载配置错误',
        self::CURL_POST_DATA_ERROR             => 'CURL 请求数据异常',
        self::CURL_NOT_INSTALLED               => 'CURL 组件没有安装',
    ];
}