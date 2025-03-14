<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';
/**
 * Config items:
 * * sms_api_d7networks_auth
 */

class Sms_api_d7networks extends Abstract_sms_api {
    const SUCCESS_CODE = "accepted";

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "https://api.d7networks.com/messages/v1/send";
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }

        $fields = array(
            'messages' => [
                array(
                    'originator' => 'SignOTP',
                    'recipients' => [$dialingCode.$mobile],
                    'content'    => $content,
                )
            ],
        );
       
        $this->utils->debug_log("===============d7networks SMS fields", $fields);
        return $fields;
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
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer '.$this->getParam('auth')));
        $this->setCurlProxyOptions($handle);

        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        $message = 'error occurred';
        if(isset($result['detail']) && isset($result['detail']['message'])){
            $message = $result['detail']['message'];
        }

        $this->utils->error_log("===============d7networks return error", $returnQueryString);
        return $message;
    }
    

    public function isSuccess($returnQueryString) {
        $response = json_decode($returnQueryString, true);
        if (isset($response['status']) && $response['status'] == self::SUCCESS_CODE) {
            return true;
        } else {
            return false;
        }
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_d7networks_'.$name);
    }

}
