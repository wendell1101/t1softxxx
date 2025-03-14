<?php

class simpleplay_des
{
    var $key;
    var $iv;
    function __construct( $creds ) {
        $key = isset($creds['key']) ? $creds['key'] : 0;
        $iv = isset($creds['iv']) ? $creds['iv'] : 0;
        $this->key = $key;
        if( $iv == 0 ) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
        }
    }

    function encrypt($str) {
		return base64_encode( openssl_encrypt($str, 'DES-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv  ) );
	}
    function decrypt($str) {
		$str = openssl_decrypt(base64_decode($str), 'DES-CBC', $this->key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $this->iv);
		return rtrim($str, "\x01..\x1F");
    }

    function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }
}

?>