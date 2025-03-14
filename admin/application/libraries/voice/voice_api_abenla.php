<?php
session_start();
require_once dirname(__FILE__) . '/abstract_voice_api.php';

/**
 * abenla voice API implementation
 * * url:    http://api.abenla.com/api/SendSms
 * * method: GET
 *
 * Config items:
 * * voice_api_abenla_username
 * * voice_api_abenla_sendsmspassword
 * * voice_api_abenla_url			  (optional)
 */
class Voice_api_abenla extends Abstract_voice_api {
	const RESULT_CODE_SUCCESS 			= 106;
	const ARG_SERVICETYPEID_DEFAULT		= 271;
	const ARG_BRANDNAME_DEFAULT			= 'n/a';
	const ARG_CALLBACK_DEFAULT			= 'false';
	const URL_DEFAULT					= 'http://api.abenla.com/api/SendSms';

	protected $ident = 'VOICE_ABENLA';
	protected $api_name = 'abenla';
	protected $error_code = [
		100 => 'Other' ,
		101 => 'UserNotExist' ,
		102 => 'WrongPassword' ,
		103 => 'AccountIsDeActivated' ,
		104 => 'CanNotAccess' ,
		105 => 'AccountIsZero' ,
		106 => 'Success' ,
		107 => 'WrongSign' ,
		108 => 'WrongBrandName' ,
		109 => 'ExceedSms' ,
		110 => 'SendSmsFail' ,
		111 => 'WrongServiceType' ,
		112 => 'ExpiredRequest' ,
		113 => 'NotHasAESKey' ,
		114 => 'ProcessFail' ,
		115 => 'RecordNotExists' ,
		116 => 'RecordExisted' ,
		117 => 'BlacklistKeyword' ,
		118 => 'WrongTemplate' ,
	];

    public function getUrl() {
        $url = $this->getParam('url');
        $url = empty($baseurl) ? self::URL_DEFAULT : $baseurl;

        // $this->CI->utils->debug_log("{$this->ident} url", $url);

        return $url;
    }

	protected function configCurl($handle, $mobile, $content, $dialingCode) {
		$url = $this->getUrl();
		$fields = $this->getFields($mobile, $content, $dialingCode);

		// workaround: send brandName=n/a without url encoding
		// unset($fields['brandName']);
		// $message = $fields['message'];
		// unset($fields['message']);
		// $fields_string = http_build_query($fields);
		// $fields_string .= "&brandName=" . self::ARG_BRANDNAME_DEFAULT;
		// $fields_string .= "&message={$message}";
		$fields_ar = [];
		foreach ($fields as $f => $v) {
			$fields_ar[] = "{$f}={$v}";
		}
		$fields_string = implode('&', $fields_ar);

		$url .= "?{$fields_string}";

		$this->utils->debug_log("{$this->ident} configCurl full url", $url);

		curl_setopt($handle, CURLOPT_URL, $url);
		// curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_POST, 0);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

		return $fields;
	}

	public function getFields($mobile, $content, $dialingCode) {
		$fields = [
			'loginName'		=> $this->getParam('username') ,
			'sign'			=> md5($this->getParam('sendsmspassword')) ,
			'serviceTypeId'	=> self::ARG_SERVICETYPEID_DEFAULT ,
			'phoneNumber'	=> $dialingCode . $mobile ,
			'message'		=> "\"{$content}\"" ,
			'brandName'		=> self::ARG_BRANDNAME_DEFAULT ,
			'callBack'		=> self::ARG_CALLBACK_DEFAULT ,
			'smsGuid'		=> 1 ,
			// 'ab_dynamic_field'	=> null ,
		];

		$this->utils->debug_log("{$this->ident} request fields", $fields);

		return $fields;
	}

	public function getErrorMsg($returnQueryString) {
		$mesg = 'Unknown error';

		$resp = json_decode($returnQueryString, 1);
		if (empty($resp)) {
			return $mesg;
		}

		$mesg = isset($resp['Message']) ? $resp['Message'] : null;
		$mesg .= isset($resp['Code']) ? "; Code={$resp['Code']}" : null;
		$mesg .= isset($resp['SmsPerMessage']) ? "; SmsPerMessage={$resp['SmsPerMessage']}" : null;

		$this->utils->debug_log("{$this->ident} getErrorMsg", [ 'mesg' => $mesg, 'resp' => $resp ]);

		return $mesg;
	}

	public function isSuccess($returnQueryString) {
		$result = false;

		$resp = json_decode($returnQueryString, 1);

		if (isset($resp['Code']) && $resp['Code'] == self::RESULT_CODE_SUCCESS) {
			$result = true;
		}

		$this->utils->debug_log("{$this->ident} isSuccess", [ 'result' => $result, 'resp' => $resp ]);

		return $result;
	}

	protected function getParam($param) {
		return $this->CI->config->item("voice_api_{$this->api_name}_{$param}");
	}


}
