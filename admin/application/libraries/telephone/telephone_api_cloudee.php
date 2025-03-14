<?php
require_once dirname(__FILE__) . '/abstract_telephone_api.php';

/**
 *
 * CLOUDEE_TELEPHONE_API, ID: 6307
 *
 * Required Fields:
 *
 * * URL
 * * Extra Info
 *
 * Field Values:
 *
 * * URL: https://cloud.sawasdeeshop.com/_o/v2/calls/outgoing
 * * Extra Info
 * > {
    * >    
 * > }
 *
 *
 * @category Telephone
 * @copyright 2013-2022 tot
 */
class Telephone_api_cloudee extends Abstract_telephone_api {
    const RETURN_SUCCESS = 'Success';

    public function getPlatformCode() {
        return CLOUDEE_TELEPHONE_API;
    }


    public function getCallUrl($phoneNumber, $callerId) {
        $country_code=!empty($this->getSystemInfo('country_code'))?$this->getSystemInfo('country_code'):'+66';
        $flow_uuid=$this->getSystemInfo('flow_uuid');

        $getSystemInfo = $this->getSystemInfo('cloudee_map_teleId_items');
        $callerId=$this->checkAdminTele($this->getPlatformCode(),$getSystemInfo);

        $params = array(
            'idempotencyKey' =>$this->idempotencyKey(),
            'flowUuid' => $flow_uuid,
            'destination' => $country_code.$phoneNumber,
            'ext' => $callerId,
            'callerId'=>$this->get_callId()
        );
        $this->utils->debug_log("==============cloudee getCallUrl params generated: ", $params);
        return $this->processTeleUrlForm($params);
    }

    private function processTeleUrlForm($params) {
        $url = $this->getSystemInfo('url');
        $apikey=$this->getSystemInfo('api_key');
        
        $queryString = http_build_query($apikey);
        $url=$url . '?' . $queryString;

        $this->utils->debug_log("==============cloudee processTeleUrlForm Call URL", $url);
        $result_content = $this->submitPostForm($url, $params, true, $params['destination']);
        return $result_content;
    }

    public function decodeResult($resultString) {
        if(!isset($resultString['statusCode'])){

            $decode_content = json_decode($resultString, true);
        }
        $result = array(
            'success' => false,
            'type' => self::REDIRECT_TYPE_POST_RESULT,
            'msg' => 'Failed for unknown reason.'
        );
        if(isset($decode_content['error'])) {
            $result['msg'] = $decode_content['error'];
            $this->utils->debug_log("============cloudee decodeResult error", $decode_content['error']);
        }
        elseif($resultString['statusCode']==200) {
            $result['success'] = true;
            $result['msg'] = 'API success  Wait for connecting...';
        }
        return $result;
    }

    private function idempotencyKey() {
        $time = time();
        $result = $time.'-'.uniqid();
        return $result;
    }

    private function get_callId() {
        $callid_array=$this->getSystemInfo('callId');
        if(!$callid_array||empty($callid_array)){
            $callid_array=array(
                "1"=>"6628215080",
                "2"=>"6628215241",
                "3"=>"6628215242",
                "4"=>"6628215243"
            );
        }
        $randomKey = array_rand($callid_array);
        $randomValue = $callid_array[$randomKey];
        return $randomValue;
    }
}
