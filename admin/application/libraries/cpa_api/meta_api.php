<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Meta api
 * OGP-34199
 * doc: https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api
 *
 * @version		1.0.0
 */

Class Meta_api extends Abstract_tracking_api {

    public $pixel_id;
    public $access_token;
    public $_trackingInfo;

    public function init()
    {
        $this->CI->load->model(array('player_login_report', 'player', 'third_party_login', 'payment_account', 'tracking_platform_model', 'player_trackingevent'));

        $this->_options = array_replace_recursive($this->_options, config_item('meta'));
        $this->pixel_id = '';
        $this->access_token = '';
        $this->_trackingInfo = [];

        $this->post_content = [
            "data" => [[
                "action_source"     => "", 
                "event_source_url"  => "", 
                "event_name"        => "",
                "event_time"        => "",
                "user_data"         => [
                    // "em" => "", // email
                    // "ph" => "", // phone
                    // "ge" => "", // gender
                    // "db" => "", // date of birth
                    // "ln" => "", // last name
                    // "fn" => "", // first name
                    // "ct" => "", // city
                    // "st" => "", // state
                    // "zp" => "", // zip
                    // "country" => "", // country
                    // "external_id" => "", // external id
                    "client_user_agent" => "", 
                    "client_ip_address" => "",
                ],
            ]]
        ];
    }

    public function generatePostContent($platform_setting, $tracking_extra_info, $player_id = null)
    {
        $tracking_extra_info = $this->CI->tracking_platform_model->getTrackingPlatformByPlatformType(Tracking_platform_model::PLATFORM_TYPE_META);
        $this->utils->debug_log('============generatePostContent tracking_extra_info============ ', $tracking_extra_info);

        // $this->pixel_id = $tracking_extra_info['trackingId'];
        // $this->access_token = $tracking_extra_info['token'];

        if(!empty($tracking_extra_info)){
            foreach ($tracking_extra_info as $key => $value) {
                $this->_trackingInfo[] = [
                    'pixel_id' => $value['trackingId'],
                    'access_token' => $value['token'],
                ];
            }
        }
    }

    public function triggerPlayerPostBack($eventName, $player, $platform_setting, $tracking_extra_info)
    {
        $this->platform_setting = $platform_setting;
		        
        $res = false;
        $this->player = $player ?: null;
        $this->generatePostContent($platform_setting, $tracking_extra_info);
        $this->utils->debug_log('============processTrackingCallback eventName============ ', $eventName);

        switch ($eventName) {
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM:
                $res = $this->regPostBack('', $player, $tracking_extra_info);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, 'Success', $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS:
                $walletAccount = $this->utils->safeGetArray($tracking_extra_info, 'walletAccount');
                $res = $this->withdrawalPostBack($tracking_extra_info, $player, 'Success', $walletAccount);
                break;
            default:
                // $res = $this->commonPostBack($eventName, $platform_setting, $tracking_extra_info, $player);
        }
        return $res;
    }

    public function regPostBack($clickId, $player, $extra_info = null)
    {
        if (!in_array('reg', $this->platform_setting['available_event'])) {
            return false;
        }

        $eventName = $this->getEventName('reg');
        if (empty($eventName)) {
            return false;
        }
        $_info = $this->CI->player_trackingevent->getNotifyBySourceAndNotify($player['playerId'], 50);
        $this->utils->debug_log('============regPostBack info============ ', $_info);
        $info = json_decode($_info[0]['params'], true);
        $info['id'] = $_info[0]['id'];

        $this->post_content['data'][0]['action_source']                  = 'website';
        $this->post_content['data'][0]['event_source_url']               = $info['source_url'];
        $this->post_content['data'][0]['event_name']                     = $eventName;
        $this->post_content['data'][0]['event_time']                     = strtotime(date('Y-m-d\TH:i:sP'));
        $this->post_content['data'][0]['user_data']['client_user_agent'] = $info['client_user_agent'];
        $this->post_content['data'][0]['user_data']['client_ip_address'] = $info['client_ip_address'];

        if($this->getOptions('for_test')){
            $this->post_content['test_event_code'] = $this->getOptions('test_event_code');
        }

        $post_back_data = $this->post_content;

        $this->utils->debug_log('============regPostBack post_back_data============ ', $post_back_data);

        $res = $this->doPost($post_back_data, $player);
        foreach($res as $val){
            if(json_decode($val['resp'],true)['events_received'] == 1){
                $this->CI->player_trackingevent->setIsNotify($player['playerId'], $info['id']);
            }
        }
        return $res;
    }

    public function everyTimeDepositPostBack($tracking_extra_info, $player, $status, $deposit_order = null)
    {
        $this->utils->debug_log(' ========================everyTimeDepositPostBack deposit_order:', $deposit_order);

        if (!in_array('dep', $this->platform_setting['available_event'])) {
            return false;
        }

        $eventName = $this->getEventName('dep');
        if (empty($eventName)) {
            return false;
        }

        $sale_order = $this->CI->sale_order->getSaleOrderById($deposit_order['id']);
        $this->utils->debug_log('============everyTimeDepositPostBack sale_order============ ', $sale_order);
        $_info = $this->CI->player_trackingevent->getNotifyBySourceAndNotify($player['playerId'], 20);
        $this->utils->debug_log('============everyTimeDepositPostBack info============ ', $_info);
        foreach($_info as $val){
            $params = json_decode($val['params'], true);
            if(isset($params['order_id']) && $params['order_id'] == $sale_order->id){
                $info = $params;
                $info['id'] = $val['id'];
            }
        }

        // main event
        $this->post_content['data'][0]['action_source']                  = 'website';
        $this->post_content['data'][0]['event_source_url']               = $info['source_url'];
        $this->post_content['data'][0]['event_name']                     = $eventName;
        $this->post_content['data'][0]['event_time']                     = strtotime(date('Y-m-d\TH:i:sP'));
        $this->post_content['data'][0]['user_data']['client_user_agent'] = $sale_order->browser_user_agent;
        $this->post_content['data'][0]['user_data']['client_ip_address'] = $sale_order->ip;
        $this->post_content['data'][0]['custom_data']['currency']        = $info['currency'];
        $this->post_content['data'][0]['custom_data']['value']           = $sale_order->amount;
        $this->post_content['data'][0]['custom_data']['order_id']        = $sale_order->secure_id;

        if($this->getOptions('for_test')){
            $this->post_content['test_event_code'] = $this->getOptions('test_event_code');
        }

        $post_back_data = $this->post_content;
        
        $this->utils->debug_log('============everyTimeDepositPostBack post_back_data============ ', $post_back_data);

        $res = $this->doPost($post_back_data, $player);
        foreach($res as $val){
            if(json_decode($val['resp'],true)['events_received'] == 1){
                $this->CI->player_trackingevent->setIsNotify($player['playerId'], $info['id']);
            }
        }
        return $res;
    }

    public function withdrawalPostBack($tracking_extra_info, $player, $status, $walletAccount = null)
    {
        $this->utils->debug_log(' ========================withdrawalPostBack walletAccount:', $walletAccount);

        if (!in_array('wit', $this->platform_setting['available_event'])) {
            return false;
        }

        $eventName = $this->getEventName('wit');
        if (empty($eventName)) {
            return false;
        }

        $wallet_account = $this->CI->wallet_model->getWalletAccountById($walletAccount['walletAccountId']);
        $this->utils->debug_log('============withdrawalPostBack wallet_account============ ', $wallet_account);
        $_info = $this->CI->player_trackingevent->getNotifyBySourceAndNotify($player['playerId'], 30);
        $this->utils->debug_log('============withdrawalPostBack info============ ', $_info);
        foreach($_info as $val){
            $params = json_decode($val['params'], true);
            if(isset($params['walletAccountId']) && $params['walletAccountId'] == $walletAccount['walletAccountId']){
                $info = $params;
                $info['id'] = $val['id'];
            }
        }

        // main event
        $this->post_content['data'][0]['action_source']                  = 'website';
        $this->post_content['data'][0]['event_source_url']               = $info['source_url'];
        $this->post_content['data'][0]['event_name']                     = $eventName;
        $this->post_content['data'][0]['event_time']                     = strtotime(date('Y-m-d\TH:i:sP'));
        $this->post_content['data'][0]['user_data']['client_user_agent'] = $wallet_account['browser_user_agent'];
        $this->post_content['data'][0]['user_data']['client_ip_address'] = $wallet_account['dwIp'];
        $this->post_content['data'][0]['custom_data']['currency']        = $info['currency'];
        $this->post_content['data'][0]['custom_data']['value']           = $wallet_account['amount'];
        $this->post_content['data'][0]['custom_data']['order_id']        = $wallet_account['transactionCode'];

        if($this->getOptions('for_test')){
            $this->post_content['test_event_code'] = $this->getOptions('test_event_code');
        }

        $post_back_data = $this->post_content;
        
        $this->utils->debug_log('============withdrawalPostBack post_back_data============ ', $post_back_data);

        $res = $this->doPost($post_back_data, $player);
        foreach($res as $val){
            if(json_decode($val['resp'],true)['events_received'] == 1){
                $this->CI->player_trackingevent->setIsNotify($player['playerId'], $info['id']);
            }
        }
        return $res;
    }

    private function getEventName($event)
    {
        $postback_setting =  $this->getOptions($event);
        $this->utils->debug_log(' ========================getEventName:', $postback_setting);
        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName');
            return $eventName;
        }

        return false;
    }

    public function doPost($post_back_data, $player = null, $deposit_order = null, $post_url = null)
    {
        $_post_back_data = json_encode($post_back_data, JSON_UNESCAPED_UNICODE);
        if (!$this->getOptions('isDebug')) {
            $this->_options['CURLOPT'][CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json',
            );
        }
        $post_url = $this->getApiURL();
        foreach($post_url as $url){
            $res[] = parent::doPost($_post_back_data, $player, $deposit_order, $url);
        }
        return $res;
    }

    protected function getApiURL()
    {
        $api_url = $this->getOptions('api_url');
        $url = [];
        foreach($this->_trackingInfo as $trackingInfo){
            $this->pixel_id = $trackingInfo['pixel_id'];
            $this->access_token = $trackingInfo['access_token'];
            $str = trim($this->pixel_id).'/events?access_token='.$this->access_token;
            $url[] = $api_url.$str;
        }
        $this->utils->debug_log('============getApiURL url============ ', $url);
        return $url;
    }

    public function checkApifields($platform_setting, $tracking_extra_info) {
        return $this->utils->safeGetArray($tracking_extra_info, 'pixel_id');
    }
}
