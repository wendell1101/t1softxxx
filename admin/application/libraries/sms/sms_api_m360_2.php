<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';
/**
 * Config items:
 * * sms_api_m360_2_account
 * * sms_api_m360_2_password
 */

class Sms_api_m360_2 extends Abstract_sms_api {
    const SUCCESS_CODE = "201";

    protected function signContent($content) {
        return sprintf("%s", $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "https://api.m360.com.ph/v3/api/broadcast";
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'app_key'  => $this->getParam('app_key'),
            'app_secret' => $this->getParam('app_secret'),
            'msisdn'    => $dialingCode.$mobile,
            'content'      => $content,
            'shortcode_mask' => $this->getParam('shortcode_mask'),
        );

        $this->utils->debug_log("===============m360 SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {

        $result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['code'];
        $errorDesc = $result['name'];

        $this->utils->error_log("===============m360 return [$errorCode]: $errorDesc", $returnQueryString);

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
        return $this->CI->config->item('sms_api_m360_2_'.$name);
    }

}