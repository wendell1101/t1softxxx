<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * indodax Crypto Rate API implementation
 * https://indodax.com/api/summaries
 *
 */
class Cryptorate_api_indodax_usdt extends Abstract_cryptorate_api {

    public function getUrl() {
        $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
        return $this->getParam('url') ? $this->getParam('url') : 'https://indodax.com/api/summaries';
    }

    public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
        $table = json_decode(file_get_contents($this->getUrl()), true);
        $cust_fix_rate = 0;
        if($this->utils->getCurrentCurrency()['currency_code'] == 'IDR'){
            if($paymentType == 'deposit'){
                $rate = $this->getDecimalPlaceSetting($table['tickers']['usdt_idr']['buy']);
            }else if($paymentType == 'withdrawal'){
                $rate = $this->getDecimalPlaceSetting($table['tickers']['usdt_idr']['sell']);
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
                }else if(strtoupper($base) == 'IDR'){
                    $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
                }
            }
        }

        if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("USDT Crypto rate and target amount", $cust_fix_rate ,$target_amount);
            return array($target_amount,$cust_fix_rate);
        }else{
            $this->utils->debug_log("Cryptorate_api_indodax_eth has error");
            return false;
        }
    }

    public function getParam($name) {
        return $this->CI->config->item('cryptorate_api_indodax_usdt_'.$name);
    }
}
