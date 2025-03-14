<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * MOVIDER SMS API implementation
 * https://movider.co/en/
 *
 * Config items:
 * * sms_api_movider_api_key
 * * sms_api_movider_api_secret
 */
class Sms_api_movider extends Abstract_sms_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.movider.co/v1/sms';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array();
        $fields['api_key']  = $this->getParam('api_key');
        $fields['api_secret']  = $this->getParam('api_secret');
        $fields['from']  = $this->getParam('api_from');
        $fields['text'] = $content;
        $fields['to']  = $dialingCode.$mobile;
        $this->utils->debug_log("===============movider SMS fields", $fields);
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
        $params = json_decode($returnQueryString, true);
        if(isset($params['error']) && !empty($params['error']) && isset($params['error']['description']) && !empty($params['error']['description'])){
            return $params['error']['description'];
        }else{
            return 'UNKNOWN ERROR!';
        }
    }

    public function isSuccess($returnQueryString) {
        $params = json_decode($returnQueryString, true);
        if(!empty($params['phone_number_list'])){
            return true;
        }else{
            return false;
        }
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_movider_'.$name);
    }
}
