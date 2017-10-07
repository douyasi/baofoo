<?php

namespace Douyasi\Baofoo;

use Douyasi\Baofoo\BaofooException;


/**
 * Class Tool 宝付工具类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 */
class Tool
{

    /**
     * generateSerialNo 随机生成商户流水号
     * 
     * @return string 20位长度流水号
     */
    public static function generateSerialNo()
    {
        return 'TSN'.date('ymdHis').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * generateTransId 随机生成商户订单号
     * 
     * @return string 20位长度订单号
     */
    public static function generateTransId()
    {
        return 'TI'.date('ymdHis').str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * isSuccess 判断宝付返回结果是否成功
     * 
     * @param  array  $resData
     * @return bool
     */
    public static function isSuccess($resData)
    {
        if (isset($resData['resp_code'])) {
            return $resData['resp_code'] === '0000' || $resData['resp_code'] === 'BF00114';
        }
        return false;
    }

    /**
     * getPayCode 获取宝付银行卡编码 pay_code
     * 
     * @param  string $payCode
     * @return string
     */
    public static function getPayCode($payCode)
    {
        $bankCode = $payCode;
        switch ($payCode) {
            case 'COMM':  // 交通银行
                $bankCode = 'BCOM';
                break;
            case 'SPABANK':  // 平安银行
                $bankCode = 'PAB';
                break;
            case 'SHBANK':  // 上海银行
                $bankCode = 'SHB';
                break;
            case 'HXBANK':  // 华夏银行
                $bankCode = 'HXB';
                break;
        }
        return $bankCode;
    }

    /**
     * getBaofooResult 获取宝付结果
     * 
     * @param  string $respCode 响应 code 码
     * @param  string $respMsg 响应消息
     * @return array [
     *         'status' => true,  // bool 提交状态
     *         'message' => ''，  // string 消息正文
     *         'need_query' => false, // bool 是否需要查询类
     * ]
     */
    public static function getBaofooResult($respCode, $respMsg)
    {
        switch ($respCode) {

            // 交易成功类:
            case '0000':  // 交易成功
            case 'BF00114':  // 订单已支付成功，请勿重复支付
                return ['status' => true, 'message' => isset(self::$baofooSdkMessages[$respCode]) ? self::$baofooSdkMessages[$respCode] : $respMsg, 'need_query' => false];
                break;

            // 交易结果暂未知，需查询类:
            case 'BF00100':
            case 'BF00112':
            case 'BF00113':
            case 'BF00115':
            case 'BF00144':
            case 'BF00202':
                return ['status' => false, 'message' => isset(self::$baofooSdkMessages[$respCode]) ? self::$baofooSdkMessages[$respCode] : $respMsg, 'need_query' => true];
                break;

            // 其它 / 交易失败，无需查询类:
            default:
                return ['status' => false, 'message' => isset(self::$baofooSdkMessages[$respCode]) ? self::$baofooSdkMessages[$respCode] : $respMsg, 'need_query' => false];
                break;
        }
    }

    protected static $baofooSdkMessages = [
        '0000'    => '交易成功',
        'BF00100' => '系统异常，请联系宝付',
        'BF00101' => '持卡人信息有误',
        'BF00102' => '银行卡已过有效期，请联系发卡行',
        'BF00103' => '账户余额不足',
        'BF00104' => '交易金额超限',
        'BF00105' => '短信验证码错误',
        'BF00106' => '短信验证码失效',
        'BF00107' => '当前银行卡不支持该业务，请联系发卡行',
        'BF00108' => '交易失败，请联系发卡行',
        'BF00109' => '交易金额低于限额',
        'BF00110' => '该卡暂不支持此交易',
        'BF00111' => '交易失败',
        'BF00112' => '系统繁忙，请稍后再试',
        'BF00113' => '交易结果未知，请稍后查询',
        'BF00115' => '交易处理中，请稍后查询',
        'BF00116' => '该终端号不存在',
        'BF00118' => '报文中密文解析失败',
        'BF00120' => '报文交易要素缺失',
        'BF00121' => '报文交易要素格式错误',
        'BF00122' => '卡号和支付通道不匹配',
        'BF00123' => '商户不存在或状态不正常，请联系宝付',
        'BF00124' => '商户与终端号不匹配',
        'BF00125' => '商户该终端下未开通此类型交易',
        'BF00126' => '该笔订单已存在',
        'BF00127' => '不支持该支付通道的交易',
        'BF00128' => '该笔订单不存在',
        // 'BF00129' => '密文和明文中参数【%s】不一致,请确认是否被篡改!',
        'BF00130' => '请确认是否发送短信,当前交易必须通过短信验证!',
        'BF00131' => '当前交易信息与短信交易信息不一致,请核对信息',
        'BF00132' => '短信验证超时，请稍后再试',
        'BF00133' => '短信验证失败',
        'BF00134' => '绑定关系不存在',
        'BF00135' => '交易金额不正确',
        'BF00136' => '订单创建失败',
        'BF00140' => '该卡已被注销',
        'BF00141' => '该卡已挂失',
        'BF00144' => '该交易有风险,订单处理中',
        'BF00146' => '订单金额超过单笔限额',
        'BF00147' => '该银行卡不支持此交易',
        'BF00177' => '非法的交易 BF00180 获取短信验证码失败',
        'BF00180' => '获取短信验证码失败',
        'BF00182' => '您输入的银行卡号有误，请重新输入',
        'BF00187' => '暂不支持信用卡的绑定',
        'BF00188' => '绑卡失败',
        'BF00190' => '商户流水号不能重复',
        'BF00199' => '订单日期格式不正确',
        'BF00200' => '发送短信和支付时商户订单号不一致',
        'BF00201' => '发送短信和支付交易时金额不相等',
        'BF00202' => '交易超时，请稍后查询',
        'BF00203' => '退款交易已受理',
        'BF00204' => '确认绑卡时与预绑卡时的商户订单号不一致',
        'BF00232' => '银行卡未开通认证支付',
        'BF00233' => '密码输入次数超限，请联系发卡行',
        'BF00234' => '单日交易金额超限',
        'BF00235' => '单笔交易金额超限',
        'BF00236' => '卡号无效，请确认后输入',
        'BF00237' => '该卡已冻结，请联系发卡行',
        'BF00249' => '订单已过期，请使用新的订单号发起交易 BF00251 订单未支付',
        'BF00251' => '订单未支付',
        'BF00253' => '交易拒绝',
        'BF00255' => '发送短信验证码失败',
        'BF00256' => '请重新获取验证码',
        'BF00258' => '手机号码校验失败',
        'BF00260' => '短信验证码已过期，请重新发送',
        'BF00261' => '短信验证码错误次数超限，请重新获取',
        'BF00262' => '交易金额与扣款成功金额不一致，请联系宝付',
        'BF00311' => '卡类型和 biz_type 值不匹配',
        'BF00312' => '卡号校验失败',
        'BF00313' => '商户未开通此产品',
        'BF00315' => '手机号码为空，请重新输入',
        'BF00316' => 'ip 未绑定，请联系宝付',
        'BF00317' => '短信验证码已失效，请重新获取',
        'BF00321' => '身份证号不合法',
        'BF00322' => '卡类型和卡号不匹配',
        'BF00323' => '商户未开通交易模版',
        'BF00324' => '暂不支持此银行卡支付，请更换其他银行卡或咨询商户客服',
        'BF00325' => '非常抱歉!目前该银行正在维护中，请更换其他银行卡支付',
        'BF00327' => '请联系银行核实您的卡状态是否正常',
        'BF00331' => '卡号校验失败',
        'BF00332' => '交易失败，请重新支付',
        'BF00333' => '该卡有风险，发卡行限制交易',
        'BF00341' => '该卡有风险，请持卡人联系银联客服[95516]',
        'BF00342' => '单卡单日余额不足次数超限',
        'BF00343' => '验证失败(手机号有误)',
        'BF00344' => '验证失败(卡号有误)',
        'BF00345' => '验证失败(姓名有误)',
        'BF00346' => '验证失败(身份证号有误)',
        'BF00347' => '交易次数频繁，请稍后重试',
        'BF00350' => '该卡当日失败次数已超过 3 次，请次日再试!',
        'BF00351' => '该卡当日交易笔数超过限制，请次日再试!',
        'BF00353' => '未设置手机号码，请联系发卡行确认',
        // 'BF08701' => '该卡本次可支付***元，请更换其他银行卡!',
        // 'BF08702' => '该商户本次可支付***元，请更换其他银行卡或咨询商户客服!',
        // 'BF08703' => '支付金额不能低于最低限额...元!',
        // 'BF08704' => '单笔金额超限，该银行单笔可支付 xxx 元!',
        'BF00373' => '请求处理中，请勿重复交',

    ];

}