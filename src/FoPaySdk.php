<?php

namespace Douyasi\Baofoo;

use Douyasi\Baofoo\Rsa;
use Douyasi\Baofoo\Tool;
use Douyasi\Baofoo\BaofooException;
use Douyasi\Baofoo\FoPayData;
use Douyasi\Baofoo\Bankcard;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


/**
 * Class FoPaySdk 宝付代付类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 * @date   2017-09-22
 */
class FoPaySdk
{
    /**
     * 符号含义
     * 
     * 1 M 强制域(Mandatory) 必须填写的属性,否则会被认为格式错误
     * 2 C 条件域(Conditional) 某条件成立时必须填写的属性
     * 3 O 选用域(Optional) 选填属性
     * 4 R 原样返回域(Returned) 必须与先前报文中对应域的值相同的域
     *
     * 本 PaySdk 支持的代付交易场景：
     * 
     * 代付交易接口(BF0040001) [支持]
     * 代付交易状态查证接口(BF0040002) [支持]
     * 代付交易退款查证接口(BF0040003) [支持]
     * 代付交易拆分接口(BF0040004) [支持]
     * 代付绑卡交易接口(BF0040006) [不支持]
     * 宝付账户实时交易接口(BF0040007) [不支持]
     * 账户收款方交易查证接口(BF0040010) [不支持]
     * 代付宝付回调接口 [支持]
     */

    /**
     * 默认配置 键名
     * 
     * @var array
     */
    protected $defaultConfigKey = [
                                    'terminal_id',
                                    'member_id',
                                ];

    /**
     * baofoo 请求参数配置
     * 
     * @var array
     */
    private $_config;

    /**
     * baofoo Rsa
     * 
     * @var \Douyasi\Baofoo\Rsa
     */
    private $_rsa;

    /**
     * baofoo 支付配置
     * 
     * @var array
     */
    protected $_bfpayConf;

    /**
     * 请求 url
     * 
     * @var string
     */
    private $_pay_request_url;


    /**
     * 日志
     *
     * @var Logger
     */
    private $_logger;

    /**
     * 构造函数
     * 
     * @param array $config
     */
    public function __construct($config, $bfpayConf)
    {
        if (!is_array($config) && !is_array($bfpayConf)) {
            throw new BaofooException('配置加载错误', BaofooException::BAOFOO_LOADING_CONFIG_ERROR);
        }
        // 公共参数 配置
        $defaultConfig = [
            'version'         => '4.0.0.0',  // 版本号
            'data_type'       => 'json',  // 加密报文的数据类型（xml/json）
            'terminal_id'     => '100000178',  // 默认测试商户号
            'member_id'       => '100000859',  // 默认测试终端号
        ];

        $this->_config = array_merge($defaultConfig, $config);

        $bfpayDefaultConf = [
            'fo_pay_env'               => 'TESTING',  // 环境，可选填写 测试 `TESTING` 或 生产 `PRODUCTION`，不同的环境对应请求宝付的 url 不同
            // 'timezone'              => 'Asia/Shanghai',  // 时区设置
            'public_key_path'          => __DIR__ . '/../res/cer/baofoo_pub.cer',  // 需要重载公钥文件路径
            'private_key_path'         => __DIR__ . '/../res/cer/m_pri.pfx',  // 需要重载私钥文件路径
            'private_key_password'     => '123456',  // 私钥密码
            'allowed_bind_credit_card' => false,  // 是否允许绑定信用卡
            'debug'                    => true,  // 是否开启 debug 模式
        ];

        if (isset($bfpayConf['public_key_path']) && empty($bfpayConf['public_key_path'])) {
            $bfpayConf['public_key_path'] = $bfpayDefaultConf['public_key_path'];
        }
        if (isset($bfpayConf['private_key_path']) && empty($bfpayConf['private_key_path'])) {
            $bfpayConf['private_key_path'] = $bfpayDefaultConf['private_key_path'];
        }
        $this->_bfpayConf = array_merge($bfpayDefaultConf, $bfpayConf);

        if (isset($this->_bfpayConf['timezone'])) {
            date_default_timezone_set($this->_bfpayConf['timezone']);
        } else {
            date_default_timezone_set('Asia/Shanghai');
        }

        if (isset($this->_bfpayConf['fo_pay_env']) && ($this->_bfpayConf['fo_pay_env'] == 'PRODUCTION')) {  // 如果是生产环境使用生产地址
            $this->_pay_request_url = [
                'BF0040001' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040001.do',  // 代付交易接口(BF0040001)
                'BF0040002' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040002.do',  // 代付交易状态查证接口(BF0040002)
                'BF0040003' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040003.do',  // 代付交易退款查证接口(BF0040003)
                'BF0040004' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040004.do',  // 代付交易拆分接口(BF0040004)
                'BF0040006' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040006.do',  // 代付绑卡交易接口(BF0040006)
                'BF0040007' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040007.do',  // 宝付账户实时交易接口(BF0040007)
                'BF0040010' => 'https://public.baofoo.com/baofoo-fopay/pay/BF0040010.do',  // 账户收款方交易查证接口(BF0040010)
                // 'CALLBACK'  => 'callback_url',  // 代付宝付回调接口 对 BF0040001 回调处理，需联系宝付自定义配置
            ];
        } else {
            $this->_pay_request_url = [
                'BF0040001' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do',  // 代付交易接口(BF0040001)
                'BF0040002' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do',  // 代付交易状态查证接口(BF0040002)
                'BF0040003' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040003.do',  // 代付交易退款查证接口(BF0040003)
                'BF0040004' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040004.do',  // 代付交易拆分接口(BF0040004)
                'BF0040006' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040006.do',  // 代付绑卡交易接口(BF0040006)
                'BF0040007' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040007.do',  // 宝付账户实时交易接口(BF0040007)
                'BF0040010' => 'http://paytest.baofoo.com/baofoo-fopay/pay/BF0040010.do',  // 账户收款方交易查证接口(BF0040010)
                // 'CALLBACK'  => 'callback_url',  // 代付宝付回调接口 对 BF0040001 回调处理，需联系宝付自定义配置
            ];
        }
        $this->_rsa = new Rsa($this->_bfpayConf);

        $logger = new Logger('baofoo_fopay_logger');
        $logPath = (isset($this->_bfpayConf['logger_path']) && !empty($this->_bfpayConf['logger_path'])) ? rtrim($this->_bfpayConf['logger_path'], '/').'/baofoo-fopay-'.date('Ymd').'.log' : __DIR__ . '/../log/baofoo-fopay-'.date('Ymd').'.log';
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
        $this->_logger = $logger;
    }

