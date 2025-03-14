<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * HUOBI Crypto Rate API implementation
 * https://api.huobi.pro/general/exchange_rate/list
 *
 */
class Cryptorate_api_huobi_usdt extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.huobi.pro/general/exchange_rate/list';
    }

	public function convertCryptoCurrency($amount, $base, $target,$paymentType) {
		$table = json_decode(file_get_contents($this->getUrl()), true);
        $rate_list = $table['data'];
        $cust_fix_rate = 0;

        // $this->utils->debug_log('get rate data list', $rate_list);

        foreach ($rate_list as $key => $value) {
        	if($rate_list[$key]['name'] == "usdt_cny"){
                $rate = $this->getDecimalPlaceSetting($rate_list[$key]['rate']);
                $this->utils->debug_log('get rate from list', $rate);
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
                }
                $this->utils->debug_log('get cust_fix_rate', $cust_fix_rate);
        	}
        }

        if((strtoupper($base) == 'USDTL') || (strtoupper($base) == 'USDT')){
        	$target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
        }elseif(strtoupper($base) == 'CNY'){
            if($cust_fix_rate!=0){
            	$target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

		if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("USDT Crypto rate and target amount", $cust_fix_rate, $target_amount);
			return array($target_amount, $cust_fix_rate);
		}else{
            $this->utils->debug_log("Cryptorate_api_huobi_usdt has error");
			return false;
		}
	}

	public function getParam($name) {
		return $this->CI->config->item('cryptorate_api_huobi_usdt_'.$name);
	}
}
