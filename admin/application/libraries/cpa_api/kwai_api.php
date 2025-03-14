<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Kwai api
 * OGP-29511
 * doc: https://docs.qingque.cn/d/home/eZQB6la5gGLdpSGexeOlXGFVk?identityId=1pqcPddtdb9
 *
 *
 * @version		1.0.0
 */

class Kwai_api extends Abstract_tracking_api
{
    const TESTFLAG = FALSE;
    const TRACKFLAG = FALSE;
    const DEFAULT_ATTR = 1;
    const MMPCODE = 'PL';
    const VERSION = '9.9.9';

    // const TESTFLAG = TRUE;
    /**
     * Summary of init
     * @return void
     */
    public function init()
    {
        $this->_options = array_replace_recursive($this->_options, config_item('kwai'));
        $this->post_content = [
            'clickid' => '',
            'event_name' => '',
            'pixelId' => '',
            'access_token' => '',
            'testFlag' => empty($this->_options['TESTFLAG'])? self::TESTFLAG : $this->_options['TESTFLAG'],
            'trackFlag' => empty($this->_options['TRACKFLAG'])? self::TRACKFLAG : $this->_options['TRACKFLAG'],
            'is_attributed' => empty($this->_options['DEFAULT_ATTR'])? self::DEFAULT_ATTR : $this->_options['DEFAULT_ATTR'],
            'mmpcode' => empty($this->_options['MMPCODE'])? self::MMPCODE : $this->_options['MMPCODE'],
            'pixelSdkVersion' => empty($this->_options['VERSION'])? self::VERSION : $this->_options['VERSION'],
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
        return $this->utils->safeGetArray($tracking_extra_info, 'click_id') && $this->utils->safeGetArray($tracking_extra_info, 'pixel_id', false) && $this->utils->safeGetArray($platform_setting, 'api_key');
    }

    public function generatePostContent($platform_setting, $tracking_extra_info, $player_id = null)
    {
        $this->post_content['clickid'] = $this->utils->safeGetArray($tracking_extra_info, 'click_id'); // gaid
        $this->post_content['pixelId'] = $this->utils->safeGetArray($tracking_extra_info, 'pixel_id');
        $this->post_content['access_token'] = $this->utils->safeGetArray($platform_setting, 'api_key');
        unset($this->post_content['properties']);
    }

    public function triggerPlayerPostBack($eventName, $player, $platform_setting, $tracking_extra_info)
    {
        $this->platform_setting = $platform_setting;
        
        $res = false;
        if(empty($this->utils->safeGetArray($tracking_extra_info, 'click_id', false))) {
            return false;
        }
        $this->generatePostContent($platform_setting, $tracking_extra_info);
        switch ($eventName) {
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM:
                $res = $this->regPostBack($tracking_extra_info['click_id'], $player);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->depositPostBack($tracking_extra_info, $player, $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW:
                $res = $this->pageViewPostBack($platform_setting, $tracking_extra_info);
                break;
            default:
                // $res = $this->commonPostBack($eventName, $platform_setting, $tracking_extra_info, $player);
        }
        return $res;
    }

    public function commonPostBack($event_name, $platform_setting, $tracking_extra_info, $player = null)
    {
        $eventName = $this->getEventName($event_name);
        if (empty($eventName)) {
            return false;
        }
        $this->post_content['event_name'] = $eventName;

        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player);
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
        $this->post_content['event_name'] = $eventName;

        $this->generatePostContent($platform_setting, $tracking_extra_info);
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, null);
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
        $this->post_content['event_name'] = $eventName;

        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player);
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

        $this->post_content['event_name'] = $eventName;

        $properties = array(
            // 'currency' => $this->utils->safeGetArray($tracking_extra_info, 'currency', 'BRL'),
            'price' => $deposit_order['amount']
        );

        $this->post_content['properties'] = json_encode($properties, JSON_UNESCAPED_UNICODE);
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

        $this->post_content['event_name'] = $eventName;

        $properties = array(
            // 'currency' => $this->utils->safeGetArray($tracking_extra_info, 'currency', 'BRL'),
            'price' => $deposit_order['amount']
        );

        $this->post_content['properties'] = json_encode($properties, JSON_UNESCAPED_UNICODE);
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
    }


    public function doPost($post_back_data, $player = null, $deposit_order = null)
    {
        $_post_back_data = json_encode($post_back_data, JSON_UNESCAPED_UNICODE);
        if (!$this->getOptions('isDebug')) {
            $this->_options['CURLOPT'][CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json',
                'accept: application/json',
                'charset: utf-8'
            );
        }
        $result = parent::doPost($_post_back_data, $player, $deposit_order);
        $result['post_data'] = $_post_back_data;
        return $result;
    }
}
