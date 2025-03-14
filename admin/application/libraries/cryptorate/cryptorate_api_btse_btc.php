<?php
require_once dirname(__FILE__) . '/abstract_cryptorate_api.php';

/**
 * BTSE Crypto Rate API implementation
 * https://api.btse.com/spot/api/v3.2/price
 *
 */
class Cryptorate_api_btse_btc extends Abstract_cryptorate_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.btse.com/spot/api/v3.2/price';
    }

	public function convertCryptoCurrency($amount, $base, $target, $paymentType) {
		$rate_list = json_decode(file_get_contents($this->getUrl()), true);
        $cust_fix_rate = 0;

        foreach ($rate_list as $key => $value) {
        	if($rate_list[$key]['symbol'] == "BTC-CNY"){
        		$rate = $this->getDecimalPlaceSetting($rate_list[$key]['indexPrice']);
        	}
        }

        if(!empty($rate)){
            $cust_fix_rate = $this->getDecimalPlaceSetting($rate * $this->getCustFixRate('BTC',$paymentType));
            if(strtoupper($base) == 'BTC'){
                $target_amount = $this->getDecimalPlaceSetting($amount * $cust_fix_rate);
            }elseif(strtoupper($base) == 'CNY'){
                $target_amount = $this->getDecimalPlaceSetting($amount / $cust_fix_rate);
            }
        }

		if(!empty($cust_fix_rate) && isset($target_amount)){
            $this->utils->debug_log("BTC Crypto rate and target amount", $cust_fix_rate ,$target_amount);
			return array($target_amount, $cust_fix_rate);
		}else{
            $this->utils->debug_log("Cryptorate_api_btse_btc has error");
			return false;
		}
	}

	protected function getParam($name) {
		return $this->CI->config->item('cryptorate_api_btse_btc_'.$name);
	}
}
