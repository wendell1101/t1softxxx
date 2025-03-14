<?php
require_once dirname(__FILE__) . '/abstract_exchange_rate_api.php';

/**
 * HUOBI Crypto Rate API implementation
 * https://api.huobi.pro/general/exchange_rate/list
 *
 */
class Exchange_rate_api_huobi_super extends Abstract_exchange_rate_api {
    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.huobi.pro/general/exchange_rate/list';
    }

    public function getExchangeRate($base, $target){
        $table = json_decode(file_get_contents($this->getUrl()), true);
        $base = $this->changeBaseFromEqualExchange(strtolower($base));
        $rate_list = $table['data'];
        $target_rate = null;
        foreach ($rate_list as $key => $value) {
            if($rate_list[$key]['name'] == "{$base}_{$target}"){
                $target_rate = $this->getDecimalPlaceSetting($rate_list[$key]['rate']);
                $this->utils->debug_log("base currency = {$base}, target currency = {$target}, target_rate ", $target_rate);
                return $target_rate;
            }
        }
        $this->utils->debug_log("base currency = {$base}, target currency = {$target}, not existed target rate");
        return $target_rate;
    }

	public function getParam($properties) {
        return $this->utils->getConfig('exchange_rate_api_huobi_super_'.$properties);
	}
    
    private function changeBaseFromEqualExchange($base){
        $equal_exchange_rate = $this->utils->getConfig('equal_exchange_rate');        
        if(in_array($base, $equal_exchange_rate)){
            $base = 'usd';
        }
        return $base;
    }
}
