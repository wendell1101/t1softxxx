<?php

/**
 *
 * Defines general behavior of Crypto Rate API classes.
 *
 * General behaviors include:
 * * Generate Content
 * * Send Content
 *
 *
 * @category CRYPTORATE
 * @version 2.8.18
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_cryptorate_api {

	const COIN_BTC   = "BTC";
	const COIN_ETH   = "ETH";
	const COIN_USDT  = "USDT";
	const COIN_USDTE = "USDTE";
	const COIN_USDTT = "USDTT";
	const COIN_BCH   = "BCH";
	const COIN_LTC   = "LTC";

	const WITHDRAWAL = 'withdrawal';
	const DEPOSIT    = 'deposit';

	function __construct() {
		$this->CI = &get_instance();
		$this->utils = $this->CI->utils;

		$this->call_socks5_proxy          = $this->CI->config->item('call_socks5_proxy');
		$this->call_socks5_proxy_login    = $this->CI->config->item('call_socks5_proxy_login');
		$this->call_socks5_proxy_password = $this->CI->config->item('call_socks5_proxy_password');
	}

	// -- functions for convert crypto currency --
	public abstract function convertCryptoCurrency($amount, $base, $target, $paymentType);

	// -- functions for get data url --
	public abstract function getUrl();

	//-- get config to fix decimal place
	public function getDecimalPlaceSetting($number){
		$decimalPlace = abs($this->getParam('decimal_place'));
		$number_converted = number_format($number, $decimalPlace, '.', '');
		// OGP-23116: ensure enough decimal places for very small amount
		if (floatval($number_converted) == 0) {
			$number_converted = $this->utils->smallNumberToFixed($number);
		}
		// $this->utils->debug_log(__METHOD__, 'number_converted after', $number_converted, 'number', $number);
		return $number_converted;
	}

	//-- get config to Update Timing
	public function getUpdateTiming(){
		$updateTiming = abs($this->getParam('update_timing'));
		return $updateTiming;
	}

	public function getAllowCompareDigital(){
		$allowCompareDigital = abs($this->getParam('allow_compare_digital'));
		return $allowCompareDigital;
	}

	//-- get config to crypto input decimal place
	public function getInputDecimalPlaceSetting($reciprocal = true){
		if($reciprocal){
			$inputDecimalPlace = abs($this->getParam('crypto_input_decimal_place'));
			return number_format((1/pow(10,$inputDecimalPlace)),$inputDecimalPlace,'.','');
		}else{
			$inputDecimalPlace = abs($this->getParam('crypto_input_decimal_place'));
			return $inputDecimalPlace;
		}
	}

	public function getCustFixRate($cryptoCurrency, $paymentType){
		$this->CI->load->model('payment_account');
		$cryptoCurrencySetting = $this->CI->payment_account->getCryptoCurrencySetting($cryptoCurrency, $paymentType);
		if(!empty($cryptoCurrencySetting) && is_array($cryptoCurrencySetting)){
			return abs($cryptoCurrencySetting[0]['exchange_rate_multiplier']);
		}else{
			return 1;
		}
	}

	public function processCurl($fields){
		$ch = curl_init();

		$fields      = $this->configCurl($ch, $fields);
		$response    = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($response, 0, $header_size);
		$content     = substr($response, $header_size);
		$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errCode     = curl_errno($ch);
		$error       = curl_error($ch);
		$statusText  = $errCode . ':' . $error;

		$this->utils->debug_log("===============crypto api curl", 'content', $content, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
		if($response === false) {
			$this->utils->error_log("Error sending crypto through ".get_class($this). "...", curl_error($ch));
		}
		curl_close($ch);

		return $content;
	}

	# set proxy
	public function setCurlProxyOptions($curl_resources){
		// set proxy
		$settle_proxy = FALSE;
		if ($settle_proxy === FALSE && !empty($this->call_socks5_proxy)) {
			$this->CI->utils->debug_log('http call with socks5 proxy', $this->call_socks5_proxy);
			curl_setopt($curl_resources, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			curl_setopt($curl_resources, CURLOPT_PROXY, $this->call_socks5_proxy);
			if (!empty($this->call_socks5_proxy_login) && !empty($this->call_socks5_proxy_password)) {
				curl_setopt($curl_resources, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($curl_resources, CURLOPT_PROXYUSERPWD, $this->call_socks5_proxy_login . ':' . $this->call_socks5_proxy_password);
			}
			$settle_proxy = TRUE;
		}

		if($settle_proxy === FALSE && !empty($this->call_http_proxy_host)){
			//http proxy
			$this->CI->utils->debug_log('http call with http proxy', $this->call_http_proxy_host);
			curl_setopt($curl_resources, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($curl_resources, CURLOPT_PROXY, $this->call_http_proxy_host);
			curl_setopt($curl_resources, CURLOPT_PROXYPORT,  (empty($this->call_http_proxy_port)) ? 3128 : $this->call_http_proxy_port);
			if (!empty($this->call_http_proxy_login) && !empty($this->call_http_proxy_password)) {
				curl_setopt($curl_resources, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($curl_resources, CURLOPT_PROXYUSERPWD, $this->call_http_proxy_login . ':' . $this->call_http_proxy_password);
			}
		}

		return $this;
	}
}
