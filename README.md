# Douyasi/Baofoo

可能是最好的 Baofoo 支付第三包


### 使用示例

这里给出宝付绑卡示例：

```php
<?php

// 自动加载
require __DIR__ . '/vendor/autoload.php';

$config = [
    'member_id'   => '100000276',
    'terminal_id' => '100000990',
    'request_url' => 'http://vgw.baofoo.com/cutpayment/api/backTransRequest',
];

$bfpayConf = [
    'private_key_password' => '123456',
    'public_key_path'      => '',
    'private_key_path'     => '',
    'debug'                => true,
];

$bindData = [
    'acc_no' => '6222020111122220000',
    'id_holder' => '张宝',
    'mobile' => '13800000000',
    'id_card' => '320301198502169142',
];
$baofoo = new \Douyasi\Baofoo\Sdk($config, $bfpayConf);
$ret = $baofoo->bindCard($bindData);

var_dump($ret);
```