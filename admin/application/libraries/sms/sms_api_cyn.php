<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';
/**
 * Config items:
 * * sms_api_cyn_api_id
 * * sms_api_cyn_api_password
 * * sms_api_cyn_sender_id
 */

class Sms_api_cyn extends Abstract_sms_api {
    const SUCCESS_CODE = "S";
    const SMS_TYPE = "P";
    const SMS_ENCODING = "T";

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "http://coremmm.acestartelecoms.net/api/SendSMS";
    }

    public function send($mobile, $content, $dialingCode = NULL){
        $ch = curl_init();

        $fields      = $this->configCurl($ch, $mobile, $content, $dialingCode);
        $result      = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = substr($result, 0, $header_size);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errCode     = curl_errno($ch);
        $error       = curl_error($ch);
        $statusText  = $errCode . ':' . $error;
        $content     = substr($result, $header_size);

        $this->utils->debug_log("===============sms api curl response ", $content);
        if($content === false) {
            $this->utils->error_log("Error sending SMS through ".get_class($this). "...", curl_error($ch));
        }
        curl_close($ch);

        #save to response result
        $this->CI->load->model(array('response_result'));
        $player_id = NULL;
        $resultAll['type']     = 'sms';
        $resultAll['url']      = $this->getUrl();
        $resultAll['params']   = $fields;
        $resultAll['content']  = $content;
        $flag = $this->isSuccess($content) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;

        $response_result_id = $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag,
            self::SMS_API, self::SMS_API, json_encode($resultAll), $statusCode, $statusText, $header,
            array('player_id' => $player_id, 'related_id3' => $mobile));

        return $content;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        // $content = $this->signContent($content);
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $this->setCurlProxyOptions($handle);
        return $fields;
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'api_id' => $this->getParam('api_id'),
            'api_password' => $this->getParam('api_password'),
            'sms_type' => self::SMS_TYPE,
            'encoding' => self::SMS_ENCODING,
            'sender_id' => $this->getParam('sender_id'),
            'phonenumber' => $dialingCode.$mobile,
            'textmessage' => $content,
        );
       
        $this->utils->debug_log("===============cyn SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        
        $result = json_decode($returnQueryString, true);
        if (!is_array($result)) {
            return $returnQueryString;
        }
        $errorCode = $result['status'];
        $errorDesc = $result['remarks'];

        $this->utils->error_log("===============cyn return [$errorCode]: $errorDesc", $returnQueryString);

        return $errorCode.": ".$errorDesc;
    }

    public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if($result['status'] == self::SUCCESS_CODE)
            return true;
        else
            return false;
    }
    protected function getParam($name) {
        return $this->CI->config->item('sms_api_cyn_'.$name);
    }

}