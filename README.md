# Baofoo

可能是最好的 宝付SDK 第三方包！

## 使用示例

### composer 加载

```
{
    "require": {
        "douyasi/baofoo": "dev-master"
    },
    "repositories": [
        {"type": "vcs", "url": "git@github.com:douyasi/baofoo.git"}
    ]
}
```

### 自动加载与初始化

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
];

$baofoo = new \Douyasi\Baofoo\Sdk($config, $bfpayConf);
```

### 直接绑卡

```php

$bindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    // 'trans_id' => '', // 可以不传，sdk 会自动生成
    'acc_no' => '6222020111122220000',
    'id_holder' => '张宝',
    'id_card' => '320301198502169142',
    'mobile' => '13800000000',
    // 'pay_code' => '',  // 建议不要手动传 pay_code ，sdk 会根据卡号自动查询得到 pay_code ，而且会根据配置 限制是否允许绑定信用卡
    'additional_info' => '测试',
    'req_reserved' => '保留字段',
    // 'sms_code' => '123456',  // 直接绑卡无须传 sms_code
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

### 解除绑卡

```php

$unbindData = [
    'bind_id' => '201709151709081000009905295',
    'trans_id' => 'TI170915101656903557',  // 必须与绑卡的时候的订单号一致
];

$ret = $baofoo->unbindCard($unbindData)
```

### 查询绑卡状态

```php

$queryBindData = [
    // 'trans_serial_no' => '',  // 可以不传，sdk 会自动生成
    'acc_no' => '6222020111122220000',
];

$ret = $baofoo->queryBindCard($queryBindData);
```


### 预支付

```php

$payData = [
    // 'trans_serial_no' => '', // 可以不传，sdk 会自动生成
    // 'trans_id' => '', // 可以不传，sdk 会自动生成
    'bind_id' => '201709151709081000009905295',
    'txn_amt' => 1,  // 金额，分为单位，这里是 1分
    'mobile' => '13800000000',
    'acc_no' => '6222020111122220000',  // 银行卡号
    'trade_date' => '20170915191103', // 可以不传，sdk 会自动生成
    'additional_info' => '测试',  // 可选项
    'req_reserved' => '保留字段',  // 可选项
    'risk_content' => '{"client_ip":"100.0.0.0"}',
];

$ret = $baofoo->prePay($payData);
```


### 确定支付

```php

$payData = [
    'business_no'    => 'TI170915124325276174', // `prePay` 那一步宝付返回得到的业务流水号
    'sms_code'         => '123456', // 支付时的短信验证码,若开通短信类交易则必填
    'trade_date'       => '20170915191103', // 订单交易日期(M),可以不传，sdk 会自动生成
    'additional_info' => '', //附加字段(O),长度不超过 128 位
    'req_reserved'    => '', //请求方保留域(O)
];

$ret = $baofoo->doPay($payData);
```

### 其它接口

其它接口暂不列出示例，请查阅 `Sdk.php` 代码。

## 参考资源

- 《宝付认证支付 API 商户接入接口文档》
- Inspired by [navyxie/baofoo](https://github.com/navyxie/baofoo) 。

## 联系方式

在使用中，遇到问题可以发 `issue` ，或者通过以下方式联系作者我。

- Email: raoyc <raoyc2009@gmail.com>
- 官网：http://douyasi.com
- QQ群：260655062
- Github: [ycrao](https://github.com/ycrao)
