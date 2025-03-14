<?php
session_start();
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * linkmobility SMS API implementation
 * ttp://wsx.sp247.net/sms/send
 *
 * Config items:
 * * Sms_api_linkmobility_apikey
 */
class Sms_api_linkmobility extends Abstract_sms_api {
	const SUCCESS_CODE = "1005";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'https://wsx.sp247.net/sms/send';
    }

	protected function signContent($content) {
		return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
	}

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $url = $this->getUrl();
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_json = json_encode($fields);
        $fields['auth'] = $authorization = base64_encode($this->getParam('username').':'.$this->getParam('password'));;

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_json);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.$fields['auth']));
        $this->setCurlProxyOptions($handle);

        return $fields;
    }

	public function getFields($mobile, $content, $dialingCode) {
		if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
		$fields = array(
			"source"  			=> $this->CI->config->item('sms_from'),
			"destination"   	=> '+'.$dialingCode.$mobile,
			"userData"  		=> $content,
			"platformId" 		=> $this->getParam('platformId'),
	 		"platformPartnerId" => $this->getParam('platformPartnerId')
		);
		$this->utils->debug_log("=================================linkmobility SMS fields", $fields);
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
        $result = json_decode($returnQueryString, true);

        if (!is_array($result)) {
            return $returnQueryString;
        }

        if(array_key_exists('resultCode', $result)){
            $errorCode = $result['resultCode'];
            $errorDesc = $result['description'];
            $this->utils->error_log("===============linkmobility return [$errorCode]: $errorDesc", $returnQueryString);
            return $errorCode.": ".$errorDesc;
        }else{
            return 'Unknown Error';
        }
    }

    public function isSuccess($returnQueryString) {

        $result = json_decode($returnQueryString, true);
        $this->utils->debug_log("===============linkmobility SMS isSuccess returnQueryString", $result);

        if( isset($result['resultCode']) && $result['resultCode'] == self::SUCCESS_CODE)
            return true;
        else
            return false;
    }

	protected function getParam($name) {
		return $this->CI->config->item('sms_api_linkmobility_'.$name);
	}

}
