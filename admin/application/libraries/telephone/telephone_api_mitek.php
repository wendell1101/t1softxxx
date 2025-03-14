<?php
require_once dirname(__FILE__) . '/abstract_telephone_api.php';

/**
 *
 * MITEK_TELEPHONE_API, ID: 6173
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: http://203.82.36.236:12121/bridge/callctrl
 * * Extra Info
 * > {
 * >    "mitek_default_caller" : "817",
 * >    "mitek_country_code" : ""
 * > }
 *
 *
 * @category Telephone
 * @copyright 2013-2022 tot
 */
class Telephone_api_mitek extends Abstract_telephone_api {
    const RETURN_SUCCESS = 'Success';

    public function getPlatformCode() {
        return MITEK_TELEPHONE_API;
    }

    public function getCallUrl($phoneNumber, $callerId) {
        if(empty($callerId)) {
            $callerId = $this->getSystemInfo('mitek_default_caller');
        }

        $getSystemInfo = $this->getSystemInfo('mitek_map_teleId_items');
        $callerId=$this->checkAdminTele($this->getPlatformCode(),$getSystemInfo);
        $country_code = $this->getSystemInfo('mitek_country_code', '84');
        $secret = $this->getSystemInfo('mitek_default_secret', '6ef82ba36589c86d1f5a0387eaff2f77');
        $params = array(
            'secret' => $secret,
            'extension' => $callerId,
            'phone' => $country_code.$phoneNumber,
        );

        $this->utils->debug_log("==============mitek getCallUrl params generated: ", $params);

        return $this->processTeleUrlForm($params);
    }

    private function processTeleUrlForm($params) {
        $url = $this->getSystemInfo('url');
        $this->utils->debug_log("==============mitek processTeleUrlForm Call URL", $url);
        $result_content = $this->submitGetForm($url, $params, false, $params['phone']);
        return $result_content;
    }

    public function decodeResult($resultString) {
        $decode_content = json_decode($resultString, true);
        $result = array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_POST_RESULT,
            'msg' => 'Failed for unknown reason.'
        );
        $this->utils->debug_log("============mitek processTeleUrlForm decodeResult", $decode_content);
        if(!isset($decode_content['response'])) {
            $result['msg'] = "API response doesn't contain the key 'ResultCode'.";
        }
        else {
            if($decode_content['response'] == self::RETURN_SUCCESS) {
                $result['success'] = true;
                $result['msg'] = 'API success ResultCode - ['. $decode_content['message'] .']: Wait for connecting...';
            }
            else if(isset($decode_content['code'])) {
                $result['msg'] = 'API failed ResultCode - ['. $decode_content['code'] .']: '.$decode_content['message'];
            }
        }

        return $result;
    }
}
