# Baofoo

>   可能是最好的第三方宝付 SDK ！

[![Latest Stable Version](https://poser.pugx.org/douyasi/baofoo/v/stable.svg?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![Latest Unstable Version](https://poser.pugx.org/douyasi/baofoo/v/unstable.svg?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![License](https://poser.pugx.org/douyasi/baofoo/license?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![Total Downloads](https://poser.pugx.org/douyasi/baofoo/downloads?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)

## 使用说明

### 宝付认证支付

依据宝付认证支付 API 商户接入接口文档》（V4.0.4.0），本 `Sdk` 支持以下认证支付交易子类：

 - 01 直接绑卡类交易 [支持]
 - 02 解除绑定关系类交易 [支持]
 - 03 查询绑定关系类交易 [支持]
 - 11 预绑卡类交易 [支持]
 - 12 确认绑卡类交易 [支持]
 - 15 预支付交易(发送短信) [支持]
 - 16 支付确认交易 [支持]
 - 31 交易状态查询类交易 [支持]
 - 异步通知 [不支持]

以上 8 个接口都支持传入以下可选项：

```
    // 'additional_info' => '测试',  // 附加字段，可以不传或留空
    // 'req_reserved' => '保留字段',  // 请求方保留域，可以不传或留空
```

### 宝付代付

依据《宝付代付 API 接口文档》（V4.1.11），本 `FoPaySdk` 支持以下代付交易场景：

- 代付交易接口(BF0040001) [支持]
- 代付交易状态查证接口(BF0040002) [支持]
- 代付交易退款查证接口(BF0040003) [支持]
- 代付交易拆分接口(BF0040004) [支持]
- 代付绑卡交易接口(BF0040006) [不支持]
- 宝付账户实时交易接口(BF0040007) [不支持]
- 账户收款方交易查证接口(BF0040010) [不支持]
- 代付宝付回调接口 [不支持]

## `composer` 包引入

在 `composer.json` 文件中添加上 `"douyasi/baofoo": "~1.0"` 项，然后执行 `composer update` 命令。

```json
{
    "require": {
        "douyasi/baofoo": "~1.0"
    }
}
```

或者在 `bash` 下跳到项目根目录执行 `composer require 'douyasi/baofoo:~1.0'` 命令安装。

## 宝付认证支付使用示例

### 代码引入与初始化

```php
<?php

// 自动加载
require __DIR__ . '/vendor/autoload.php';

$config = [
    'member_id'   => '100000276',  // 商户号
    'terminal_id' => '100000990',  // 终端号
    'request_url' => 'http://vgw.baofoo.com/cutpayment/api/backTransRequest',  // 请求宝付网关地址
];

$bfpayConf = [
    // 'timezone'                 => 'Asia/Shanghai',  // 时区设置，不填写，默认使用 `Asia/Shanghai`
    'private_key_password'     => '123456',  // 私钥密码
    'public_key_path'          => '',  // 公钥路径，留空使用 res\cer 文件
    'private_key_path'         => '',  // 私钥路径，留空使用 res\cer 文件
    'allowed_bind_credit_card' => false,  // 是否允许绑定信用卡，某些金融场景可能不允许使用信用卡 CC
    'debug'                    => false,  // 是否开启 debug 模式
    'logger_path'              => '',  // 记录请求日志的根路径，请使用绝对路径
];

$baofoo = new \Douyasi\Baofoo\Sdk($config, $bfpayConf);
```

### 直接绑卡(01)

```php

$bindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trans_id' => '',  // 可以不传，sdk 会自动生成
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成， 手动传入，必须符合宝付要求的日期格式
    /* 持卡人四要素*/
    'acc_no' => '6222020111122220000',
    'id_holder' => '张宝',
    'id_card' => '320301198502169142',
    'mobile' => '13800000000',
    // 'pay_code' => '',  // 建议不要手动传 `pay_code` ，sdk 会根据卡号自动查询得到 `pay_code` ，而且会根据配置 限制是否允许绑定信用卡
    // 'additional_info' => '测试',  // 附加字段，可以不传或留空，其他接口亦是如此
    // 'req_reserved' => '保留字段',  // 请求方保留域，可以不传或留空，其他接口亦是如此
];

$ret = $baofoo->bindCard($bindData);
```

#### 返回值

- 返回值 `$ret` 为数组。
- 当 `$ret['code']` 为 `200` 时，正常成功结果；`500` 时为异常结果。
- `$ret['msg']` 会存放消息，异常时可以返回给前端使用。
- `$ret['need_query']` 当交易结果暂未知，需查询类时会返回 `true` ，其他情况返回 `false` 。具体请参考《宝付认证支付 API 商户接入接口文档》（V4.0.4.0）*附录 - 应答码* 那一节内容。
- 宝付响应解密之后的内容会放在 `$ret['data']` 中。

正常成功结果 `json` 化示例：

```json
{
    "code":200,
    "msg":"ok",
    "need_query": false,
    "data":{
        "additional_info":"保留字段",
        "bind_id":"201709152043261000009906390",
        "data_type":"json",
        "member_id":"100000276",
        "req_reserved":"保留字段",
        "resp_code":"0000",
        "resp_msg":"交易成功",
        "terminal_id":"100000990",
        "trade_date":"20170915124325",
        "trans_id":"TI170915124325276174",
        "trans_serial_no":"TSN17091512432551565",
        "txn_sub_type":"01",
        "txn_type":"0431",
        "version":"4.0.0.0"
    }
}
```

异常结果 `json` 化示例：

```json
{
    "code":500,
    "msg":"系统异常, 请联系宝付",
    "need_query": false,
    "data":{
        "resp_code":"BF00100",
        "resp_msg":"系统异常, 请联系宝付"
    }
}
```

其他接口返回值结构亦是如此，不再赘述返回值。

### 解除绑卡(02)

```php

$unbindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成， 手动传入，必须符合宝付要求的日期格式
    'bind_id' => '201709151709081000009905295',  // 绑卡时得到的 bind_id
    'trans_id' => 'TI170915101656903557',  // 必须与绑卡的时候的订单号一致
];

$ret = $baofoo->unbindCard($unbindData);
```

### 查询绑卡状态(03)

```php

$queryBindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成， 手动传入，必须符合宝付要求的日期格式
    'acc_no' => '6222020111122220000',
];

$ret = $baofoo->queryBindCard($queryBindData);
```


### 预支付(15)

```php

$payData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trans_id' => '',  // 可以不传，sdk 会自动生成
    'bind_id' => '201709151709081000009905295',
    'txn_amt' => 1,  // 金额，分为单位，这里是 1分
    'mobile' => '13800000000',
    'acc_no' => '6222020111122220000',  // 银行卡号
    'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成， 手动传入，必须符合宝付要求的日期格式
    'risk_content' => [
        'client_ip' => '127.0.0.1', // 必须传入，请将客户真实 ip 传入
    ],
];

$ret = $baofoo->prePay($payData);
```

### 确定支付(16)

```php

$payData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    'business_no' => 'TI170915124325276174', // `prePay` 那一步宝付返回得到的业务流水号
    'sms_code' => '123456', // 支付时的短信验证码，若开通短信类交易则必填
    'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成， 手动传入，必须符合宝付要求的日期格式
];

$ret = $baofoo->doPay($payData);
```

### 其它接口

其它接口暂不列出示例，请查阅 `Sdk.php` 代码调用。

## 宝付代付使用示例

### 代码引入与初始化

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$config = [
    'version'         => '4.0.0.0',  // 版本号
    'data_type'       => 'json',  // 加密报文的数据类型（xml/json）
    'member_id'       => '100000178',  // 默认测试商户号
    'terminal_id'     => '100000859',  // 默认测试终端号
];

$bfpayConf = [
    'fo_pay_env'               => 'TESTING',  // 环境，可选 测试 TESTING 和 生产PRODUCTION
    // 'timezone'                 => 'Asia/Shanghai',  // 时区设置，不填写，默认使用 `Asia/Shanghai`
    'private_key_password'     => '123456',  // 私钥密码
    'public_key_path'          => '',  // 公钥路径，留空使用 res\cer 文件
    'private_key_path'         => '',  // 私钥路径，留空使用 res\cer 文件
    'debug'                    => false,  // 是否开启 debug 模式
    'logger_path'              => '',  // 记录请求日志的根路径，请使用绝对路径
];

$foPay = new \Douyasi\Baofoo\FoPaySdk($config, $bfpayConf);
```

### 代付交易接口(BF0040001)

```php

// 单条数据
$data = [
    'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M （建议在自己程序中自行生成订单号，这里使用本包 `Tool` 类 助手方法） 
    'trans_money'   => '100',  // 转账金额 （元）M 
    'to_acc_name'   => '张宝',  // 收款人姓名 M
    'to_acc_no'     => '6222020111122220000',
    'to_bank_name'  => '中国银行',  // 收款人银行名称 M
    'to_pro_name'   => '',  // 收款人开户行省名 C
    'to_city_name'  => '',  // 收款人开户行市名 C
    'to_acc_dept'   => '',  // 收款人开户行机构名 C
    'trans_card_id' => '',  // 银行卡身份证件号码 C
    'trans_mobile'  => '',  // 银行卡预留手机号 C
    'trans_summary' => '',  // 摘要 R
];

$ret1 = $foPay->agentPay($data);

// 多条数据，宝付一个批次最多5条数据，这里演示 2条

# 依次传入
$data1 = [
    'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
    'trans_money'   => '100',  // 转账金额 （元）M 
    'to_acc_name'   => '张宝',  // 收款人姓名 M
    'to_acc_no'     => '6222020111122220000',
    'to_bank_name'  => '中国银行',  // 收款人银行名称 M
];
$data2 = [
    'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
    'trans_money'   => '200',  // 转账金额 （元）M 
    'to_acc_name'   => '张宝',  // 收款人姓名 M
    'to_acc_no'     => '6222020111122220000',
    'to_bank_name'  => '中国银行',  // 收款人银行名称 M
];

$ret2 = $foPay->agentPay($data1, $data2);

# 一次性传入

$data3 = [
    [
        'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
        'trans_money'   => '300',  // 转账金额 （元）M 
        'to_acc_name'   => '张宝',  // 收款人姓名 M
        'to_acc_no'     => '6222020111122220000',
        'to_bank_name'  => '中国银行',  // 收款人银行名称 M
    ],
    [
        'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
        'trans_money'   => '400',  // 转账金额 （元）M 
        'to_acc_name'   => '张宝',  // 收款人姓名 M
        'to_acc_no'     => '6222020111122220000',
        'to_bank_name'  => '中国银行',  // 收款人银行名称 M
    ],
];

$ret3 = $foPay->agentPay($data3);
```

#### 返回值

- 返回值 `$ret` 为数组。
- 请特别注意：本 `FoPaySdk` 不对宝付代付响应的结果做额外的结构包装，各接口返回的数组由宝付代付接口自身响应经 `RSA` 解密 `JSON` 转换之后所得，几乎可视为原样输出，请开发者参阅《宝付代付 API 接口文档》（V4.1.11）并结合实际在代码中进行逻辑处理。

正常成功结果 `json` 化示例：

```json
{
    "trans_content":{
        "trans_reqDatas":[
            {
                "trans_reqData":[
                    {
                        "to_acc_dept":"||中国银行",
                        "to_acc_name":"张宝",
                        "to_acc_no":"6222020111122220000",
                        "trans_batchid":20868159,
                        "trans_money":"300.00",
                        "trans_no":"TI171007162051843424",
                        "trans_orderid":17098873,
                        "trans_summary":""
                    },
                    {
                        "to_acc_dept":"||中国银行",
                        "to_acc_name":"张宝",
                        "to_acc_no":"6222020111122220000",
                        "trans_batchid":20868159,
                        "trans_money":"400.00",
                        "trans_no":"TI171007162051952720",
                        "trans_orderid":17098874,
                        "trans_summary":""
                    }
                ]
            }
        ],
        "trans_head":{
            "return_code":"0000",
            "return_msg":"代付请求交易成功"
        }
    }
}
```

异常结果 `json` 化示例：

```json
{
    "trans_content":{
        "trans_head":{
            "return_code":"0004",
            "return_msg":"交易请求记录条数超过上限!"
        }
    }
}
```

具体 `return_code` 对应的异常请参阅《宝付代付 API 接口文档》（V4.1.11）。

### 代付交易状态查证接口(BF0040002)

```php
// 这里只演示单条数据，多条的参考上面 `代付交易接口(BF0040001)` 用法
$data = [
    'trans_no'      => 'TI171007162051843424',  // 商户订单号 M
    'trans_batchid' => 20868159,  // 宝付批次号 O
];

$ret = $foPay->agentPayStatusQuery($data);
```

上面测试代码返回值 `json` 化结果：

> `state` 订单交易处理状态 M 0:转账中; 1:转账成功; -1:转账失败; 2:转账退款.

```json
{
    "trans_content":{
        "trans_reqDatas":[
            {
                "trans_reqData":{
                    "state":-1,
                    "to_acc_dept":"||中国银行",
                    "to_acc_name":"张宝",
                    "to_acc_no":"6222020111122220000",
                    "trans_batchid":20868159,
                    "trans_endtime":"2017-10-07 16:20:55",
                    "trans_fee":"",
                    "trans_money":"300.00",
                    "trans_no":"TI171007162051843424",
                    "trans_orderid":17098873,
                    "trans_remark":"收款帐号与银行名称不匹配",
                    "trans_starttime":"2017-10-07 16:20:54",
                    "trans_summary":""
                }
            }
        ],
        "trans_head":{
            "return_code":"0000",
            "return_msg":"代付请求交易成功"
        }
    }
}
```

### 代付交易退款查证接口(BF0040003)

```php
// 本接口只支持单条数据传入，同天查询
$data = [
    'trans_btime' => '20171007',  // 格式:YYYYMMDD(最多查询一天记录) 查询起始时间 M
    'trans_etime' => '20171007',  // 格式:YYYYMMDD(最多查询一天记录) 查询结束时间 M 
];

$ret = $foPay->agentPayRefundQuery($data);
```

### 代付交易拆分接口(BF0040004)

```php
$data1 = [
    'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
    'trans_money'   => '100',  // 转账金额 （元）M 
    'to_acc_name'   => '张宝',  // 收款人姓名 M
    'to_acc_no'     => '6222020111122220000',
    'to_bank_name'  => '中国银行',  // 收款人银行名称 M
];

$data2 = [
    'trans_no'      => \Douyasi\Baofoo\Tool::generateTransId(),  // 商户订单号 M
    'trans_money'   => '200',  // 转账金额 （元）M 
    'to_acc_name'   => '张宝',  // 收款人姓名 M
    'to_acc_no'     => '6222020111122220000',
    'to_bank_name'  => '中国银行',  // 收款人银行名称 M
];
$ret = $foPay->agentPaySplit($data1, $data2);
```

上面测试代码返回值 `json` 化结果：

```json
{
    "trans_content":{
        "trans_reqDatas":[
            {
                "trans_reqData":[
                    {
                        "to_acc_dept":"||中国银行",
                        "to_acc_name":"张宝",
                        "to_acc_no":"6222020111122220000",
                        "trans_batchid":20868174,
                        "trans_money":"100.00",
                        "trans_no":"TI171007220502112918",
                        "trans_orderid":17098890,
                        "trans_summary":""
                    },
                    {
                        "to_acc_dept":"||中国银行",
                        "to_acc_name":"张宝",
                        "to_acc_no":"6222020111122220000",
                        "trans_batchid":20868174,
                        "trans_money":"200.00",
                        "trans_no":"TI171007220502102969",
                        "trans_orderid":17098891,
                        "trans_summary":""
                    }
                ]
            }
        ],
        "trans_head":{
            "return_code":"0000",
            "return_msg":"代付请求交易成功"
        }
    }
}
```

### 其它

其它未尽事宜，请开发者自行参阅《宝付代付 API 接口文档》（V4.1.11）。

## 参考资源

- 《宝付认证支付 API 商户接入接口文档》（V4.0.4.0）
- 《宝付代付 API 接口文档》（V4.1.11）
- Inspired by [navyxie/baofoo](https://github.com/navyxie/baofoo) 。

## 联系方式

在使用中，遇到问题可以发 `issue` ，或者通过以下方式联系作者我。

- Email: raoyc <raoyc2009@gmail.com>
- 官网：http://douyasi.com
- QQ群：260655062
- Github: [ycrao](https://github.com/ycrao)
