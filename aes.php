<?php
/*From https://www.jianshu.com/p/f941a309b087 */
class AES {
    /**
 *
 * @param string $string 需要加密的字符串
 * @param string $key 密钥
 * @return string
 */ public static function encrypt($string, $key)
    {
        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        // openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = strtoupper(bin2hex($data)); return $data;
    }
    /**
    * @param string $string 需要解密的字符串
* @param string $key 密钥
* @return string
    */
    public static function decrypt($string, $key)
    {
        // 对接java，服务商做的AES加密通过SHA1PRNG算法（只要password一样，每次生成的数组都是一样的），Java的加密源码翻译php如下：
         $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
         $decrypted = openssl_decrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA); return $decrypted;
    }
}

?>