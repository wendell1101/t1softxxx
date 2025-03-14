<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * upay Crypto Rate API implementation
 * https://jst-168u.cc/Apipay/query_rate
 *
 */
class Cryptorate_api_jstpay_usdt extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://jst-168u.cc/Apipay/query_rate';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $cust_fix_rate = 0;
        $rate = 0;
        $currency = $this->utils->getCurrentCurrency()['currency_code'];

        $cryptoRate = json_decode($this->getCryptoRate(), true);
        $this->CI->utils->debug_log('=====================jstpay usdt crypto cryptoRate', $cryptoRate);
        
        if (!empty($cryptoRate)) {
            $rate = $cryptoRate['BRL'];
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate(self::COIN_USDT, $paymentType));

            if(strtoupper($base) == self::COIN_USDT){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }elseif(strtoupper($base) == $currency){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }
        }else{
            $this->utils->debug_log("=====================jstpay usdt crypto getCryptoRate fail");
            return false;
        }

        $this->utils->debug_log("=====================jstpay usdt crypto rate and target amount", $cust_fix_rate, $target_amount);

        if(!empty($cust_fix_rate) && isset($target_amount)){
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("=====================jstpay usdt crypto convertCryptoCurrency fail");
            return false;
        }
    }

    public function getCryptoRate(){
        $params['trade_currency'] = self::COIN_USDT;
        return $this->processCurl($params);
    }

    public function getHeaders(){
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded"
        );
        $this->utils->debug_log('=====================jstpay crypto getHeaders', $headers);
        return $headers;
    }

    protected function configCurl($handle, $fields) {
        $url = $this->getUrl();

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($fields));
        $this->setCurlProxyOptions($handle);

        $this->utils->debug_log('=====================configCurl','url', $url, 'fields', $fields);

        return $fields;
    }

    protected function getParam($name) {
        return $this->CI->config->item('cryptorate_api_jstpay_usdt_'.$name);
    }
}
