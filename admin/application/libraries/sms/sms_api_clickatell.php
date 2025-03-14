<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * Clickatell SMS API implementation
 * http://central.clickatell.com/index
 *
 * Config items:
 * * sms_api_clickatell_user
 * * sms_api_clickatell_password
 * * sms_api_clickatell_api_id
 * * sms_api_clickatell_country_code
 */
class Sms_api_clickatell extends Abstract_sms_api {
    const SUCCESS_CODE = "ID:";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'http://api.clickatell.com/http/sendmsg';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array();
        $fields['user']  = $this->getParam('user');
        $fields['password']  = $this->getParam('password');
        $fields['api_id']  = $this->getParam('api_id');
        $fields['to']  = $dialingCode.$mobile;
        if ($this->CI->config->item('sms_used_UCS2BE') == true) {
            $fields['text']  = bin2hex(mb_convert_encoding($content, 'UCS-2BE', 'auto'));
            $fields['unicode']  = '1';
        }else{
            $fields['text'] = $content;
        }
        $this->utils->debug_log("===============clickatell SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $content = $this->signContent($content);
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_string = http_build_query($fields);
        curl_setopt($handle,CURLOPT_URL,$url);
        curl_setopt($handle,CURLOPT_POST,count($fields));
        curl_setopt($handle,CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($handle,CURLOPT_RETURNTRANSFER,1);

        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        return $returnQueryString;
    }

    public function isSuccess($returnQueryString) {
        if(strpos($returnQueryString, self::SUCCESS_CODE) === 0)
            return true;
        else
            return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_clickatell_'.$name);
    }
}
