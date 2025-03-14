<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * url :https://www.chuanxin.com/sms/send
 *
 * Config items:
 * * sms_api_chuanxin_accessId
 * * sms_api_chuanxin_secret
 * 
 */
class Sms_api_chuanxin extends Abstract_sms_api {
    const SUCCESS_CODE = 00000;

	protected function signContent($content) {
		return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
	}

    public function getUrl() {
        $url=$this->getParam('url') ? $this->getParam('url') : 'http://47.242.85.7:9090/sms/batch/v2?';
        return $url;
    }
    public function getFields($mobile, $content, $dialingCode) {
        $currency = $this->utils->getCurrentCurrency()['currency_code'];
        $this->utils->debug_log("===============chuanxin origin currency", $currency);

        $appkey = $this->getParam('appkey');
        $appcode = $this->getParam('code');
        $appsecret = $this->getParam('secret');
        if($currency=='PHP'){   
            $appkey = $this->getParam('appkey_PHP');
            $appsecret = $this->getParam('secret_PHP');
        }
        $this->utils->debug_log("===============chuanxin currency", $currency);
        $this->utils->debug_log("===============chuanxin dialingCode", $dialingCode);

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        trim($dialingCode,'+');

        $fields = array(
            'appkey' => $appkey,
            'appcode' => $appcode,
            'appsecret'   => $appsecret,
            'uid'      =>uniqid('', true),
            'phone'   => $dialingCode.$mobile,
            'msg'  => $content,
        );
       
        $this->utils->debug_log("===============chuanxin SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
		$result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['code'];
        $errorDesc = $result['desc'];

        $this->utils->error_log("===============chuanxin return [$errorCode]: $errorDesc", $returnQueryString);

        return $errorCode.": ".$errorDesc;
    }

	public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if( isset($result['status'])  == self::SUCCESS_CODE)
            return true;
        else
            return false;
	}

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_chuanxin_'.$name);
    }

	protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();

        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_string = http_build_query($fields);

        curl_setopt($handle,CURLOPT_URL,$url.$fields_string);
        curl_setopt($handle,CURLOPT_POST, false);
		curl_setopt($handle,CURLOPT_RETURNTRANSFER,1);
        $this->setCurlProxyOptions($handle);
        $this->utils->error_log("===============chuanxin url ", $url.$fields_string);

		return $fields;
	}
}
