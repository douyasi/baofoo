<?php

namespace Douyasi\Baofoo;

use Douyasi\Baofoo\Rsa;
use Douyasi\Baofoo\Tool;
use Douyasi\Baofoo\BaofooException;
use Douyasi\Baofoo\Bankcard;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Sdk
 * 宝付 SDK 类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 */
class Sdk
{

    /**
     * 符号含义
     * 
     * 1 M 强制域(Mandatory) 必须填写的属性,否则会被认为格式错误
     * 2 C 条件域(Conditional) 某条件成立时必须填写的属性
     * 3 O 选用域(Optional) 选填属性
     * 4 R 原样返回域(Returned) 必须与先前报文中对应域的值相同的域
     *
     * 交易子类
     * 
     * 01 直接绑卡类交易
     * 02 解除绑定关系类交易
     * 03 查询绑定关系类交易
     * 
     * 04 支付类交易 [已废弃]
     * 05 发送短信类交易 [已废弃]
     * 
     * 06 交易状态查询类交易 [已变更为31]
     *
     * 11 预绑卡类交易
     * 12 确认绑卡类交易
     * 15 预支付交易(发送短信)
     * 16 支付确认交易
     * 31 交易状态查询类交易
     */

    /**
     * 默认配置 键名
     * 
     * @var array
     */
    protected $defaultConfigKey = [
                                    'biz_type', 
                                    'id_card_type',
                                    /*
                                    'acc_pwd',
                                    'valid_date',
                                    'valid_no',
                                    */
                                    'additional_info',
                                    'req_reserved',
                                    'terminal_id',
                                    'member_id',
                                    'trans_serial_no',
                                    /*
                                    'trade_date'
                                    */
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
    private $_request_url;

    /**
     * 日志
     *
     * @var Logger
     */
    private $_log;

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
        $defaultConfig = [
            'version'         => '4.0.0.0', //版本号
            'data_type'       => 'json', //加密报文的数据类型（xml/json）
            'txn_type'        => '0431', //交易类型
            'biz_type'        => '0000', //接入类型
            'id_card_type'    => '01', //证件类型固定01（身份证） 
            'acc_pwd'         => '',  //银行卡密码（传空）
            'valid_date'      => '', //卡有效期 （传空）
            'valid_no'        => '', //卡安全码（传空）
            'additional_info' => '', //附加字段
            'req_reserved'    => '', //保留
        ];
        $this->_request_url = (isset($config['request_url']) && !empty($config['request_url'])) ? $config['request_url'] : 'https://tgw.baofoo.com/cutpayment/api/backTransRequest';
        $this->_config = array_merge($defaultConfig, $config);

        $bfpayDefaultConf = [
            // 'timezone'                 => 'Asia/Shanghai',  // 时区设置
            'private_key_password'     => '123456',  // 私钥密码
            'allowed_bind_credit_card' => false,  // 是否允许绑定信用卡
            'debug'                    => true,  // 是否开启 debug 模式
        ];

        $this->_bfpayConf = array_merge($bfpayDefaultConf, $bfpayConf);

        if (isset($this->_bfpayConf['timezone'])) {
            date_default_timezone_set($this->_bfpayConf['timezone']);
        } else {
            date_default_timezone_set('Asia/Shanghai');
        }

        $this->_rsa = new Rsa($this->_bfpayConf);
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
     * 实名建立绑定关系类交易
     * 
     * @param  array $bindData
     * @return mixed
     */
    public function bindCard($bindData)
    {
        $params = [
            'txn_sub_type'    => '01',  // 交易子类(M)
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'trans_id'        => Tool::generateTransId(),  //商户订单号(M),唯一订单号,8-20 位字母和数字,同一天内不可重复;
            'acc_no'          => '',  // 绑定卡号(M),请求绑定的银行卡号
            'id_card_type'    => '01',  // 身份证类型(O),默认 01 为身份证号
            'id_card'         => '',  // 身份证号(M)
            'id_holder'       => '',  // 持卡人姓名(M)
            'mobile'          => '',  // 银行卡绑定手机号(M),预留手机号
            'valid_date'      => '',  // 卡有效期(C)
            'valid_no'        => '',  // 卡安全码(C),银行卡背后最后三位数字
            'pay_code'        => '',  // 银行编码(M),建议不要手动传 `pay_code` , sdk 会根据卡号自动查询得到 `pay_code` ，而且会根据配置 限制是否允许绑定信用卡
            'trade_date'      => '',  // 订单日期(M),可以不传，sdk 根据当前日期自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $bindData);

        if (!$data['pay_code']) {  // 建议不要手动传 `pay_code`
            try {
                $card = Bankcard::info($data['acc_no']);
                if ($card['validated']) {
                    if (!$this->_bfpayConf['allowed_bind_credit_card'] && $card['cardType'] == 'CC') {
                        throw new BaofooException('不支持信用卡', BaofooException::CREDIT_CARD_NOT_ALLOWED);
                    }
                    $data['pay_code'] = Tool::getPayCode($card['bank']);
                    return $this->_post($data);
                } else {
                    throw new BaofooException('银行卡BIN非法，请输入合法的银行卡号', BaofooException::BANKCARD_NUMBER_ILLEGAL);
                }

            } catch (\Exception $e) {
                throw $e;
            }

        } else {
            return $this->_post($data);
        }
    }

    /**
     * unbindCard 解除绑定关系类交易
     * 
     * @param  array $unbindData
     * @return mixed
     */
    public function unbindCard($unbindData)
    {
        $params = [
            'txn_sub_type'    => '02',  // 交易子类(M)
            'bind_id'         => '',  // 绑定标识号(M),用于绑定关系的唯一标识
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'trade_date'      => '',  // 订单日期(M),可以不传,sdk 根据当前日期自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $unbindData);

        return $this->_post($data);
    }

    /**
     * queryBindCard 查询绑定关系类交易
     * 
     * @param  array $queryBindData
     * @return mixed
     */
    public function queryBindCard($queryBindData)
    {
        $params = [
            'txn_sub_type'    => '03',  // 交易子类(M)
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'trade_date'      => '',  // 订单日期(M),可以不传,sdk 根据当前日期自动生成
            'acc_no'          => '',  // 绑定的卡号(M),请求绑定的银行卡号
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $queryBindData);

        return $this->_post($data);
    }

    /**
     * preBindCard 预绑卡
     *
     * @param  array $bindData
     * @return mixed
     */
    public function preBindCard($bindData)
    {
        $params = [
            'txn_sub_type'    => '11',  // 交易子类(M)
            'trans_serial_no' => '',  // 商户流水号(M),可以不传，sdk 根据当前日期自动生成
            'trans_id'        => Tool::generateTransId(), // 商户订单号(M),唯一订单号,8-20 位字母和数字,同一天内不可重复
            'acc_no'          => '',  // 绑定卡号(M),请求绑定的银行卡号
            'id_card_type'    => '01',  // 身份证类型(O),默认 01 为身份证号
            'id_card'         => '',  // 身份证号(M)
            'id_holder'       => '',  // 持卡人姓名(M)
            'mobile'          => '',  // 银行卡绑定手机号(M),预留手机号
            'valid_date'      => '',  // 卡有效期(C)
            'valid_no'        => '',  // 卡安全码(C),银行卡背后最后三位数字
            'pay_code'        => '',  // 银行编码(M)
            'trade_date'      => '',  // 订单日期(M),可以不传，sdk 根据当前日期自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $bindData);

        return $this->_post($data);
    }

    /**
     * doBindCard 确定绑卡
     *
     * @param  array $bindData
     * @return mixed
     */
    public function doBindCard($bindData)
    {
        $params = [
            'txn_sub_type'    => '12',  // 交易子类(M)
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'trans_id'        => '',  // 这里传入的是 预绑卡接口返回的商户订单号
            'sms_code'        => '',  // 短信验证码(M)
            'trade_date'      => '',  // 订单日期(M),可以不传,sdk 会自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $bindData);

        return $this->_post($data);
    }

    /**
     * prePay 认证支付预支付类交易
     * 
     * @param  array $payData
     * @return mixed
     */
    public function prePay($payData)
    {
        $params = [
            'txn_sub_type'    => '15',  // 交易子类(M)
            'trans_id'        => Tool::generateTransId(),  // 商户订单号(M),唯一订单号,8-20 位字母和数字,同一天内不可重复
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'bind_id'         => '',  // 绑定标识号(M),用于绑定关系的唯一标识
            'txn_amt'         => '',  // 短信验证码(C),单位:分 例:1 元则提交 100
            'trade_date'      => '',  // 订单日期(M),可以不传,sdk 会自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
            'risk_content'    => [
                                    'client_ip' => '127.0.0.1',
                                 ],  // 风险控制参数(M), json化字符串数据,建议传入到 $payData
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $payData);

        return $this->_post($data);
    }

    /**
     * doPay 认证支付确认支付
     *
     * @param  array $payData
     * @return mixed
     */
    public function doPay($payData)
    {
        $params = [
            'txn_sub_type'    => '16',  // 交易子类(M)
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'business_no'     => '',  // `prePay` 那一步宝付返回得到的业务流水号(M)
            'sms_code'        => '',  // 支付时的短信验证码,若开通短信类交易则必填
            'trade_date'      => '',  // 订单日期(M),可以不传,sdk 会自动生成
            'additional_info' => '',  // 附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  // 请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $payData);

        return $this->_post($data);
    }

    /**
     * queryOrder 交易状态查询类交易
     * 
     * @param  array $queryOrderData
     * @return mixed
     */
    public function queryOrder($queryOrderData)
    {
        $params = [
            'trans_serial_no' => '',  // 商户流水号(M),可以不传,sdk 会自动生成
            'txn_sub_type'    => '31',  //交易子类(M)
            'orig_trans_id'   => '',  //原始商户订单号(M),由宝付返回,用于在后续类交易中唯一标识一笔交易
            'orig_trade_date' => '',  //原始订单日期(M)
            'additional_info' => '',  //附加字段(O),长度不超过 128 位
            'req_reserved'    => '',  //请求方保留域(O)
        ];

        $params = array_merge($this->getDefaultConfig(), $params);
        $data = array_merge($params, $queryOrderData);

        return $this->_post($data);
    }

    /**
     * _post post请求通用方法
     * 
     * @param  array $data [接口请求需要数据]
     * @return mixed
     */
    private function _post($data)
    {
        $rsa = $this->_rsa;
        $defaultConfig = $this->getDefaultConfig();
        $requestUrl = $this->_request_url;

        $data['biz_type']        = '0000';  // 接入类型(C),默认 0000
        $data['terminal_id']     = isset($data['terminal_id']) ? $data['terminal_id'] : (isset($defaultConfig['terminal_id']) ? $defaultConfig['terminal_id'] : '');  // 终端号(M)
        $data['member_id']       = isset($data['member_id']) ? $data['member_id'] : (isset($defaultConfig['member_id']) ? $defaultConfig['member_id'] : '');  // 商户号(M),宝付提供给商户的唯一编号

        if ($data['txn_sub_type'] != '31') {  // 查询交易状态的接口不需要 `trade_date`
            $data['trade_date'] = isset($data['trade_date']) && !empty($data['trade_date']) ? date('YmdHis', strtotime($data['trade_date'])) : date('YmdHis');  // 订单日期(M),14 位定长。格式:年年年年月月日日时时分分秒秒
        }

        $data['trans_serial_no'] = isset($data['trans_serial_no']) && !empty($data['trans_serial_no']) ? $data['trans_serial_no'] : Tool::generateSerialNo();  // 商户流水号(M),8-20 位字母和数字,每次请求都不可重复(当天和历史均不可重复)


        $jsonData = json_encode($data);


        // 组装请求数据
        $postData = [
            'version'      => isset($data['version']) ? $date['version'] : (isset($defaultConfig['version']) ? $defaultConfig['version'] : '4.0.0.0'),
            'terminal_id'  => $data['terminal_id'],
            'txn_type'     => isset($data['txn_type']) ? $data['txn_type'] : '0431',
            'txn_sub_type' => $data['txn_sub_type'],
            'member_id'    => $data['member_id'],
            'data_type'    => isset($data['data_type']) ? $data['data_type'] : (isset($defaultConfig['data_type']) ? $defaultConfig['data_type'] : 'json'),
            'data_content' => $rsa->encryptedByPrivateKey($jsonData),
        ];

        // 请求宝付接口
        try {
            $retData = $this->_curl_post($postData, $requestUrl);
            $retBody = $this->_decryptByPublicKey($retData);
            $resData = json_decode($retBody, true);
            return $this->_success($resData);
        } catch (\Exception $e) {
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
        if (curl_errno($curl)) {
            $error = curl_error($curl);  // 捕抓异常
            throw new BaofooException('Curl 请求数据异常：'.$error, BaofooException::CURL_POST_DATA_ERROR);
        }
        curl_close($curl);  // 关闭CURL会话
        return $retData;
    }

    /**
     * paySuccess 判断订单是否支付成功
     * 
     * @param  array $resData 明文json数据
     * @return array [code:200表示成功]
     */
    private function _success($resData)
    {
        $code = 200;
        $msg = 'ok';
        if (!Tool::isSuccess($resData)) {
            $code = 500;
            $ret = Tool::getBaofooResult($resData['resp_code'], $resData['resp_msg']);
            if (!$ret['status']) {
                $msg = $ret['message'];
            }
        }
        return [
            'code' => $code,
            'msg'  => $msg,
            'data' => $resData,
        ];
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