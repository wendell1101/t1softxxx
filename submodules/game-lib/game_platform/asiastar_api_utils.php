<?php

trait asiastar_api_utils {
    function microtime_int()
    {
        return (int)(microtime(true) * 1000);
    }

    function set_cipher($cipher) {
        $this->cipher = $cipher;
    }
    
    function set_mode($mode)
    {
        $this->mode = $mode;
    }
    
    function set_iv($iv)
    {
        $this->iv = $iv;
    }
    
    function set_key($key)
    {
        $this->secret_key = $key;
    }
    
    function require_pkcs5() {
        $this->pad_method = 'pkcs5';
    }

    function pad_or_unpad($str, $ext) {
        if ( is_null($this->pad_method) ) {
            return $str;
        } else{
            $func_name = __CLASS__ . '::' . $this->pad_method . '_' . $ext . 'pad';
            if ( is_callable($func_name) )
            {
                $size = mcrypt_get_block_size($this->cipher, $this->mode);
                return call_user_func($func_name, $str, $size);
            }
        }
        return $str;
    }
    
    function pad($str) {
        return $this->pad_or_unpad($str, '');
    }
    
    function unpad($str) {
        return $this->pad_or_unpad($str, 'un');
    }
    
    function encrypt($str) {
        $str = $this->pad($str);
        $td = mcrypt_module_open($this->cipher, '', $this->mode, '');
        if ( empty($this->iv) )
        {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }
        mcrypt_generic_init($td, hex2bin($this->md5key), $iv);
        $cyper_text = mcrypt_generic($td, $str);
        $rt = bin2hex($cyper_text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $rt;
    }
    
    function decrypt($str){
        $td = mcrypt_module_open($this->cipher, '', $this->mode, '');
        if ( empty($this->iv) )
        {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }
        mcrypt_generic_init($td, $this->md5key, $iv);
        //$decrypted_text = mdecrypt_generic($td, self::hex2bin($str));
        $decrypted_text = mdecrypt_generic($td, base64_decode($str));
        $rt = $decrypted_text;
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $this->unpad($rt);
    }
    
    function hex2bin($hexdata) {
        $bindata = '';
        $length = strlen($hexdata);
        for ($i=0; $i< $length; $i += 2)
        {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }
        return $bindata;
    }
    
    function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    function pkcs5_unpad($text)
    {
        //$pad = ord($text{strlen($text) - 1});
        $pad = ord(substr($text, -1));
        
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }
}
