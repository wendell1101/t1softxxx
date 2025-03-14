<?php

/**
 *
 * Defines general behavior of "notify in app" API classes.
 *
 * General behaviors include:
 * * Generate Content
 * * Send Content
 *
 *
 * @category notify in app
 * @version 2.8.18
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_notify_api {

    public $prefix_string = 'notify_api';

    public $call_socks5_proxy;
    public $call_socks5_proxy_login;
    public $call_socks5_proxy_password;
    public $call_http_proxy_host;
    public $call_http_proxy_port;
    public $call_http_proxy_login;
    public $call_http_proxy_password;

	function __construct() {
		$this->CI = &get_instance();
		$this->utils = $this->CI->utils;

		$this->call_socks5_proxy          = $this->CI->config->item('call_socks5_proxy');
		$this->call_socks5_proxy_login    = $this->CI->config->item('call_socks5_proxy_login');
		$this->call_socks5_proxy_password = $this->CI->config->item('call_socks5_proxy_password');
		$this->call_http_proxy_host       = $this->CI->config->item('call_http_proxy_host');
		$this->call_http_proxy_port       = $this->CI->config->item('call_http_proxy_port');
		$this->call_http_proxy_login      = $this->CI->config->item('call_http_proxy_login');
		$this->call_http_proxy_password   = $this->CI->config->item('call_http_proxy_password');
	}

	const NOTIFY_IN_APP_API = 'NOTIFY_IN_APP';
    const STATUS_OK = '200';
	// -- functions for sending notify --
	public abstract function getUrl();
	public abstract function getFields($notify_token, $notify_data, $mode);
	# Parse the response, return error message if any
	public abstract function getErrorMsg($response);
	# Parse the response, return a boolean indicating whether the send succeed
	public abstract function isSuccess($response);

	public function getPlatformCode(){
		return NOTIFY_IN_APP_API; #API number for notify apis
	}

	// $content needs to be in UTF-8 encoding
    public function send($notify_token, $notify_data, $mode, $player_id = null){
        $this->CI->load->model(['response_result']);
        $url = $this->getUrl();

        $params = $this->getFields($notify_token, $notify_data, $mode);
        // $fields_string = http_build_query($fields);
        // $params = $fields_string;

        $_headers = $this->getHeaders(); // for auth

        $config = [];
        $config['is_post'] = true; // use POST method
        $config['post_json'] = true; // POST data via json
        $config['is_result_json'] = true;
        $config['timeout_second'] = 3;
        $config['connect_timeout'] = 3;

        $config['header_array'] = $_headers;

        list( $header
            , $content
            , $statusCode
            , $statusText
            , $errCode
            , $error
            , $resultObj ) = $this->utils->httpCall($url, $params, $config, $initSSL=null);
        $result = $content;


		$flag = $this->isSuccess($result) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;

        $resultAll['type']     = 'notify_in_app';
        // for the uri, /system_management/show_response_content/resp_20230206.15
        $resultAll['url']      = $url;
		$resultAll['params']   = $params;
		$resultAll['content']  = $result;
        $_fields = [];

        if(! empty($player_id) ){
            $_fields['player_id'] = $player_id;
        }
        $_fields['related_id3'] = $notify_token;
        $response_result_id = $this->CI->response_result->saveResponseResult( $this->getPlatformCode() // #1
                                                                                , $flag // #2
                                                                                , $url // #3
                                                                                , json_encode($params) // #4
                                                                                , json_encode($resultAll) // #5
                                                                                , $statusCode // #6
                                                                                , $statusText // #7
                                                                                , $header // #8
                                                                                , $_fields // #9
                                                                            );

		return $result;

    }

	// perform curl configuration, by default post the fields returned by getFields.
	// Subclasses can overwrite this function for non-default curl configuration
	protected function configCurl($handle, $notify_token, $content, $dialingCode) {
		$url = $this->getUrl();
		$content = $this->signContent($content);
		$fields = $this->getFields($notify_token, $content, $dialingCode);
		$fields_string = http_build_query($fields);


        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        curl_setopt($handle,CURLOPT_URL,$url);
		curl_setopt($handle,CURLOPT_POST,count($fields));
		curl_setopt($handle,CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($handle,CURLOPT_RETURNTRANSFER,1);
        $this->setCurlProxyOptions($handle);

		return $fields;
	}

	// Some API will require user to append signature to notify content
	// Subclasses can overwrite this function to provide implementation of the signature
	protected function signContent($content) {
		return $content;
	}
    protected function getHeaders(){
        return [];
    }

	# Returns a string describing the balance. Empty string if balance query is not supported by API.
	public function getBalanceString() {
		return '';
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

    public function getParam($param) {
		return $this->CI->config->item("{$this->prefix_string}_{$this->api_name}_{$param}");
	}


}
