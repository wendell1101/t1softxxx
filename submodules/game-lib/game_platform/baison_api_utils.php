<?php

trait baison_api_utils {
    function desEncode($key, $str)
    {
        $str = $this->pkcs5_pad(trim($str), 16);
        $encrypt_str = openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING);
        return base64_encode($encrypt_str);
    }

    function desDecode($key, $str)
    {
        $str = base64_decode($str);
        $decrypt_str = openssl_decrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING);
        return trim($this->pkcs5_unpad($decrypt_str));
    }

    function mcrypt_desEncode($encryptKey, $str)
    {
        $str = trim($str);
        $blocksize = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $str = $this->pkcs5_pad($str, $blocksize);
        $iv = @mcrypt_create_iv(@mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $encrypt_str = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $encryptKey, $str, MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypt_str);
    }

    function mcrypt_desDecode($encryptKey, $str)
    {
        $str = base64_decode($str);
        $iv = @mcrypt_create_iv(@mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
        $decrypt_str = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $encryptKey, $str, MCRYPT_MODE_ECB, $iv);
        return $this->pkcs5_unpad(trim($decrypt_str));
    }

    function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function pkcs5_unpad($text)
    {
        //$pad = ord($text{strlen($text)-1});
        $pad = ord(substr($text, -1));
        
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }

    function pkcs7_pad($source, $blocksize)
    {
        $source = trim($source);
        $pad = $blocksize - (strlen($source) % $blocksize);
        if ($pad <= $blocksize) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    function pkcs7_unpad($source)
    {
        $source = trim($source);
        $char = substr($source, -1);
        $num = ord($char);
        if ($num == 62) return $source;
        $source = substr($source, 0, -$num);
        return $source;
    }

    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function microtime_int()
    {
        return (int)(microtime(true));
    }
}
