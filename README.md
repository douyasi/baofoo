# Baofoo

>   可能是最好的第三方宝付 SDK ！

[![Latest Stable Version](https://poser.pugx.org/douyasi/baofoo/v/stable.svg?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![Latest Unstable Version](https://poser.pugx.org/douyasi/baofoo/v/unstable.svg?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![License](https://poser.pugx.org/douyasi/identity-card/license?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)
[![Total Downloads](https://poser.pugx.org/douyasi/identity-card/downloads?format=flat-square)](https://packagist.org/packages/douyasi/baofoo)

## 使用说明

目前本 `sdk` 支持以下交易子类：

 - 01 直接绑卡类交易
 - 02 解除绑定关系类交易
 - 03 查询绑定关系类交易
 - 11 预绑卡类交易
 - 12 确认绑卡类交易
 - 15 预支付交易(发送短信)
 - 16 支付确认交易
 - 31 交易状态查询类交易

以上 8 个接口都支持传入以下可选项：

```
    // 'additional_info' => '测试',  // 附加字段，可以不传或留空
    // 'req_reserved' => '保留字段',  // 请求方保留域，可以不传或留空
```

## 使用示例

### composer 加载

在 `composer.json` 文件中添加上 `"douyasi/baofoo": "dev-master"` 项，然后执行 `composer update` 命令。

```json
{
    "require": {
        "douyasi/baofoo": "~1.0"
    }
}
```

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
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成, 手动传入，必须符合宝付要求的日期格式
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
- 宝付响应解密之后的内容会放在 `$ret['data']` 中。

正常成功结果 `json` 化示例：

```json
{
    "code":200,
    "msg":"ok",
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
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成, 手动传入，必须符合宝付要求的日期格式
    'bind_id' => '201709151709081000009905295',  // 绑卡时得到的 bind_id
    'trans_id' => 'TI170915101656903557',  // 必须与绑卡的时候的订单号一致
];

$ret = $baofoo->unbindCard($unbindData);
```

### 查询绑卡状态(03)

```php

$queryBindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成, 手动传入，必须符合宝付要求的日期格式
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
    'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成, 手动传入，必须符合宝付要求的日期格式
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
    'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成, 手动传入，必须符合宝付要求的日期格式
];

$ret = $baofoo->doPay($payData);
```

### 其它接口

其它接口暂不列出示例，请查阅 `Sdk.php` 代码调用。

## 参考资源

- 《宝付认证支付 API 商户接入接口文档》
- Inspired by [navyxie/baofoo](https://github.com/navyxie/baofoo) 。

## 联系方式

在使用中，遇到问题可以发 `issue` ，或者通过以下方式联系作者我。

- Email: raoyc <raoyc2009@gmail.com>
- 官网：http://douyasi.com
- QQ群：260655062
- Github: [ycrao](https://github.com/ycrao)
