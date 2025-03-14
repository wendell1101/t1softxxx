<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * okx Crypto Rate API implementation
 * https://www.okx.com/api/v5/market/exchange-rate
 *
 */
class Cryptorate_api_okx_usdt extends Abstract_cryptorate_api {
    public function getUrl() {
        $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
        return $this->getParam('url') ? $this->getParam('url') : 'https://www.okx.com/api/v5/market/exchange-rate';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        if (($data = @file_get_contents($this->getUrl())) === false) {
            $this->utils->debug_log("Cryptorate_api_okx_usdt failed to open stream");
            return false;
        } else {
            $table = json_decode($data, true);
        }

        $this->utils->debug_log("============okx usdt", $table);
        $cust_fix_rate = 0;

        if($this->utils->getCurrentCurrency()['currency_code'] == 'CNY'){
            $rate = $this->getDecimalPlaceSetting($table['data'][0]['usdCny']);
        }

        if(!empty($rate)){
            switch ($target) {
                case 'USDTL':
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDTL',$paymentType));
                    break;
                case 'USDT':
                    $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('USDT',$paymentType));
                    break;
                default:
                    case 'USDT':
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
            $this->utils->debug_log("Cryptorate_api_okx_usdt has error");
            return false;
        }
    }

    public function getParam($name) {
        return $this->CI->config->item('cryptorate_api_okx_usdt_'.$name);
    }
}