    /**
     * 获取默认配置
     * 
     * @return array
     */
    public function getDefaultConfig()
    {
        $config = [];
        $_config = $this->_config;
        foreach ($this->defaultConfigKey as $k) {
            if (array_key_exists($k, $_config)) {
                $config[$k] = $_config[$k];
            }
        }
        return $config;
    }

    /**
     * apiResponse 按照宝付代付响应的数据格式组合
     * 
     * @param  int $code
     * @param  array $message
     * @return mixed
     */
    public function apiResponse($code, $message)
    {
        $response  = [
            'trans_content' => [
                'trans_head' => [
                    'return_code' => $code,
                    'return_msg' => $message,
                ],
            ],
        ];
        return json_encode($response);
    }

    /**
     * agentPay
     * (BF0040001) 代付交易接口 : 该接口的主要功能为代付交易
     * 代付交易一次处理的请求条数有限制，不超过5个，超过5个：交易请求记录条数超过上限! (下面部分接口类似，不再在注释中赘述了)
     * 
     * @param  array ...$params 接受类似于以下结构的数组参数
     * [
     *       'trans_no'      => '',  // 商户订单号 M
     *       'trans_money'   => '',  // 转账金额 M 
     *       'to_acc_name'   => '',  // 收款人姓名 M
     *       'to_bank_name'  => '',  // 收款人银行名称 M
     *       'to_pro_name'   => '',  // 收款人开户行省名 C
     *       'to_city_name'  => '',  // 收款人开户行市名 C
     *       'to_acc_dept'   => '',  // 收款人开户行机构名 C
     *       'trans_card_id' => '',  // 银行卡身份证件号码 C
     *       'trans_mobile'  => '',  // 银行卡预留手机号 C
     *       'trans_summary' => '',  // 摘要 R
     * ]
     * @return array
     */
    public function agentPay(...$params)
    {
        $fpData = new FoPayData();
        if (count($params) == 1) {  // 如果只有一个入参参数
            $data = $params[0];
            if (isset($data['trans_no']) && isset($data['trans_money']) && isset($data['to_acc_name']) && isset($data['to_bank_name'])) {  // 一维数组单条数据,简单校验代付必填项是否均有值
                $fpData->fillData($data);
            } else {  // 多维数组
                foreach ($data as $d) {
                    if (isset($d['trans_no']) && isset($d['trans_money']) && isset($d['to_acc_name']) && isset($d['to_bank_name'])) {  // 简单校验代付必填项是否均有值
                        $fpData->fillData($d);
                    } else {
                        return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                    }
                }
            }
        } else {
            foreach ($params as $data) {
                if (isset($data['trans_no']) && isset($data['trans_money']) && isset($data['to_acc_name']) && isset($data['to_bank_name'])) {  // 简单校验代付必填项是否均有值
                    $fpData->fillData($data);
                } else {
                    return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                }
            }
        }

        $request_url = $this->_pay_request_url['BF0040001'];
        $data_content = $fpData->get();
        return $this->_post($data_content, $request_url);
    }

