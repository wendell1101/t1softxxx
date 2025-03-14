<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * kmicloud SMS API implementation
 * http://47.112.240.3/web/#/1?page_id=3
 *
 * Config items:
 * * sms_api_kmicloud_accessKey
 * * sms_api_kmicloud_secretKey
 */
class Sms_api_kmicloud extends Abstract_sms_api {
    const SUCCESS_CODE = 200;

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'http://api.kmicloud.com/sms/send/v1/otp';
    }

    public function getFields($mobile, $content, $dialingCode) {
        $fields = array(
            'accessKey'     => $this->getParam('accessKey'),
            'secretKey' => $this->getParam('secretKey'),
            'to'       => '00'.$dialingCode.$mobile,
            'message'      => $content
        );
        $this->utils->debug_log("===============kmicloud SMS fields", $fields);
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
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
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
        $resp = json_decode($returnQueryString, true);
        $this->utils->debug_log("===============kmicloud SMS getErrorMsg returnQueryString", $returnQueryString);
        $this->utils->debug_log("===============kmicloud SMS getErrorMsg resp", $resp);

        if($resp){
            $err_msg = $resp['code'] .': '. $resp['message'];
        }
        else{
            $err_msg = $returnQueryString;
        }
        return $err_msg;
    }

    public function isSuccess($returnQueryString) {
        $resp = json_decode($returnQueryString, true);
        if(isset($resp['code']) && $resp['code'] == self::SUCCESS_CODE){
            return true;
        }
        return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_kmicloud_'.$name);
    }

}
