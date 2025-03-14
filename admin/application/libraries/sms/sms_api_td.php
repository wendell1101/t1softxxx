<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * TEXTSDAILY SMS API implementation
 * http://central.textsdaily.com/index
 *
 * Config items:
 * * sms_api_textsdaily_token
 * * sms_api_textsdaily_country_code
 */
class Sms_api_td extends Abstract_sms_api {
    const SUCCESS_CODE = "success";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://textsdaily.com/api/sms';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }

        if(substr($mobile,0,1) == '0'){
            $mobile = substr($mobile,1);
        }

        $fields = array();
        $sms_list['to'] = $dialingCode.$mobile;
        $sms_list['from'] = $this->getParam('sms_from');
        $sms_list['text'] = $content;

        $fields['messages'] = array($sms_list);
        $this->utils->debug_log("===============textsdaily SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer '.$this->getParam('token')));
        $this->setCurlProxyOptions($handle);

        return $fields;
    }

    public function send($mobile, $content, $dialingCode = NULL){
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


        $this->utils->debug_log("===============sms api curl response ", $content);
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
        $this->utils->debug_log("===============sms isSuccess ", $isSuccess);

        if($isSuccess){
            $flag = Response_result::FLAG_NORMAL;
        }
        else{
            $flag = Response_result::FLAG_ERROR;
        }

        $response_result_id = $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag,
            self::SMS_API, self::SMS_API, json_encode($resultAll), $statusCode, $statusText, $header,
            array('player_id' => $player_id, 'related_id3' => $mobile));

        return $content;
    }

    public function getErrorMsg($returnQueryString) {
        $response = json_decode($returnQueryString,true);
        if(isset($response['data']['errors']['errors']) && !empty($response['data']['errors']['errors'])){
            return $response['data']['errors']['errors'];
        }else{
            return $errorDesc = "Errors";
        }
    }

    public function isSuccess($returnQueryString) {
        $response = json_decode($returnQueryString,true);
        if(!empty($response['data']['success']))
            return true;
        else
            return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_td_'.$name);
    }
}