    /**
     * agentPayStatusQuery
     * (BF0040002) 代付交易状态查证接口 : 该接口的主要功能为查询代付交易状态
     * 
     * @param  array ...$params 接受类似于以下结构的数组参数
     * [
     *     'trans_batchid' => '',  // 宝付批次号 O
     *     'trans_no'      => '',  // 商户订单号 M
     * ]
     * @return array
     */
    public function agentPayStatusQuery(...$params)
    {
        $fpData = new FoPayData();
        if (count($params) == 1) {  // 如果只有一个入参参数
            $data = $params[0];
            if (isset($data['trans_no'])) {  // 一维数组单条数据,简单校验代付必填项是否均有值
                $fpData->fillData($data);
            } else {  // 多维数组
                foreach ($data as $d) {
                    if (isset($d['trans_no'])) {  // 简单校验代付必填项是否均有值
                        $fpData->fillData($d);
                    } else {
                        return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                    }
                }
            }
        } else {
            foreach ($params as $data) {
                if (isset($data['trans_no'])) {  // 简单校验代付必填项是否均有值
                    $fpData->fillData($data);
                } else {
                    return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                }
            }
        }

        $request_url = $this->_pay_request_url['BF0040002'];
        $data_content = $fpData->get();
        return $this->_post($data_content, $request_url);
    }


    /**
     * agentPayRefundQuery
     * (BF0040003) 代付交易退款查证接口 : 该接口的主要功能为查询代付交易退款订单
     * 代付交易退款查证一次处理的请求条数 (trans_reqData) 有限制，不超过 1 个，且时间限制为同为一天
     *
     * @param  array $params 接受以下结构的数组参数
     * [
     *     'trans_btime' => '20171007',  // 格式:YYYYMMDD(最多查询一天记录) 查询起始时间 M
     *     'trans_etime' => '20171007',  // 格式:YYYYMMDD(最多查询一天记录) 查询结束时间 M 
     * ]
     * @return array
     */
    public function agentPayRefundQuery($params)
    {
        $fpData = new FoPayData();
        if (isset($params['trans_btime']) && isset($params['trans_etime'])) {
            $fpData->fillData($params);
        } else {
            return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
        }

        $request_url = $this->_pay_request_url['BF0040003'];
        $data_content = $fpData->get();
        return $this->_post($data_content, $request_url);
    }

    /**
     * agentPaySplit
     * (BF0040004) 代付交易拆分接口 : 该接口的主要功能为代付交易拆封接口。原接口BF0040001不能对私进行拆分，不能满足客户需求，故增加本接口.
     * 请求报文体中的 `trans_no` 要求全局唯一，如果发现有重复则报错
     * 
     * @param  array array ...$params 接受类似于以下结构的数组参数
     * [
     *       'trans_no'      => '',  // 商户订单号 M
     *       'trans_money'   => '',  // 转账金额 M 
     *       'to_acc_name'   => '',  // 收款人姓名 M
     *       'to_bank_name'  => '',  // 收款人银行名称 M
     *       'to_pro_name'   => '',  // 收款人开户行省名 C
     *       'to_city_name'  => '',  // 收款人开户行市名 C
     *       'to_acc_dept'   => '',  // 收款人开户行机构名 C
     *       'trans_card_id' => '',  // 银行卡身份证件号码 C
     *       'trans_mobile'  => '',  // 银行卡预留手机号 C
     *       'trans_summary' => '',  // 摘要 R
     * ]
     * @return array
     */
    public function agentPaySplit(...$params)
    {
        $fpData = new FoPayData();
        if (count($params) == 1) {  // 如果只有一个入参参数
            $data = $params[0];
            if (isset($data['trans_no']) && isset($data['trans_money']) && isset($data['to_acc_name']) && isset($data['to_bank_name'])) {  // 一维数组单条数据,简单校验代付必填项是否均有值
                $fpData->fillData($data);
            } else {  // 多维数组
                foreach ($data as $d) {
                    if (isset($d['trans_no']) && isset($d['trans_money']) && isset($d['to_acc_name']) && isset($d['to_bank_name'])) {  // 简单校验代付必填项是否均有值
                        $fpData->fillData($d);
                    } else {
                        return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                    }
                }
            }
        } else {
            foreach ($params as $data) {
                if (isset($data['trans_no']) && isset($data['trans_money']) && isset($data['to_acc_name']) && isset($data['to_bank_name'])) {  // 简单校验代付必填项是否均有值
                    $fpData->fillData($data);
                } else {
                    return $this->apiResponse('0003', '商户代付报文格式不正确,请检查入参');
                }
            }
        }

        $request_url = $this->_pay_request_url['BF0040004'];
        // 传入 0交易笔数 0交易总额，FoPaySdk 会自动重新计算交易笔数与总额
        $trans_head = [
            'trans_count' => 0,
            'trans_totalMoney' => 0,
        ];
        $data_content = $fpData->get(false, $trans_head);
        return $this->_post($data_content, $request_url);
    }

