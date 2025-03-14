<?php
/**
 * Abstract exchange rate api
 *
 * Abstract exchange rate api
 *
 * @package		Abstract_exchange_rate_api
 * @subpackage	Libraries
 * @category	Libraries
 * @version		1.0.0
 */
abstract class Abstract_exchange_rate_api {
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->library(array('utils'));
		$this->utils = $this->CI->utils;
		$this->call_socks5_proxy          = $this->CI->config->item('call_socks5_proxy');
		$this->call_socks5_proxy_login    = $this->CI->config->item('call_socks5_proxy_login');
		$this->call_socks5_proxy_password = $this->CI->config->item('call_socks5_proxy_password');
	}

	public abstract function getExchangeRate($base_currency, $target_currency);

	public abstract function getUrl();

	public abstract function getParam($properties);

	public function getDecimalPlaceSetting($number){
		$decimalPlace = abs($this->getParam('decimal_place'));
		$number_converted = number_format($number, $decimalPlace, '.', '');
		if (floatval($number_converted) == 0) {
			$number_converted = $this->utils->smallNumberToFixed($number);
		}
		return $number_converted;
	}

	protected function configCurl($handle, $fields) {
        $url = $this->getUrl();
        $fields_json = json_encode($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        $this->setCurlProxyOptions($handle);

        $this->utils->debug_log('=====================configCurl','url', $url, 'fields', $fields);

        return $fields;
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

		$this->utils->debug_log("===============exchange rate api curl", 'content', $content, 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);
		if($response === false) {
			$this->utils->error_log("Error sending exchange rate through ".get_class($this). "...", curl_error($ch));
		}
		curl_close($ch);

		return $content;
	}

	# set proxy
	public function setCurlProxyOptions($curl_resources){
		// set proxy
		$settle_proxy = FALSE;
		if ($settle_proxy === FALSE && !empty($this->call_socks5_proxy)) {
			$this->utils->debug_log('http call with socks5 proxy', $this->call_socks5_proxy);
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
			$this->utils->debug_log('http call with http proxy', $this->call_http_proxy_host);
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
