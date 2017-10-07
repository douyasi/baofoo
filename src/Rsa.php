<?php

namespace Douyasi\Baofoo;

use Douyasi\Baofoo\BaofooException;

/**
 * Class Rsa 宝付 RSA 加密解密类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 */
class Rsa
{

    private $private_key;
    private $public_key;
    private $debug;

    const BAOFOO_ENCRYPT_LEN = 32;

    /**
     * 构造方法
     * 
     * @param array $bfpayConf 宝付配置数组
     */
    public function __construct($bfpayConf)
    {

        // 宝付相关配置，如果没有公私钥文件路径配置，默认走测试证书

        $private_key_password = isset($bfpayConf['private_key_password']) ? $bfpayConf['private_key_password'] : '123456';
        $public_key_path      = isset($bfpayConf['public_key_path']) && !empty($bfpayConf['public_key_path']) ? $bfpayConf['public_key_path'] : __DIR__ . '/../res/cer/bfkey_100000276@@100000990.cer';
        $private_key_path     = isset($bfpayConf['private_key_path']) && !empty($bfpayConf['private_key_path']) ? $bfpayConf['private_key_path'] : __DIR__ . '/../res/cer/bfkey_100000276@@100000990.pfx';
        $this->debug          = isset($bfpayConf['debug']) ? $bfpayConf['debug'] : false;

        // 私钥
        $pkcs12 = file_get_contents($private_key_path);
        $private_key = [];
        openssl_pkcs12_read($pkcs12, $private_key, $private_key_password);
        $this->private_key = $private_key['pkey'];

        // 公钥
        $keyFile = file_get_contents($public_key_path);
        $this->public_key = openssl_get_publickey($keyFile);

        if ($this->debug) {
            echo 'public key path: '.$public_key_path.' .'.PHP_EOL;
            echo 'private key path: '.$private_key_path.' .'.PHP_EOL;
            echo 'private key '.(empty($private_key) == true ? 'unavailable' : 'available').' .'.PHP_EOL;
            echo 'Baofoo public key '.(empty($this->public_key) == true ? 'unavailable' : 'available').' .'.PHP_EOL;
        }
    }

    /**
     * 公钥解密
     * 
     * @param  string $encrypted 密文
     * @return string
     */
    public function decryptByPublicKey($encrypted)
    {
        $decrypted = '';
        $decryptPos = 0;
        $totalLen = strlen($encrypted);
        try {
            while ($decryptPos < $totalLen) {
                openssl_public_decrypt(hex2bin(substr($encrypted, $decryptPos, self::BAOFOO_ENCRYPT_LEN * 8)), $decryptData, $this->public_key);
                $decrypted .= $decryptData;
                $decryptPos += self::BAOFOO_ENCRYPT_LEN * 8;
            }
            $decrypted = base64_decode($decrypted);
        } catch (\Exception $e) {
            throw BaofooException('Baofoo rsa decryptByPublicKey error:', $e->getMessage(), BaofooException::BAOFOO_GET_RSA_INFO_ERROR);
        }

        return $decrypted;
    }


    /**
     * 私钥加密
     * 
     * @param  string $decrypted 明文内容
     * @return string
     */
    public function encryptedByPrivateKey($decrypted)
    {
        $decrypted = base64_encode($decrypted);
        $encrypted = '';
        $totalLen = strlen($decrypted);
        $encryptPos = 0;
        try {
            while ($encryptPos < $totalLen) {
                openssl_private_encrypt(substr($decrypted, $encryptPos, self::BAOFOO_ENCRYPT_LEN), $encryptData, $this->private_key);
                $encrypted .= bin2hex($encryptData);
                $encryptPos += self::BAOFOO_ENCRYPT_LEN;
            }
        } catch (\Exception $e) {
            throw BaofooException('Baofoo rsa encryptedByPrivateKey error:', $e->getMessage(), BaofooException::BAOFOO_GET_RSA_INFO_ERROR);
        }

        return $encrypted;
    }

}