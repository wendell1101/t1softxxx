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

class Appsflyer_tracking_api extends Abstract_tracking_api
{
    public $appId;
    public $devKey;
    public $player;
    public $af_revenue;

    const EVENTNAME = 'eventName';

    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('appsflyer_tracking'));
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
        $this->platform_setting = [];

    }
    private function getEventName($event)
    {
        $postback_setting =  $this->getOptions($event);
        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName');
            return $eventName;
        }

        return false;
    }

    public function checkApifields($platform_setting, $tracking_extra_info) {
        return $this->utils->safeGetArray($tracking_extra_info, 'appsflyer_id') && $this->utils->safeGetArray($platform_setting, 'app_id', false) && $this->utils->safeGetArray($platform_setting, 'app_key');
    }
    
    public function generatePostContent($platform_setting, $tracking_extra_info, $player_id = null)
    {
        unset($this->post_content['eventValue']);
        unset($this->post_content['advertising_id']);
        unset($this->post_content['appsflyer_id']);
        // $deviceKeyName = $this->utils->safeGetArray($postback_setting, 'deviceKeyName', 'ga_id');
        $this->appId =  $this->utils->safeGetArray($platform_setting, 'app_id');
        $this->devKey = $this->utils->safeGetArray($platform_setting, 'app_key'); //$platform_setting['af_app_key'];
        $this->post_content['appsflyer_id'] = $this->utils->safeGetArray($tracking_extra_info, 'appsflyer_id'); // gaid
        if($this->utils->safeGetArray($tracking_extra_info, 'advertising_id')){
            $this->post_content['advertising_id'] = $this->utils->safeGetArray($tracking_extra_info, 'advertising_id');//$tracking_extra_info['advertising_id'];
        }
        if(!empty($this->player)) {
            // $this->post_content['customer_user_id'] = $this->utils->safeGetArray($this->player, 'playerId');
        }
    }

    /**
     * triggerPlayerPostBack function
     *
     * @param string $eventName
     * @param int $player
     * @param array $platform_setting
     * @param  $tracking_extra_info
     * @return mixed $res 
     */
    public function triggerPlayerPostBack($eventName, $player, $platform_setting, $tracking_extra_info)
    {
        $this->platform_setting = $platform_setting;
        
        $res = false;
        $this->player = $player ?: null;
        if(empty($this->utils->safeGetArray($tracking_extra_info, 'appsflyer_id', false))) {
            return false;
        }
        $this->generatePostContent($platform_setting, $tracking_extra_info);
        switch ($eventName) {
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN:
                $res = $this->loginPostBack($tracking_extra_info['appsflyer_id'], $player);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM:
                $res = $this->regPostBack($tracking_extra_info['appsflyer_id'], $player);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->depositPostBack($tracking_extra_info, $player, $deposit_order);
                $this->largeAmountDepositPostBack($tracking_extra_info, $player, $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
                $this->largeAmountDepositPostBack($tracking_extra_info, $player, $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW:
                $res = $this->pageViewPostBack($platform_setting, $tracking_extra_info);
                break;
            default:
                // $res = $this->commonPostBack($eventName, $platform_setting, $tracking_extra_info, $player);
        }
        return $res;
    }

    public function pageViewPostBack($platform_setting, $tracking_extra_info)
    {
        // if(!$this->utils->safeGetArray($platform_setting['available_event'], 'page_view')){
        if (!in_array('page_view', $platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('page_view');
        if (empty($eventName)) {
            return false;
        }
        $this->post_content[self::EVENTNAME] = $eventName;

        $this->generatePostContent($platform_setting, $tracking_extra_info);
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, null);
    }

    public function loginPostBack($clickId, $player, $extra_info = null)
    {

        if (!in_array('login', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('login');
        if (empty($eventName)) {
            return false;
        }
        $this->post_content[self::EVENTNAME] = $eventName;
        $eventValue = array();
        $eventValue['af_registration_method'] = 'mobile';
        $this->post_content['eventValue'] = json_encode($eventValue, JSON_UNESCAPED_UNICODE);
        $post_back_data = $this->post_content;

        return $this->doPost($post_back_data, $player);
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
        $this->post_content[self::EVENTNAME] = $eventName;
        $eventValue = array();
        $eventValue['af_registration_method'] = 'mobile';
        $this->post_content['eventValue'] = json_encode($eventValue, JSON_UNESCAPED_UNICODE);
        $post_back_data = $this->post_content;

        return $this->doPost($post_back_data, $player);
    }

    public function createDepositOrderPostBack($tracking_extra_info, $player, $deposit_order = null)
    {
        $this->utils->debug_log(' ========================createDepositOrderPostBack deposit_order:', $deposit_order);
        if (!in_array('ftd', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('crdep');
        if (empty($eventName)) {
            return $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
        }

        $this->post_content[self::EVENTNAME] = $eventName;

        if ($deposit_order) {

            $revenue = $deposit_order['amount'];
            $this->af_revenue['af_revenue'] = $revenue;
            $this->af_revenue['af_currency'] = $this->utils->safeGetArray($this->platform_setting, 'currency', 'BRL');
            $this->af_revenue['af_quantity'] = 1;
            $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
    }

    public function depositPostBack($tracking_extra_info, $player, $deposit_order = null)
    {
        $this->utils->debug_log(' ========================depositPostBack deposit_order:', $deposit_order);
        if (!in_array('ftd', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('ftd');
        if (empty($eventName)) {
            return $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
        }

        $this->post_content[self::EVENTNAME] = $eventName;

        if ($deposit_order) {

            $revenue = $deposit_order['amount'];
            $this->af_revenue['af_revenue'] = $revenue;
            $this->af_revenue['af_currency'] = $this->utils->safeGetArray($this->platform_setting, 'currency', 'BRL');
            $this->af_revenue['af_quantity'] = 1;
            // $this->af_revenue['user_id'] = $player['playerId'];
            $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
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

        $this->post_content[self::EVENTNAME] = $eventName;

        if ($deposit_order) {

            $revenue = $deposit_order['amount'];
            $this->af_revenue['af_revenue'] = $revenue;
            $this->af_revenue['af_currency'] = $this->utils->safeGetArray($this->platform_setting, 'currency', 'BRL');
            $this->af_revenue['af_quantity'] = 1;
            // $this->af_revenue['user_id'] = $player['playerId'];
            $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
    }

    public function largeAmountDepositPostBack($tracking_extra_info, $player, $deposit_order = null) {
        $this->utils->debug_log(' ========================largeAmountDepositPostBack deposit_order:', $deposit_order);

        if (!in_array('large_amount', $this->platform_setting['available_event'])) {
            $this->utils->debug_log(' ========================largeAmountDepositPostBack deposit_order:', $deposit_order);
            return false;
        }
        $eventName = $this->getEventName('large_amount');
        if (empty($eventName)) {
            return false;
        }

        $threshold = $this->utils->safeGetArray($this->platform_setting, 'large_amount_threshold', false);
        if(empty($threshold)){
            return false;
        }
        
        $this->post_content[self::EVENTNAME] = $eventName;

        if ($deposit_order) {
            $revenue = $deposit_order['amount'];
            $this->af_revenue['af_revenue'] = $revenue;
            $this->af_revenue['af_currency'] = $this->utils->safeGetArray($this->platform_setting, 'currency', 'BRL');
            $this->af_revenue['af_quantity'] = 1;
            // $this->af_revenue['user_id'] = $player['playerId'];
            $this->post_content['eventValue'] = json_encode($this->af_revenue, JSON_UNESCAPED_UNICODE);

            if ($revenue < $threshold) {
                return false;
            }
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
    }


    public function withdrawalSuccessPostBack($cpaInfo, $player, $status, $withdrawal_order = null){}

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
