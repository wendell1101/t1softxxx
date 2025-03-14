<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * HUOBI Crypto Rate API implementation
 * https://api.huobi.pro/general/exchange_rate/list
 *
 */
class Cryptorate_api_nopay_rate extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://wallet-pay.qb8.app/order/rateCoinQuery';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $cust_fix_rate = 0;
        $rate = 0;

        $currency = $this->utils->getCurrentCurrency();
        $params['currency'] = $target; //usdt
        $params['convert_to'] = $currency['currency_code'];


        $cryptoRate = json_decode($this->getCryptoRate($params), true);
        if (!empty($cryptoRate) && !empty($cryptoRate['data'])) {
            $rate = $cryptoRate['data']['rate'];
        }
        $amount=1;
        $this->utils->debug_log("====nopay_rate paramsparams",$params);

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate($base, $paymentType));
            // if(strtoupper($base) == self::COIN_USDT){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            // }elseif(strtoupper($base) == $currency){
            //     $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            // }
        }else{
            $this->utils->debug_log("=====================nopay_rate crypto getCryptoRate fail");
            return false;
        }

        $this->utils->debug_log("=====================nopay_rate crypto rate and target amount", $cust_fix_rate, $target_amount);

        if(!empty($cust_fix_rate) && isset($target_amount)){

            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("=====================nopay_rate crypto convertCryptoCurrency fail");
            return false;
        }
    }

    public function getCryptoRate($params){
        $sendData=[];
        $sendData['coin'] = $params['currency'];
        $sendData['currency'] = $params['convert_to']; 
        $sendData['timestamp'] = time(); 
        $sendData['sign'] = $this->sign($sendData); 
        return $this->processCurl($sendData);
    }

    public function getHeaders($params){
        $headers = array(
            "Content-Type: application/json",
            "version: v1",
            "appid:".$this->getParam('id')
        );
        $this->utils->debug_log('=====================nopay_rate crypto getHeaders', $headers);
        return $headers;
    }

    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = hash("sha256", $signStr);
        $this->utils->debug_log("=====================nopay crypto sign $sign");
        return $sign;
    }

    public function createSignStr($params) {

        $this->utils->debug_log("=====================nopay crypto params:",$params);

        $signStr = "";
        $params['appId'] = $this->getParam('id');
        ksort($params);//按key字母升序排序
        foreach ($params as $k=>$v){
            if($v){
                $signStr .="&$k=$v";
            }
        }
        $signStr .="&key=".$this->getParam('key');
        $signStr = trim($signStr,'&');
        $this->utils->debug_log("=====================nopay crypto signStr: $signStr params:", $params);
        return $signStr;
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

        $this->utils->debug_log('====================nopay_rate configCurl','url', $url, 'fields', $fields);

        return $fields;
    }

    protected function getParam($name) {
        return $this->CI->config->item('cryptorate_api_nopay_rate_'.$name);
    }
}
