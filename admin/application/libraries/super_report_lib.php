<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Super report
 *
 * Super report library
 *
 * @package		Super_report
 * @author		Gary
 * @version		1.0.0
 */

class Super_report_lib {
	private $master_currency_code = null;
	private $master_currency_decimals = null;
	private $exchange_api = null;
	private $available_currency_list = [];
	private $available_currencies = [];
	
	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library(array('utils', 'playerapi_lib'));
		$this->ci->load->model(['super_report']);
		$this->utils=$this->ci->utils;	
		$this->super_report = $this->ci->super_report;
		$this->init();
	}

	public function init(){
		$this->master_currency_code = $this->getMasterCurrencyCode();
		$this->master_currency_decimals = $this->getMasterCurrencyDecimals();
		$this->available_currency_list = $this->utils->getAvailableCurrencyList();
		$this->available_currencies = array_keys($this->available_currency_list);
	}

	public function getAvailableCurrencies(){
		if(!empty($this->master_currency_code)){
			array_unshift($this->available_currencies, 'super');
			return $this->available_currencies;
		}else{
			return $this->available_currencies;
		}		
	}
	public function getDecimalsConfigByCurrency($currency){
		if($currency == 'super'){
			return $this->master_currency_decimals;
		}else{
			if(isset($this->available_currency_list[$currency]['decimals'])){
				return $this->available_currency_list[$currency]['decimals'];
			}else{
				return 2;
			}
		}
	}
	
	public function getMasterCurrencyCode() {
		$masterCurrencySettings = $this->utils->getConfig('super_report_settings');
		if(!empty($masterCurrencySettings['master_currency']['code']) && $this->utils->isAvailableCurrencyKey($masterCurrencySettings['master_currency']['code'], false)){
			return $masterCurrencySettings['master_currency']['code'];
		}else{
			return null;
		}
	}

	public function getMasterCurrencyDecimals(){
		$masterCurrencySettings = $this->utils->getConfig('super_report_settings');
		if(!empty($masterCurrencySettings['master_currency']['decimals'])){
			return $masterCurrencySettings['master_currency']['decimals'];
		}else{
			return 2;
		}
	}

	public function getExchangeApiForMaster($target_currency) {
		$master_exchange_rate_api = $this->utils->getConfig('super_report_settings')['master_exchange_rate_api'];
		if(!in_array($target_currency, array_keys($master_exchange_rate_api), true)){
			return $master_exchange_rate_api['default'];
		}else{
			return $master_exchange_rate_api[$target_currency];
		}
	}
		
	public function convertTargetAmount($amount, $base, $target){
		$targetAmount = null;
		if($this->utils->isAvailableCurrencyKey($base, false) && $this->utils->isAvailableCurrencyKey($target, false)){
			$exchangeApi = $this->getExchangeApiForMaster($target);
			$exchangeRateData = $this->super_report->getExchangetRateFromDB($exchangeApi ,$base, $target);
			if(!empty($exchangeRateData['rate'])){
				$targetAmount = number_format($amount * $exchangeRateData['rate'], $this->master_currency_decimals, '.', '');
				return $targetAmount;
			}
		}
		return $targetAmount;
	}

}
/* End of file super_report.php */
/* Location: ./application/libraries/super_report.php */