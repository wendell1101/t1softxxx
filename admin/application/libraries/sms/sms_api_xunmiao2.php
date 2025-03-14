<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';
/**
 * Config items:
 * * sms_api_xunmiao2_account
 * * sms_api_xunmiao2_password
 */

class Sms_api_xunmiao2 extends Abstract_sms_api {
    const SUCCESS_CODE = "0";

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "http://openapi.xunmiao.net/api/intl-sms/send";
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'account'  => $this->getParam('account'),
            'password' => $this->getParam('password'),
            'phone'    => $dialingCode.$mobile,
            'msg'      => $content,
        );
       
        $this->utils->debug_log("===============xunmiao2 SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        
        $result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['code'];
        $errorDesc = $result['msg'];

        $this->utils->error_log("===============xunmiao2 return [$errorCode]: $errorDesc", $returnQueryString);

        return $errorCode.": ".$errorDesc;
    }

    public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if($result['code'] == self::SUCCESS_CODE)
            return true;
        else
            return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_xunmiao2_'.$name);
    }

}
