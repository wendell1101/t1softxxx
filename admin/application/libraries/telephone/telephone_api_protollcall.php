<?php
require_once dirname(__FILE__) . '/abstract_telephone_api.php';

/**
 *
 * PROTOLLCALL_TELEPHONE_API, ID: 6307
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://pbx-a06-webapi.protollcall.com:4433/pbxws.php
 * * Extra Info
 * > {
    * >    
 * > }
 *
 *
 * @category Telephone
 * @copyright 2013-2022 tot
 */
class Telephone_api_protollcall extends Abstract_telephone_api {
    const RETURN_SUCCESS = 'Success';

    public function getPlatformCode() {
        return PROTOLLCALL_TELEPHONE_API;
    }


    public function getCallUrl($phoneNumber, $callerId) {
        $country_code=!empty($this->getSystemInfo('country_code') || $this->getSystemInfo('country_code')=='0')?$this->getSystemInfo('country_code'):'+66';

        $getSystemInfo = $this->getSystemInfo('protollcall_map_teleId_items');
        $callerId=$this->checkAdminTele($this->getPlatformCode(),$getSystemInfo);

        $params = array(
            'ext' => $callerId,
            'tel'=>$country_code.$phoneNumber
        );
        return $this->processTeleUrlForm($params);
    }

    private function processTeleUrlForm($params) {
        $url = $this->getSystemInfo('url');
        $result_content = $this->submitPostForm($url, $params, true,$params['tel']);
        return $result_content;
    }

    public function decodeResult($result_array) {

        $decode_content = json_decode($result_array, true);

        $result = array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_POST_RESULT,
            'msg' => 'Failed for unknown reason.'
        );
        if(!$decode_content['success']) {
            $result['msg'] = $decode_content['msg'];
            $this->utils->debug_log("============protollcall decodeResult error", $decode_content['msg']);
        }
        elseif($decode_content['success']) {
            $result['success'] = true;
            $result['msg'] = 'API success  protollcall:'.$decode_content['msg'].'Wait for connecting...';
        }
        return $result;
    }

    public function basic_token()
    {
        $password = $this->getSystemInfo('key');
        $username = $this->getSystemInfo('account');
        $token = base64_encode("$username:$password");
        $headers = [
            'Authorization: Basic ' . $token,
            'Content-Type: application/json',
        ];
        return $headers;
    }

}
