<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * coingecko Crypto Rate API implementation
 * https://api.coingecko.com/api/v3/simple/price?ids=Tether&vs_currencies=vnd
 *
 */
class Cryptorate_api_coingecko_usdt extends Abstract_cryptorate_api {

    public function getUrl() {
        $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.coingecko.com/api/v3/simple/price?ids=Tether&vs_currencies='.$defaultCurrency;
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $table = json_decode(file_get_contents($this->getUrl()), true);
        $cust_fix_rate = 0;

        if($this->utils->getCurrentCurrency()['currency_code'] == 'VND'){
            $rate = $this->getDecimalPlaceSetting($table['tether']['vnd']);
        }else if($this->utils->getCurrentCurrency()['currency_code'] == 'CNY'){
            $rate = $this->getDecimalPlaceSetting($table['tether']['cny']);
        }else if($this->utils->getCurrentCurrency()['currency_code'] == 'THB'){
            $rate = $this->getDecimalPlaceSetting($table['tether']['thb']);
        }else if($this->utils->getCurrentCurrency()['currency_code'] == 'BRL'){
            $rate = $this->getDecimalPlaceSetting($table['tether']['brl']);
        }

        if(!empty($rate)){
            switch ($target) {
                case 'USDTL':
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDTL',$paymentType));
                    break;
                case 'USDT':
                default:
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDT',$paymentType));
                    break;
            }

            if((strtoupper($base) == 'USDTL') || (strtoupper($base) == 'USDT')){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }else if(strtoupper($base) == 'VND' || strtoupper($base) == 'CNY' || strtoupper($base) == 'THB' || strtoupper($base) == 'BRL'){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

        if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("USDT Crypto rate and target amount", $cust_fix_rate, $target_amount);
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("Cryptorate_api_coingecko_usdt has error");
            return false;
        }
    }

    public function convertCryptoCurrencyByPcfApi($amount, $base, $target, $paymentType) {

        if(!empty($base)){
            $url = 'https://api.coingecko.com/api/v3/simple/price?ids=Tether&vs_currencies='.$base;
            $table = json_decode(file_get_contents($url), true);
            if(empty($table['tether'])){
                $this->utils->debug_log("Cryptorate_api_coingecko_usdt has error");
                return false;
            }
            $rate = $this->getDecimalPlaceSetting($table['tether'][strtolower($base)]);
        }else{
            $table = json_decode(file_get_contents($this->getUrl()), true);

            if($this->utils->getCurrentCurrency()['currency_code'] == 'VND'){
                $rate = $this->getDecimalPlaceSetting($table['tether']['vnd']);
            }else if($this->utils->getCurrentCurrency()['currency_code'] == 'CNY'){
                $rate = $this->getDecimalPlaceSetting($table['tether']['cny']);
            }else if($this->utils->getCurrentCurrency()['currency_code'] == 'THB'){
                $rate = $this->getDecimalPlaceSetting($table['tether']['thb']);
            }else if($this->utils->getCurrentCurrency()['currency_code'] == 'BRL'){
                $rate = $this->getDecimalPlaceSetting($table['tether']['brl']);
            }
        }

        $cust_fix_rate = 0;

        if(!empty($rate)){
            switch ($target) {
                case 'USDTL':
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDTL',$paymentType));
                    break;
                case 'USDT':
                default:
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDT',$paymentType));
                    break;
            }

            if((strtoupper($base) == 'USDTL') || (strtoupper($base) == 'USDT')){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }else if(strtoupper($base) == 'VND' || strtoupper($base) == 'CNY' || strtoupper($base) == 'THB' || strtoupper($base) == 'BRL'){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

        if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("USDT Crypto rate and target amount", $cust_fix_rate, $target_amount);
            return array($target_amount, $cust_fix_rate);
        }else{
            $this->utils->debug_log("Cryptorate_api_coingecko_usdt has error");
            return false;
        }
    }

    public function getParam($name) {
        return $this->CI->config->item('cryptorate_api_coingecko_usdt_'.$name);
    }
}
