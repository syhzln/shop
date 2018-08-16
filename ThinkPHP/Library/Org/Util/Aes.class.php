<?php
namespace Org\Util;
class aes
{
	
    /**
     * pkcs7补码
     * @param string $string  明文
     * @param int $blocksize Blocksize , 以 byte 为单位
     * @return String
     */ 
    private function addPkcs7Padding($string, $blocksize = 32) {
        $len = strlen($string); //取得字符串长度
        $pad = $blocksize - ($len % $blocksize); //取得补码的长度
        $string .= str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
        return $string;
    }

    /**
     * 加密然后base64转码
     * 
     * @param String 明文
     * @param $key 密钥
     */
    function aes256ecbEncrypt($str, $key ) {   
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $this->addPkcs7Padding($str) , MCRYPT_MODE_ECB, ''));
    }

    /**
     * 除去pkcs7 padding
     * 
     * @param String 解密后的结果
     * 
     * @return String
     */
    private function stripPkcs7Padding($string){
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);

        if(preg_match("/$slastc{".$slast."}/", $string)){
            $string = substr($string, 0, strlen($string)-$slast);
            return $string;
        } else {
            return false;
        }
    }

    /**
     * 解密 
     * @param String $encryptedText 二进制的密文 
     * @param String $key 密钥
     * @return String
     */
    function aes256ecbDecrypt($encryptedText, $key) {
        $encryptedText =base64_decode($encryptedText);
        return $this->stripPkcs7Padding(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $encryptedText, MCRYPT_MODE_ECB, ''));
    }
}