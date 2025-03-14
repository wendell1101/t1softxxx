<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * haosms SMS API implementation
 * http://www.haosms.net/client/extra_api/send_msg
 *
 * Config items:
 * * api_key
 * * phones
 * * content
 */
class Sms_api_haosms extends Abstract_sms_api {

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'http://www.haosms.net/client/extra_api/otp_msg';
    }

    public function send($mobile, $content, $dialingCode = NULL){
        $this->utils->debug_log("===============sms send", $mobile);
        $ch = curl_init();
        $fields      = $this->configCurl($ch, $mobile, $content, $dialingCode);
        $content     = curl_exec($ch);
        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

    public function getFields($mobile, $content, $dialingCode) {

        $platform = (!empty($this->getParam('api_platform')))? $this->getParam('api_platform'):'中国OTP通道1';

        $fields = array(
            'api_key' => $this->getParam('api_key'),
            'phone' => $mobile,
            'content' => $content,
            "platform" => $platform,
            'tmp_id' => $this->getParam('tmp_id')
        );

        $this->utils->debug_log("===============haosms SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $params = http_build_query($fields);
        curl_setopt($handle, CURLOPT_URL, $url.'?'.$params);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);

        $this->utils->debug_log("===============haosms SMS get url", $url.'?'.$params);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        $resp = json_decode($returnQueryString, true);
        $this->utils->debug_log("===============haosms SMS getErrorMsg resp", $resp);
        return $resp;

    }

    public function isSuccess($returnQueryString) {
        $resp = json_decode($returnQueryString, true);
        $this->utils->debug_log("=================================haosms SMS isSuccess resp", $resp);
        if(is_null($resp) or !is_array($resp)) {
            return false;
        }
        if (array_key_exists("result",$resp) && $resp["result"] != "False") {
            return $resp['result'];
        }
       return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_haosms_'.$name);
    }

}
