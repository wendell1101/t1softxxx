<?php

trait Hawk_authentication_module
{
    /**
     * Hawk Request Authorization
     *
     * @return array
     */
    function requestAuthorization($hmackey='',$str='hawk.1.header',$str2='hawk.1.response')
    {

        $hdrs = $this->getallheaders();
        $auth = empty($hdrs['Authorization']) ? '' : $hdrs['Authorization'];
        $hawk = $this->parseHeader($auth);

        $str = $str . "\n" .
            $hawk['ts'] .  "\n" .
            $hawk['nonce'] . "\n" .
            $_SERVER['REQUEST_METHOD'] . "\n" .
            $_SERVER['REQUEST_URI'] . "\n" .
            $hdrs['Host'] . "\n" .                          // locally you can use "localhost" . "\n" .
            ((!$this->utils->isHttps()) ? 80 : 443) . "\n" .  // locally you can use "55136" . "\n" .
            $hawk['hash'] . "\n" .
            $hawk['ext'] . "\n";

        $str2 = $str2 . "\n" .
            $hawk['ts'] .  "\n" .
            $hawk['nonce'] . "\n" .
            $_SERVER['REQUEST_METHOD'] . "\n" .
            $_SERVER['REQUEST_URI'] . "\n" .
            $hdrs['Host'] . "\n" .                          // locally you can use "localhost" . "\n" .
            ((!$this->utils->isHttps()) ? 80 : 443) . "\n" .  // locally you can use "55136" . "\n" .
            //$hawk['hash']
            "". "\n" .
            $hawk['ext'] . "\n";

        $hash   = base64_encode(hash_hmac('sha256', $str, $hmackey, true));
        $hash2  = base64_encode(hash_hmac('sha256', $str2, $hmackey, true));

        $header = 'hawk mac="'.$hash2.'", ext="'.$hawk['ext'].'"';

        return [
            'status'    => $hawk['mac'] == $hash ? true : false,
            'header'    => $header,
            'url'       => $hdrs['Host']
        ];
    }

    /**
     * Get All Headers
     *
     * @return array
     */
    public function getAllHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Parse Hawk header
     *
     * @param string $hawk
     *
     * @return mixed
     */
    public function parseHeader($hawk = '')
    {
        if(! empty($hawk)){
            $segments = explode(', ', substr(trim($hawk), 5, -1));

            $parts['id'] = substr($segments[0], 4, strlen($segments[0])-5);
            $parts['ts'] = substr($segments[1], 4, strlen($segments[1])-5);
            $parts['nonce'] = substr($segments[2], 7, strlen($segments[2])-8);
            $parts['ext'] = substr($segments[3], 5, strlen($segments[3])-6);
            $parts['mac'] = substr($segments[4], 5, strlen($segments[4])-6);
            $parts['hash'] = substr($segments[5], 6, strlen($segments[5])-6);

            return $parts;
        }else{
            return $parts = [
                'id' => null,
                'ts' => null,
                'nonce' => null,
                'ext' => null,
                'mac' => null,
                'hash' => null
            ];
        }
    }
}