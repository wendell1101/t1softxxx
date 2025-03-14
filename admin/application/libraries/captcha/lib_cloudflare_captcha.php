<?php

/**
 * Cloudflare captcha library
 * config item:
 * enable_cloudflare_captcha_on_login, cloudflare_captcha_key, cloudflare_captcha_secret
 *
 */
class Lib_cloudflare_captcha
{
	/**
	 * @property CI_Controller $CI
	 */
	private $CI;
	/**
	 * @property Utils $utils
	 */
	private $utils;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->utils=$this->CI->utils;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isEnable(){
		return $this->utils->getConfig('enable_cloudflare_captcha_on_login');
	}

	/**
	 *
	 * @return string
	 */
	public function getClientKey(){
		return $this->utils->getConfig('cloudflare_captcha_key');
	}

	public function validate($token, $ip)
	{
		$url='https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$method='POST';
		$params=[
			'secret'=>$this->utils->getConfig('cloudflare_captcha_secret'),
			'response'=>$token,
			'remoteip'=>$ip,
			'idempotency_key'=>random_string('alnum', 32),
		];

		list($header, $content, $statusCode, $statusText, $errCode, $error, $obj)=
			$this->utils->callHttp($url, $method, $params);

		$json=$this->utils->decodeJson($content);

		if ($json['success']) {
			return true;
		} else {
			// print log
			$this->utils->error_log('cloudflare_captcha validate failed', $json);
			return false;
		}
	}
}
