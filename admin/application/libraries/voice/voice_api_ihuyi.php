<?php
session_start();
require_once dirname(__FILE__) . '/abstract_voice_api.php';

/**
 * ihuyi voice API implementation
 * http://api.voice.ihuyi.com/webservice/voice.php?method=Submit
 *
 * Config items:
 * * voice_api_ihuyi_apikey
 */
class Voice_api_ihuyi extends Abstract_voice_api {
	const SUCCESS_CODE = "2";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'http://api.voice.ihuyi.com/webservice/voice.php?method=Submit';
    }

	protected function configCurl($handle, $mobile, $content, $dialingCode) {
		$url = $this->getUrl();
		$fields = $this->getFields($mobile, $content, $dialingCode);
		$fields_string = http_build_query($fields);

		curl_setopt($handle, CURLOPT_URL,$url);
		curl_setopt($handle, CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_POST, 1);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

		return $fields;
	}

	public function getFields($mobile, $content, $dialingCode) {
		$fields = array(
			'account'  => $this->getParam('api_id'),
			'password' => $this->getParam('api_key'),
			'mobile'   => $mobile,
			'content'  =>  $content
		);
		$this->utils->debug_log("=================================ihuyi voice fields", $fields);
		return $fields;
	}

	public function getErrorMsg($returnQueryString) {
        $obj = simplexml_load_string($returnQueryString);
        $arr = $this->utils->xmlToArray($obj);

        $message = 'Unknown error!';
        if(isset($arr['code'])){
        	if(isset($arr['msg'])){
        		$message = $arr['code'].': '.$arr['msg'];
        	} else {
        		$message = $arr['code'].': '.self::ERROR_MSG[$arr['code']];
        	}
        }

		return $message;
	}

	public function isSuccess($returnQueryString) {
		$result = false;
        $obj = simplexml_load_string($returnQueryString);
        $arr = $this->utils->xmlToArray($obj);

        if(isset($arr['code']) && $arr['code'] == self::SUCCESS_CODE){
        	$result = true;
        }

		$this->utils->debug_log("=================================ihuyi voice isSuccess resp", $arr);
		return $result;
	}

	protected function getParam($name) {
		return $this->CI->config->item('voice_api_ihuyi_'.$name);
	}

	# Reference: https://developer.ihuyi.com/api/voice
	const ERROR_MSG = array(
		'0'     => '提交失败',
		'2'     => '提交成功',
		'400'   => '非法 ip访问',
		'401'   => '帐号不能为空',
		'402'   => '密码不能为空',
		'403'   => '手机号码不能为空',
		'4030'  => '手机号码已被列入黑名单',
		'404'   => '短信内容不能为空',
		'405'   => '用户名或密码不正确',
		'4050'  => '账号被冻结',
		'4051'  => '剩余条数不足',
		'4052'  => '访问 ip与备案ip不符',
		'406'   => '手机号码格式不正确',
		'407'   => '短信内容含有敏感字符',
		'4070'  => '语音验证码内容必须为 4-6位数字',
		'408'   => '您的帐户疑被恶意利用，已被自动冻结，如有疑问请与客服联系',
	);
}
