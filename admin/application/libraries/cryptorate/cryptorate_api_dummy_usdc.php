<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

// For alphabook to convert USD and USDC , fix rate 1:1
class Cryptorate_api_dummy_usdc extends Abstract_cryptorate_api {

    public function getUrl() {
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $cust_fix_rate = 1;

        if($this->utils->getCurrentCurrency()['currency_code'] == 'USD'){
            $rate = $this->getDecimalPlaceSetting($cust_fix_rate = 1);
        }else{
            $this->utils->debug_log("Cryptorate_api_dummy_usdc has error");
            return false;
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDC',$paymentType) );

            if((strtoupper($base) == 'USDC') || (strtoupper($base) == 'USDC')){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }else if(strtoupper($base) == 'USD'){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

        if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("USDT Crypto rate and target amount", $cust_fix_rate, $target_amount);
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("Cryptorate_api_dummy_usdc has error");
            return false;
        }
    }

    public function getParam($name) {
        return $this->CI->config->item('cryptorate_api_dummy_usdc_'.$name);
    }
}
