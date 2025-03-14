<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * buka SMS API implementation
 * http://central.buka.com/index
 *
 * Config items:
 * * sms_api_buka_key
 * * sms_api_buka_account
 * * sms_api_buka_appId
 */
class Sms_api_buka extends Abstract_sms_api {
    const SUCCESS_CODE = 0;

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://api.onbuka.com/v3/sendSms';
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }

        $fields = array(
            'appId' => $this->getParam('appId'),
            'numbers' => $dialingCode.$mobile,
            'content' => $content,
        );

        $this->utils->debug_log("===============buka SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);

        $account = $this->getParam('account');
        $key = $this->getParam('key');
        $timestamp = time();
        $sign = md5($account.$key.$timestamp);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8',
            'Sign:'.$sign,
            'Timestamp:'.$timestamp,
            'Api-Key:'.$account)
        );
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
        $response = json_decode($returnQueryString,true);
        if(isset($response['reason']) && !empty($response['reason'])){
            return $response['reason'];
        }else{
            return $errorDesc = "Errors";
        }
    }

    public function isSuccess($returnQueryString) {
        $response = json_decode($returnQueryString,true);
        $this->utils->debug_log("===============sms response buka ", $response);
        if(isset($response['status']) && $response['status'] == self::SUCCESS_CODE){
            return true;
        }else{
            return false;
        }
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_buka_'.$name);
    }
}