    /**
     * _post post请求通用方法
     * 
     * @param  array $data [接口请求需要数据]
     * @param  string $url [接口请求url]
     * @return mixed
     */
    private function _post($data, $url)
    {
        $rsa = $this->_rsa;
        $defaultConfig = $this->getDefaultConfig();

        $version = (isset($defaultConfig['version'])) ? $defaultConfig['version'] : '4.0.0';  // 版本号
        $terminal_id     = (isset($defaultConfig['terminal_id'])) ? $defaultConfig['terminal_id'] : '100000178';  // 终端号(M)
        $member_id       = (isset($defaultConfig['member_id'])) ? $defaultConfig['member_id'] : '100000859';  // 商户号(M),宝付提供给商户的唯一编号
        $data_type       = (isset($defaultConfig['data_type'])) ? $defaultConfig['data_type'] : 'json';  //加密报文的数据类型,默认为 `json`(且本 `Sdk` 也仅支持 `json`)


        $jsonData = json_encode($data);
        $this->_logger->debug('-----BAOFOO FOPAY DEBUG START-----');
        $this->_logger->info('baofoo data_content data:  ', $data);

        // 组装请求数据
        $postData = [
            'version'      => $version,
            'terminal_id'  => $terminal_id,
            'member_id'    => $member_id,
            'data_type'    => $data_type,
            'data_content' => $rsa->encryptedByPrivateKey($jsonData),
        ];

        // 请求宝付接口
        try {
            $retData = $this->_curl_post($postData, $url);
            $this->_logger->info('baofoo response raw data:  ', [$retData]);

            if (count(explode('trans_content', $retData)) > 1) {
                // 特殊异常会返回明文响应，明文响应中会有 `trans_content` 字段
                $retBody = $retData;
            } else {
                $retBody = $this->_decryptByPublicKey($retData);
            }

            $resData = json_decode($retBody, true);
            $this->_logger->info('baofoo response decrypted data:  ', $resData);
            $apiRet = $resData;  // 这里不做输出转换了
            // $this->_logger->info('douyasi/baofoo api return data:  ', $apiRet);
            $this->_logger->debug('-----BAOFOO FOPAY DEBUG END-----');
            return $apiRet;
        } catch (\Exception $e) {
            $this->_logger->error('baofoo request throw exception:  ', ['code' => $e->getCode(), 'msg' => $e->getMessage()]);
            $this->_logger->debug('-----BAOFOO FOPAY DEBUG END-----');
            throw new BaofooException($e->getMessage(), $e->getCode());
        }

    }


    /**
     * _curl_post  由 Curl驱动的 POST 请求
     * 
     * @param  array $postData post数据
     * @param  string $url  请求的url
     * @return mixed
     */
    private function _curl_post($postData, $url)
    {
        $formString = http_build_query($postData);  //格式化参数

        $curl = curl_init();  // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);  // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_POST, true);  // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $formString);  // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);  // 设置超时限制防止死循环返回
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $retData = curl_exec($curl);  // 执行操作

        $this->_logger->info('baofoo request data:  ', ['post_data' => $postData, 'form_string' => $formString, 'request_url' => $url]);

        if (curl_errno($curl)) {
            $error = curl_error($curl);  // 捕抓异常
            $this->_logger->error('curl request data error:  '.$error);
            throw new BaofooException('Curl 请求数据异常：'.$error, BaofooException::CURL_POST_DATA_ERROR);
        }
        curl_close($curl);  // 关闭CURL会话
        return $retData;
    }

    /**
     * 解密数据
     * 
     * @param  string $decrypted_str 密文数据
     * @return string 解密之后的数据
     */
    private function _decryptByPublicKey($decrypted_str)
    {
        return $this->_rsa->decryptByPublicKey($decrypted_str);
    }

}