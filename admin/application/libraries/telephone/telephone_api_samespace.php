<?php
require_once dirname(__FILE__) . '/abstract_telephone_api.php';

/**
 * samespace 国信呼叫中心
 * http://www.telcrm.cn
 *
 * SAMESPACE_TELEPHONE_API, ID: 6161
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://ph1c.samespace.com/apiv1/make_call
 * * Extra Info
 * > {
 * >    "samespace_default_caller" : "817",
 * >    "samespace_country_code" : ""
 * > }
 *
 *
 * @category Telephone
 * @copyright 2013-2022 tot
 */
class Telephone_api_samespace extends Abstract_telephone_api {

    public function getPlatformCode() {
        return SAMESPACE_TELEPHONE_API;
    }

    public function getCallUrl($phoneNumber, $callerId) {
        if(empty($callerId)) {
            $callerId = $this->getSystemInfo('samespace_default_caller');
        }
        $getSystemInfo = $this->getSystemInfo('samespace_map_teleId_items');
        $callerId=$this->checkAdminTele($this->getPlatformCode(),$getSystemInfo);

        $country_code = $this->getSystemInfo('samespace_country_code', '84');
        $cloud_id = $this->getSystemInfo('cloud_id');
        $params = array(
            'username' => $callerId.'@'.$cloud_id,
            'number' => $country_code.$phoneNumber,
        );

        $this->utils->debug_log("==============samespace getCallUrl params generated: ", $params);

        return $this->processTeleUrlForm($params);
    }

    private function processTeleUrlForm($params) {
        $url = $this->getSystemInfo('url');
        $this->utils->debug_log("==============samespace processTeleUrlForm Call URL", $url);
        $result_content = $this->submitPostForm($url, $params, true, $params['number']);

        return $result_content;
    }

    public function decodeResult($resultString) {
        $decode_content = json_decode($resultString, true);
        $result = array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_POST_RESULT,
            'msg' => 'Failed for unknown reason.'
        );
        $this->utils->debug_log("============xinhongtone processTeleUrlForm decodeResult", $decode_content);
        if($decode_content['success']) {
                $result['success'] = true;
                $result['msg'] = 'API success Wait for connecting...';
            }
            else if(isset($decode_content['message'])) {
                $result['msg'] = 'API failed reason: '.$decode_content['message'];
            }

        return $result;
    }

}
