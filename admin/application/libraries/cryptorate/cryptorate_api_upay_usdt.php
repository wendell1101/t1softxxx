<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * upay Crypto Rate API implementation
 * https://openexchangerates.org/api/latest.json
 *
 */
class Cryptorate_api_upay_usdt extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://openexchangerates.org/api/latest.json';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $cust_fix_rate = 0;
        $rate = 0;

        $currency = $this->utils->getCurrentCurrency()['currency_code'];
        $params['app_id'] = $this->getParam('app_id');
        $params['base'] = $this->getParam('base');
        $params['symbols'] = $currency;


        $this->CI->utils->debug_log('=====================upay usdt crypto params', $params, $amount, $base, $target, $paymentType);

        $cryptoRate = json_decode($this->getCryptoRate($params), true);
        $this->CI->utils->debug_log('=====================upay usdt crypto cryptoRate', $cryptoRate);
        if (!empty($cryptoRate) && !empty($cryptoRate['rates'])) {
            $rate = $cryptoRate['rates']['BRL'];
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate(self::COIN_USDT, $paymentType));
            if(strtoupper($base) == self::COIN_USDT){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }elseif(strtoupper($base) == $currency){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }
        }else{
            $this->utils->debug_log("=====================upay usdt crypto getCryptoRate fail");
            return false;
        }

        $this->utils->debug_log("=====================upay usdt crypto rate and target amount", $cust_fix_rate, $target_amount);

        if(!empty($cust_fix_rate) && isset($target_amount)){
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("=====================upay usdt crypto convertCryptoCurrency fail");
            return false;
        }
    }

    // public function getCryptoRate($params){
    //     $data['currency_from'] = $params['currency'];
    //     $data['currency_to'] = $params['convert_to'];
    //     return $this->processCurl($data);
    // }

    public function getCryptoRate($params){
        #https://openexchangerates.org/api/latest.json?app_id=e0a5f19955a541fa859d1869fe86fa6a&base=USD&symbols=BRL
        // $crypto_url = $this->getSystemInfo('crypto_rate_url');
        $data['app_id']  = $params['app_id'];
        $data['base']    = $params['base'];
        $data['symbols'] = $params['symbols'];
        return $this->processCurl($data);
    }

    protected function configCurl($handle, $fields) {
        $url = $this->getUrl();
        // $fields_json = json_encode($fields);
        $url = $url.'?'.http_build_query($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($handle, CURLOPT_HTTPHEADER, $this->getHeaders($fields));
        // curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        $this->setCurlProxyOptions($handle);

        $this->utils->debug_log('=====================configCurl','url', $url, 'fields', $fields);

        return $fields;
    }

    protected function getParam($name) {
        return $this->CI->config->item('cryptorate_api_upay_usdt_'.$name);
    }
}
