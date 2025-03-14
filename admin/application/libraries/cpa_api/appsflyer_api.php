<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Adjust api
 * OGP-27040
 * doc: https://help.adjust.com/zh/article/s2s-api-reference
 *
 *
 * @version		1.0.0
 */

class Appsflyer_api extends Abstract_tracking_api
{
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('appsflyer'));
        $this->appId = '';
        $this->devKey = '';
        $this->player = false;
        $this->post_content = [
            'appsflyer_id' => '',
            'advertising_id' => '',
            'eventName' => '',
            'eventValue' => '',
        ];

        $this->af_revenue = [
            'af_revenue' => 0,
            'af_currency' => 'BRL'
        ];

        $this->_options['CURLOPT'] = [];
    }

    private function generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting)
    {
        // $deviceKeyName = $this->utils->safeGetArray($postback_setting, 'deviceKeyName', 'ga_id');
        $this->appId =  $this->utils->safeGetArray($sourceDetail, 'af_app_id');
        $this->devKey = $this->utils->safeGetArray($this->_options, $this->appId) ?: $this->utils->safeGetArray($sourceDetail, 'af_app_key'); //$sourceDetail['af_app_key'];
        $this->post_content['appsflyer_id'] = $clickId; // gaid
        $this->post_content['advertising_id'] = $this->utils->safeGetArray($sourceDetail, 'advertising_id');//$sourceDetail['advertising_id'];
        $this->post_content['eventName'] = $eventName;
        $this->post_content['customer_user_id'] = $this->utils->safeGetArray($this->player, 'playerId');
    }

    public function regPostBack($clickId, $player, $extra_info = null)
    {
        $type = $this->utils->safeGetArray($player, 'regSourceType', 'default_reg');
        $regEventMap = [
            'default_reg' => 'register_account',
            'fb_reg' => 'register_account_fb',
            'google_reg' => 'register_account_gg'
        ];

        $sourceDetail = [];
        if (!empty($_COOKIE['reg_track'])) {
            $reg_track_cookie = json_decode($_COOKIE['reg_track'], true);
            $sourceDetail = $reg_track_cookie;
        } else {
            $sourceDetail = $this->getAffSourceDetail($player);
            if(empty($sourceDetail)){
                return false;
            }
        }

        $this->utils->debug_log("============3rdparty_tracking_postback:[Appsflyer_api]============eventType: $type");
        $postback_setting =  $this->getOptions($type);

        if ($postback_setting) {
            // $eventName = isset($postback_setting['eventName']) ? $postback_setting['eventName']: $regEventMap[$type];
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName', $regEventMap[$type]);
            $this->generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting);
            $post_back_data = $this->post_content;
            return $this->doPost($post_back_data, $player);
        }
        return false;
    }

    public function createDepositOrderPostBack($cpaInfo, $player, $deposit_order = null)
    {
        $this->utils->debug_log("============3rdparty_tracking_first_deposit_postback:[Appsflyer_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  $this->getOptions('crdep');
        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName', 'recharge_order');
            $clickId = $sourceDetail["clickid"];
            $this->generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting);

            $post_back_data = $this->post_content;
            return $this->doPost($post_back_data, $player);
        }
        return false;
    }

    public function depositPostBack($cpaInfo, $player, $deposit_order = null)
    {
        $this->utils->debug_log("============3rdparty_tracking_depositPostBack:[Appsflyer_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  $this->getOptions('ftd');
        if($postback_setting){
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName', 'first_recharge_success');
            $clickId = $sourceDetail["clickid"];
            $this->generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting);

            if ($deposit_order) {

                $revenue = $deposit_order['amount'];
                $this->af_revenue['af_revenue'] = $revenue;
                $this->af_revenue['af_currency'] = $this->utils->safeGetArray($postback_setting, 'currency', 'BRL');
                $this->af_revenue['user_id'] = $player['playerId'];
                $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
            }
            $post_back_data = $this->post_content;
            return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        } else {

            return $this->everyTimeDepositPostBack($cpaInfo, $player, '',  $deposit_order);
        }
    }

    public function everyTimeDepositPostBack($cpaInfo, $player, $status, $deposit_order = null)
    {
        $this->utils->debug_log("============3rdparty_tracking_everyTimeDepositPostBack:[Appsflyer_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  $this->getOptions('dep');
        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName', 'recharge_success');
            $clickId = $sourceDetail["clickid"];
            $this->generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting);

            if ($deposit_order) {

                $revenue = $deposit_order['amount'];
                $this->af_revenue['af_revenue'] = $revenue;
                $this->af_revenue['af_currency'] = $this->utils->safeGetArray($postback_setting, 'currency', 'BRL');
                $this->af_revenue['user_id'] = $player['playerId'];
                $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
            }
            $post_back_data = $this->post_content;
            return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
        }
        return false;
    }
    public function withdrawalSuccessPostBack($cpaInfo, $player, $status, $withdrawal_order = null)
    {
        $this->utils->debug_log("============3rdparty_tracking_first_deposit_postback:[Appsflyer_api]============");
        $sourceDetail = $this->getAffSourceDetail($player);
        $postback_setting =  $this->getOptions('wd');
        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName', 'withdraw_success');
            $clickId = $sourceDetail["clickid"];
            $this->generatePostbackData($clickId, $eventName, $sourceDetail, $postback_setting);
            if($withdrawal_order){
                $revenue = $withdrawal_order['amount'];
                $this->af_revenue['af_revenue'] = $revenue;
                $this->af_revenue['af_currency'] = $this->utils->safeGetArray($postback_setting, 'currency', 'BRL');
                $this->af_revenue['user_id'] = $player['playerId'];
                $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
            }
            $post_back_data = $this->post_content;
            return $this->doPost($post_back_data, $player, $this->utils->safeGetArray($withdrawal_order, 'transactionCode'));
        }
        return false;
    }

    private function getAffSourceDetail($player)
    {
        $playerId = $player['playerId'];
        $this->CI->load->model(['player_model']);
        $playerInfo = $this->CI->player_model->getPlayerDetailArrayById($playerId);
        $this->player = $player;
        if (!empty($playerInfo['cpaId'])) {
            $aff_source_detail = json_decode($playerInfo['cpaId'], true);
            $this->utils->debug_log("============3rdparty_affiliate_network_detail============", $aff_source_detail);
            return $aff_source_detail;
        }
        return [];
    }

    public function doPost($post_back_data, $player = null, $deposit_order = null)
    {
        $_post_back_data = json_encode($post_back_data, JSON_UNESCAPED_UNICODE);
        if (!$this->getOptions('isDebug')) {
            $this->_options['CURLOPT'][CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json',
                'authentication: ' . $this->devKey,
                'Content-Length: ' . strlen($_post_back_data)
            );
        }
        return parent::doPost($_post_back_data, $player, $deposit_order);
    }

    protected function getApiURL()
    {
        $api_url = $this->getOptions('api_url');
        return $api_url . $this->appId;
    }
}
