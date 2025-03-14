<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * coingecko Crypto Rate API implementation
 * https://api.coingecko.com/api/v3/simple/price?ids=binancecoin&vs_currencies=vnd
 *
 */
class Cryptorate_api_coingecko_bnb extends Abstract_cryptorate_api {

    public function getUrl() {
        $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.coingecko.com/api/v3/simple/price?ids=binancecoin&vs_currencies='.$defaultCurrency;
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $table = json_decode(file_get_contents($this->getUrl()), true);
        $cust_fix_rate = 0;

        if($this->utils->getCurrentCurrency()['currency_code'] == 'VND'){
            $rate = $this->getDecimalPlaceSetting($table['binancecoin']['vnd']);
        }else if($this->utils->getCurrentCurrency()['currency_code'] == 'CNY'){
            $rate = $this->getDecimalPlaceSetting($table['binancecoin']['cny']);
        }else if($this->utils->getCurrentCurrency()['currency_code'] == 'THB'){
            $rate = $this->getDecimalPlaceSetting($table['binancecoin']['thb']);
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('BNB',$paymentType));
            if(strtoupper($base) == 'BNB'){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }else if(strtoupper($base) == 'VND' || strtoupper($base) == 'CNY' || strtoupper($base) == 'THB'){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

        if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("BNB Crypto rate and target amount", $cust_fix_rate, $target_amount);
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("Cryptorate_api_coingecko_bnb has error");
            return false;
        }
    }

    public function getParam($name) {
        return $this->CI->config->item('cryptorate_api_coingecko_bnb_'.$name);
    }
}
