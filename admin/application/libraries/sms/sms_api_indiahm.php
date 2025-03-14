<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * url :https://www.indiahm.com/sms/send
 *
 * Config items:
 * * sms_api_indiahm_accessId
 * * sms_api_indiahm_secret
 * 
 */
class Sms_api_indiahm extends Abstract_sms_api {
    const SUCCESS_CODE = 1;

	protected function signContent($content) {
		return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
	}

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://www.indiahm.com/sms/send';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'mobile'   => $dialingCode.$mobile,
            'from'     => $this->CI->config->item('sms_from'),
            'accessId' => $this->getParam('accessId'),
            'secret'   => $this->getParam('secret'),
            'content'  => $content,
            'type'     => 'OTP',
        );
       
        $this->utils->debug_log("===============indiahm SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
		$result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['status'];
        $errorDesc = $result['responseDescription'];

        $this->utils->error_log("===============indiahm return [$errorCode]: $errorDesc", $returnQueryString);

        return $errorCode.": ".$errorDesc;
    }

	public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if( isset($result['status']) && $result['status'] == self::SUCCESS_CODE)
            return true;
        else
            return false;
	}

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_indiahm_'.$name);
    }

}
