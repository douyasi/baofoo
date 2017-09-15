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
        return 'TSN'.date('ymdHis').str_pad(mt_rand(1,  99999),  5,  '0',  STR_PAD_LEFT);
    }

    /**
     * generateTransId 随机生成商户订单号
     * 
     * @return string 20位长度订单号
     */
    public static function generateTransId()
    {
        return 'TI'.date('ymdHis').str_pad(mt_rand(1,  999999),  6,  '0',  STR_PAD_LEFT);
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
            return $resData['resp_code'] === '0000';
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
     * @return array
     */
    public static function getBaofooResult($respCode, $respMsg)
    {
        switch ($respCode) {

            case '0000':  // 交易成功
                return ['status' => true,  'message' => '交易成功'];
                break;

            case 'BF00100':
                return ['status' => false, 'message' => '系统异常, 请联系宝付'];
                break;

            case 'BF00101':
                return ['status' => false, 'message' => '持卡人信息有误'];
                break;

            case 'BF00102':
                return ['status' => false, 'message' => '银行卡已过有效期, 请联系发卡行'];
                break;

            case 'BF00103':
                return ['status' => false, 'message' => '账户余额不足'];
                break;

            case 'BF00104':
                return ['status' => false, 'message' => '交易金额超限'];
                break;

            case 'BF00105':
                return ['status' => false, 'message' => '短信验证码错误'];
                break;

            case 'BF00106':
                return ['status' => false, 'message' => '短信验证码失效'];
                break;

            case 'BF00107':
                return ['status' => false, 'message' => '当前银行卡不支持该业务, 请联系发卡行'];
                break;

            case 'BF00108':
                return ['status' => false, 'message' => '交易失败, 请联系发卡行'];
                break;

            case 'BF00109':
                return ['status' => false, 'message' => '交易金额低于限额'];
                break;

            case 'BF00110':
                return ['status' => false, 'message' => '该卡暂不支持此交易'];
                break;

            case 'BF00111':
                return ['status' => false, 'message' => '交易失败'];
                break;

            case 'BF00112':
                return ['status' => false, 'message' => '系统繁忙, 请稍后再试'];
                break;

            case 'BF00113':
                return ['status' => false, 'message' => '交易结果未知, 请稍后查询'];
                break;

            case 'BF00114':
                return ['status' => false, 'message' => '订单已支付成功, 请勿重复支付'];
                break;

            case 'BF00115':
                return ['status' => false, 'message' => '交易处理中, 请稍后查询'];
                break;

            case 'BF00116':
                return ['status' => false, 'message' => '该终端号不存在'];
                break;

            case 'BF00117':
                return ['status' => false, 'message' => '交易金额超限, 请联系宝付'];
                break;

            case 'BF00118':
                return ['status' => false, 'message' => '报文中密文解析失败'];
                break;

            case 'BF00119':
                return ['status' => false, 'message' => '短信验证超时, 请稍后再试'];
                break;

            case 'BF00120':
                return ['status' => false, 'message' => '报文交易要素缺失'];
                break;

            case 'BF00121':
                return ['status' => false, 'message' => '报文交易要素格式错误'];
                break;

            case 'BF00122':
                return ['status' => false, 'message' => '卡号和支付通道不匹配'];
                break;

            case 'BF00123':
                return ['status' => false, 'message' => '商户不存在或状态不正常, 请联系宝付'];
                break;

            case 'BF00124':
                return ['status' => false, 'message' => '商户与终端号不匹配'];
                break;

            case 'BF00125':
                return ['status' => false, 'message' => '商户该终端下未开通此类型交易'];
                break;

            case 'BF00126':
                return ['status' => false, 'message' => '该笔订单已存在'];
                break;

            case 'BF00127':
                return ['status' => false, 'message' => '不支持该支付通道的交易'];
                break;

            case 'BF00128':
                return ['status' => false, 'message' => '该笔订单不存在'];
                break;

            case 'BF00129':
                return ['status' => false, 'message' => '密文和明文中参数不一致, 请确认是否被篡改!'];
                break;

            case 'BF00130':
                return ['status' => false, 'message' => '请确认是否发送短信, 当前交易必须通过短信验证!'];
                break;

            case 'BF00131':
                return ['status' => false, 'message' => '当前交易信息与短信交易信息不一致, 请核对信息'];
                break;

            case 'BF00132':
                return ['status' => false, 'message' => '短信验证超时, 请稍后再试'];
                break;

            case 'BF00133':
                return ['status' => false, 'message' => '短信验证失败'];
                break;

            case 'BF00134':
                return ['status' => false, 'message' => '绑定关系不存在'];
                break;

            case 'BF00135':
                return ['status' => false, 'message' => '交易金额不正确'];
                break;

            case 'BF00136':
                return ['status' => false, 'message' => '订单创建失败'];
                break;

            case 'BF00137':
                return ['status' => false, 'message' => '个人会员不能为空'];
                break;

            case 'BF00138':
                return ['status' => false, 'message' => '个人会员不存在'];
                break;

            case 'BF00140':
                return ['status' => false, 'message' => '该卡已被注销'];
                break;

            case 'BF00141':
                return ['status' => false, 'message' => '该卡已挂失'];
                break;

            case 'BF00142':
                return ['status' => false, 'message' => '暂不支持该银行卡的绑卡'];
                break;

            case 'BF00143':
                return ['status' => false, 'message' => '绑卡失败'];
                break;

            case 'BF00144':
                return ['status' => false, 'message' => '该交易有风险, 订单处理中'];
                break;

            case 'BF00146':
                return ['status' => false, 'message' => '订单金额超过单笔限额'];
                break;

            case 'BF00147':
                return ['status' => false, 'message' => '该银行卡不支持此交易'];
                break;

            case 'BF00177':
                return ['status' => false, 'message' => '非法的交易'];
                break;

            case 'BF00180':
                return ['status' => false, 'message' => '获取短信验证码失败'];
                break;

            case 'BF00182':
                return ['status' => false, 'message' => '您输入的银行卡号有误, 请重新输入'];
                break;

            case 'BF00186':
                return ['status' => false, 'message' => '该卡已绑定'];
                break;

            case 'BF00187':
                return ['status' => false, 'message' => '暂不支持信用卡的绑定'];
                break;

            case 'BF00188':
                return ['status' => false, 'message' => '绑卡失败'];
                break;

            case 'BF00189':
                return ['status' => false, 'message' => '交易金额超过限额'];
                break;

            case 'BF00190':
                return ['status' => false, 'message' => '商户流水号不能重复'];
                break;

            case 'BF00191':
                return ['status' => false, 'message' => '绑定id和用户id不匹配'];
                break;

            case 'BF00192':
                return ['status' => false, 'message' => '标的开始日期格式不正确'];
                break;

            case 'BF00193':
                return ['status' => false, 'message' => '标的结束日期格式不正确'];
                break;

            case 'BF00194':
                return ['status' => false, 'message' => '标的到期还款日期格式不正确'];
                break;

            case 'BF00195':
                return ['status' => false, 'message' => '交易金额不正确'];
                break;

            case 'BF00196':
                return ['status' => false, 'message' => '标的金额不正确'];
                break;

            case 'BF00197':
                return ['status' => false, 'message' => '还款总金额不正确'];
                break;

            case 'BF00198':
                return ['status' => false, 'message' => '年化率格式不正确'];
                break;

            case 'BF00199':
                return ['status' => false, 'message' => '订单日期格式不正确'];
                break;

            case 'BF00200':
                return ['status' => false, 'message' => '发送短信和支付时商户订单号不一致'];
                break;

            case 'BF00201':
                return ['status' => false, 'message' => '发送短信和支付交易时金额不相等'];
                break;

            case 'BF00202':
                return ['status' => false, 'message' => '交易超时, 请稍后查询'];
                break;

            case 'BF00203':
                return ['status' => false, 'message' => '退款交易已受理'];
                break;

            case 'BF00204':
                return ['status' => false, 'message' => '确认绑卡时与预绑卡时的商户订单号不一致'];
                break;

            case 'BF00232':
                return ['status' => false, 'message' => '银行卡未开通认证支付'];
                break;

            case 'BF00233':
                return ['status' => false, 'message' => '密码输入次数超限, 请联系发卡行'];
                break;

            case 'BF00234':
                return ['status' => false, 'message' => '单日交易金额超限'];
                break;

            case 'BF00235':
                return ['status' => false, 'message' => '单笔交易金额超限'];
                break;

            case 'BF00236':
                return ['status' => false, 'message' => '卡号无效, 请确认后输入'];
                break;

            case 'BF00237':
                return ['status' => false, 'message' => '该卡已冻结, 请联系发卡行'];
                break;

            case 'BF00238':
                return ['status' => false, 'message' => '交易结果未知, 请稍后查询'];
                break;

            case 'BF00309':
                return ['status' => false, 'message' => '绑卡和发送短信时手机号不一致'];
                break;

            case 'BF00311':
                return ['status' => false, 'message' => '卡类型和 biz_type 值不匹配'];
                break;

            case 'BF00312':
                return ['status' => false, 'message' => '卡号校验失败'];
                break;

            case 'BF00313':
                return ['status' => false, 'message' => '商户请求IP不合法'];
                break;

            case 'BF00315':
                return ['status' => false, 'message' => '手机号码为空, 请重新输入'];
                break;

            default:
                return ['status' => false, 'message' => $respMsg];
                break;
        }
    }

}