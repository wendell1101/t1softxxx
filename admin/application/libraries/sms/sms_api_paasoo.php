<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * PAASOO SMS API implementation
 * https://api.paasoo.cn/json
 *
 * Config items:
 * * sms_api_paasoo_key
 * * sms_api_paasoo_secret
 */
class Sms_api_paasoo extends Abstract_sms_api {
    const SUCCESS_CODE = "0";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.paasoo.cn/json?';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array();
        $fields['key']  = $this->getParam('api_key');
        $fields['secret']  = $this->getParam('api_secret');
        $fields['from']  = $this->CI->config->item('sms_from');
        $fields['to']  = $dialingCode.$mobile;
        $fields['text'] = $content;

        $this->utils->debug_log("===============paasoo SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $content = $this->signContent($content);
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_string = http_build_query($fields);
        curl_setopt($handle,CURLOPT_URL,$url.$fields_string);
        curl_setopt($handle,CURLOPT_POST, false);
        curl_setopt($handle,CURLOPT_RETURNTRANSFER,1);

        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        $params = json_decode($returnQueryString, true);
        if(isset($params['status_code']) && !empty($params['status_code'])){
            return $params['status_code'];
        }else{
            return 'UNKNOWN ERROR!';
        }
    }

    public function isSuccess($returnQueryString) {
        $params = json_decode($returnQueryString, true);
        if(isset($params['status']) && $params['status'] == self::SUCCESS_CODE){
            return true;
        }else{
            return false;
        }
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_paasoo_'.$name);
    }

}
