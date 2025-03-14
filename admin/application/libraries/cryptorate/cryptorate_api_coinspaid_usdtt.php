<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * coinspaid Crypto Rate API implementation
 * https://app.sandbox.cryptoprocessing.com/api/v2/currencies/rates
 *
 */
class Cryptorate_api_coinspaid_usdtt extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://app.sandbox.cryptoprocessing.com/api/v2/currencies/rates';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $cust_fix_rate = 0;
        $rate = 0;

        $currency = $this->utils->getCurrentCurrency()['currency_code'];
        $params['currency'] = $base;
        $params['convert_to'] = $currency;

        if ($paymentType == self::WITHDRAWAL) {
            $params['currency'] = $target;
        }

        $this->CI->utils->debug_log('=====================coinspaid usdtt crypto params', $params, $amount, $base, $target, $paymentType);

        $cryptoRate = json_decode($this->getCryptoRate($params), true);
        $this->CI->utils->debug_log('=====================coinspaid usdtt crypto cryptoRate', $cryptoRate);
        if (!empty($cryptoRate) && !empty($cryptoRate['data'])) {
            $rate = $cryptoRate['data'][0]['rate_to'];
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate(self::COIN_USDTT, $paymentType));
            if(strtoupper($base) == self::COIN_USDTT){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }elseif(strtoupper($base) == $currency){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }
        }else{
            $this->utils->debug_log("=====================coinspaid usdtt crypto getCryptoRate fail");
            return false;
        }

        $this->utils->debug_log("=====================coinspaid usdtt crypto rate and target amount", $cust_fix_rate, $target_amount);

        if(!empty($cust_fix_rate) && isset($target_amount)){
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("=====================coinspaid usdtt crypto convertCryptoCurrency fail");
            return false;
        }
    }

    public function getCryptoRate($params){
        $data['currency_from'] = $params['currency'];
        $data['currency_to'] = $params['convert_to'];
        return $this->processCurl($data);
    }

    public function getCryptoCalculate($params){
        $data['sender_amount'] = $params['amount'];
        $data['sender_currency'] = $params['convert_to'];
        $data['receiver_currency'] = $params['currency'];
        return $this->processCurl($data);
    }

    public function getHeaders($params){
        $headers = array(
            "X-Processing-Key: ".$this->getParam('header_key'),
            "X-Processing-Signature: ". $this->sign($params),
            "content-type: Content-Type: application/json"
        );

        $this->utils->debug_log('=====================coinspaid usdtt crypto getHeaders', $headers);
        return $headers;
    }

    protected function sign($params) {
        $requestBody = json_encode($params);
        $signature   = hash_hmac('sha512', $requestBody, $this->getParam('key'));
        return $signature;
    }

    protected function configCurl($handle, $fields) {
        $url = $this->getUrl();
        $fields_json = json_encode($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->getHeaders($fields));
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        $this->setCurlProxyOptions($handle);

        $this->utils->debug_log('=====================configCurl','url', $url, 'fields', $fields);

        return $fields;
    }

    protected function getParam($name) {
        return $this->CI->config->item('cryptorate_api_coinspaid_usdtt_'.$name);
    }
}
