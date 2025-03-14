<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * Config items:
 * * sms_api_xunmiao_account
 * * sms_api_xunmiao_password
 */

class Sms_api_xunmiao extends Abstract_sms_api {
    const SUCCESS_CODE = "200";
    const API_RESPONSE_INSUFFICIENT_BALANCE = '余额不足，请先充值';
    const API_RESPONSE_INVALID_PHONE = '没有有效的手机号码或未配置国家价格';


    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "https://agentapi.xunmiao.net/api/intl/api/send";
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
       
        $this->utils->debug_log("===============xunmiao SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        
        $result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }

        $errorCode = $result['code'];
        $errorDesc = $result['codeInfo'];

        if ($errorDesc == self::API_RESPONSE_INSUFFICIENT_BALANCE && !empty($this->CI->config->item('Sms_api_xunmiao_custom_insufficient_balance_msg'))) {
            $errorDesc = $this->CI->config->item('Sms_api_xunmiao_custom_insufficient_balance_msg');
        }
        elseif($errorDesc == self::API_RESPONSE_INVALID_PHONE){
            $errorDesc = lang('No valid phone number or country pricing not configured.');
        }

        $this->utils->error_log("===============xunmiao return [$errorCode]: $errorDesc", $returnQueryString);
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
        return $this->CI->config->item('sms_api_xunmiao_'.$name);
    }

}
