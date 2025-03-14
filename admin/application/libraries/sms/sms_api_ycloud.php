<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';
/**
 * Config items:
 * * sms_api_ycloud_apiKey
 */

class Sms_api_ycloud extends Abstract_sms_api {

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "https://api.ycloud.com/v2/sms";
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'to'   => $dialingCode.$mobile,
            'text' => $content,
        );
 
        $this->utils->debug_log("===============ycloud SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $content = $this->signContent($content);
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);
        $apiKey = $this->getParam('apiKey');

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'X-API-Key: '.$apiKey,
            'Content-Type: application/json',
            'accept: application/json'
        ));

        $this->setCurlProxyOptions($handle);
        return $fields;
    }

    public function send($mobile, $content, $dialingCode = NULL){
        $this->utils->debug_log("===============sms send", $mobile);
        $ch = curl_init();
        $fields      = $this->configCurl($ch, $mobile, $content, $dialingCode);
        $result      = curl_exec($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($result, 0, $header_size);
        $content     = substr($result, $header_size);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusText  = $errCode . ':' . $error;
        curl_close($ch);

        $this->utils->debug_log("===============ycloud sms api curl response ", $content);
        if($content === false) {
            $this->utils->error_log("Error sending SMS through ".get_class($this). "...", $error);
        }
        #save to response result
        $this->CI->load->model(array('response_result'));

        $player_id = NULL;
        $resultAll['type']     = 'sms';
        $resultAll['url']      = $this->getUrl();
        $resultAll['params']   = $fields;
        $resultAll['content']  = $content;

        $isSuccess = $this->isSuccess($content);
        $this->utils->debug_log("===============ycloud sms isSuccess ", $isSuccess);

        if($isSuccess){
            $flag = Response_result::FLAG_NORMAL;
        }
        else{
            $flag = Response_result::FLAG_ERROR;
        }

        $response_result_id = $this->CI->response_result->saveResponseResult(
            $this->getPlatformCode(),
            $flag,
            self::SMS_API,
            self::SMS_API,
            json_encode($resultAll),
            $statusCode,
            $statusText,
            '',
            array('player_id' => $player_id, 'related_id3' => $mobile) //9
        );

        return $content;
    }

    public function getErrorMsg($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['error']['code'];
        $errorDesc = $result['error']['message'];

        $this->utils->error_log("===============ycloud return [$errorCode]: $errorDesc", $returnQueryString);

        return $errorCode.": ".$errorDesc;
    }

    public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if(!isset($result['error']))
            return true;
        else
            return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_ycloud_'.$name);
    }

}
