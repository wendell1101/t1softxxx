<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * thaibulkssms SMS API implementation
 * http://thaibulksms.com/sms_api.php
 *
 * Config items:
 * * username
 * * password
 * * msisdn
 * * message
 * * ScheduledDelivery
 */
class Sms_api_thaibulksms extends Abstract_sms_api {
    const SUCCESS_CODE = "1";

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : 'http://thaibulksms.com/sms_api.php';
    }

    public function getFields($mobile, $content, $dialingCode) {
        $fields = array(
            'username' => $this->getParam('username'),
            'password' => $this->getParam('password'),
            'msisdn'   => $mobile,
            'message'  => $content,
        );
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        $resp = $this->loadXmlResp($returnQueryString);
        $this->utils->debug_log("=================================thaibulksms SMS getErrorMsg", $resp['Detail']);
        if(array_key_exists('Detail', $resp)) {
            return $resp['Detail'];
        }
        return $resp['Status'];
    }

    public function isSuccess($returnQueryString) {
        $resp = $this->loadXmlResp($returnQueryString);
        $this->utils->debug_log("=================================thaibulksms SMS isSuccess resp", $resp);
        if($resp['Status'] == self::SUCCESS_CODE) {
            return true;
        }else {
            return false;
        }
    }

    public function loadXmlResp($returnQueryString) {
        $obj = simplexml_load_string($returnQueryString);
        $arr = $this->utils->xmlToArray($obj);
        return $arr['QUEUE'];
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_thaibulksms_'.$name);
    }
}
