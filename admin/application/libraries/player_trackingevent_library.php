<?php
/**
 * player_trackingevent_library.php
 *
 * @author Elvis Chen
 *
 * @property BaseController $CI
 * @property CI_loader $load
 * @property Player_trackingevent $player_trackingevent
 * @property Utils $utils
 */
class Player_trackingevent_library {
    /* @var BaseController */
    public $CI;

    public function __construct(){
        $this->CI =& get_instance();
        $this->load = $this->CI->load;
        $this->utils = $this->CI->utils;
        $this->load->model(['player_trackingevent']);

        $this->player_trackingevent = $this->CI->player_trackingevent;
    }

    public function getNotify($player_id){
        $self = $this;

        $date_from = "";
        $date_to = "";

        $default_notify_period = $this->CI->utils->getConfig('default_notify_period');
        
        if($default_notify_period){
            $default_notify_period = is_numeric($default_notify_period) ? $default_notify_period : intval($default_notify_period);
            $date_from = $this->utils->formatDateTimeForMysql(new DateTime('-'.$default_notify_period.' days'));
            $date_to = $this->utils->formatDateTimeForMysql(new DateTime());
        }

        $player_notify_list = $this->player_trackingevent->getNotify($player_id, $date_from, $date_to);
        if(empty($player_notify_list)){
            return [];
        }

        foreach($player_notify_list as $notify_id => $notify_content){

        }

        return $player_notify_list;
    }

    public function setIsNotify($player_id, $notify_id){
        return $this->player_trackingevent->setIsNotify($player_id, $notify_id);
    }

    public function createNotify($player_id, $source_type, $params = array()){
        $self = $this;

        if(!$this->CI->utils->getConfig('enable_player_action_trackingevent_system') && !$this->CI->utils->getConfig('enable_player_action_trackingevent_system_by_s2s')){
            return FALSE;
        }

        $allowed_source_type = $this->CI->utils->getConfig('allowed_trackingevent_source_type');
        if(empty($allowed_source_type) && !is_array($allowed_source_type)){
            return FALSE;
        }

        if(!in_array((string)$source_type, (array)$allowed_source_type)){
            return FALSE;
        }

        // $params = json_encode($params);
        return $self->player_trackingevent->createNotify($player_id, $source_type, $params);
    }

    public function approveSaleOrder($player_id, $params = array()){
        $this->CI->load->model(['transactions']);
        $source_type = 'TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS';
        $is_first_deposit = $this->CI->transactions->isOnlyFirstDeposit($player_id);
        if ($is_first_deposit) {
            $source_type = 'TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS';
        }
        return $this->createNotify($player_id, $source_type, $params);
    }

    public function delineSaleOrder($player_id, $params = array()){
        $this->CI->load->model(['transactions']);
        $source_type = 'TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED';
        return $this->createNotify($player_id, $source_type, $params);
    }

    /**
     * generateTrafficToken function
     *
     * @param array $params
     * @return string
     */
    public function generateTrackingToken($params) {
        $recid = $this->utils->safeGetArray($params, 'recid');
        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return false;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return false;
        }
        $json_str = json_encode($params);
        return sha1($json_str);
    }

    public function getTrackingInfoByToken($token) {
        $trackingInfo = $this->player_trackingevent->getTrackingInfoByToken($token);
        if(empty($trackingInfo)){
            return false;
        }
        $platform_id = $trackingInfo['platform_id'];
        
        $platformSetting = $this->getPlatformSetting($platform_id);
        return $platformSetting;
    }

    public function getTrackingInfoByPlayerId($player_id) {
        return $this->player_trackingevent->getTrackingInfoByPlayerId($player_id);
    }

    public function updateTrackingInfo($player_id = null, $token) {
		$this->utils->debug_log('============updateTrackingInfo============', $player_id, $token);

        $trackingInfo = $this->player_trackingevent->getTrackingInfoByToken($token);
        if(empty($trackingInfo)){
            return false;
        }
        if($player_id) {

            $this->player_trackingevent->updateTrackingInfo($player_id, $token);
        }
    }

    public function checkApifields($recid, $tracking_extra_info) {
        $haskey = false;
        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
		// $result_postBack = $trackApi->pageViewPostBack($platform_setting, $tracking_extra_info);
        if(method_exists($trackApi,'checkApifields')){
            $haskey = $trackApi->checkApifields($platform_setting, $tracking_extra_info);
        }
        return $haskey;
    }

    public function processPageView($recid, $tracking_extra_info) {
		// $this->load->model('player_trackingevent');
		// $trackingToken = $this->input->cookie('_og_tracking_token');
        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW;
		$platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
		// $result_postBack = $trackApi->pageViewPostBack($platform_setting, $tracking_extra_info);
		$result_postBack = $trackApi->triggerPlayerPostBack($eventCode, null, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processPageView============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = $tracking_extra_info['token'];
        if($result_postBack) {
            $this->insertPostbackReport(Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW, $recid, null, $record_extra_info, $result_postBack);
            return true;
        }
        return false;
		// redirect($this->utils->getSystemUrl('www'));
	}

    public function processLogin($recid, $tracking_info, $playerId, $player) {
		// $this->load->model('player_trackingevent');
		// $trackingToken = $this->input->cookie('_og_tracking_token');
        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN;
		$platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        // if($this->player_trackingevent->checkRecordExist($eventCode, null, $playerId)){
        //     return;
        // }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
		$result_postBack = $trackApi->triggerPlayerPostBack($eventCode, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processRegSuccess============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $this->insertPostbackReport($eventCode, $recid, $playerId, $record_extra_info, $result_postBack);
	}


    public function processRegSuccess($recid, $tracking_info, $playerId, $player) {

        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM;
		$platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        if($this->player_trackingevent->checkRecordExist($eventCode, null, $playerId)){
            return;
        }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
		$result_postBack = $trackApi->triggerPlayerPostBack($eventCode, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processRegSuccess============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $this->insertPostbackReport($eventCode, $recid, $playerId, $record_extra_info, $result_postBack);
	}

    public function processPaymentSuccess($recid, $type, $tracking_info, $playerId, $player, $saleOrder) {
        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        if($this->player_trackingevent->checkRecordExist($type, $saleOrder['secure_id'], $playerId)){
            return;
        }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
        $tracking_extra_info['deposit_order'] = $saleOrder;
		$result_postBack = $trackApi->triggerPlayerPostBack($type, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processPaymentSuccess============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $record_extra_info['deposit_order'] = $saleOrder;
        $this->insertPostbackReport($type, $recid, $playerId, $record_extra_info, $result_postBack, $saleOrder['secure_id']);
    }


    public function processPaymentFailed($recid, $type, $tracking_info, $playerId, $player, $saleOrder) {
		$this->utils->debug_log('============result_processPaymentFailed============');

        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        if($this->player_trackingevent->checkRecordExist($type, $saleOrder['secure_id'], $playerId)){
            return;
        }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
        $tracking_extra_info['deposit_order'] = $saleOrder;
		$result_postBack = $trackApi->triggerPlayerPostBack($type, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processPaymentFailed============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $record_extra_info['deposit_order'] = $saleOrder;
        $this->insertPostbackReport($type, $recid, $playerId, $record_extra_info, $result_postBack, $saleOrder['secure_id']);
    }

    public function processWithdrawalSuccess($recid, $type, $tracking_info, $playerId, $player, $walletAccount) {
        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        if($this->player_trackingevent->checkRecordExist($type, $walletAccount['transactionCode'], $playerId)){
            return;
        }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
        $tracking_extra_info['walletAccount'] = $walletAccount;
		$result_postBack = $trackApi->triggerPlayerPostBack($type, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processWithdrawalSuccess============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $record_extra_info['walletAccount'] = $walletAccount;
        $this->insertPostbackReport($type, $recid, $playerId, $record_extra_info, $result_postBack, $walletAccount['transactionCode']);
    }

    public function processWithdrawalFailed($recid, $type, $tracking_info, $playerId, $player, $walletAccount) {
		$this->utils->debug_log('============processWithdrawalFailed============');

        $platform_setting = $this->getPlatformSetting($recid);
		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);
        if(empty($trackApi)) {
            return;
        }
        if($this->player_trackingevent->checkRecordExist($type, $walletAccount['transactionCode'], $playerId)){
            return;
        }
        $tracking_extra_info = !empty($tracking_info) ? json_decode($tracking_info['extra_info'], true) : null;
        $tracking_extra_info['walletAccount'] = $walletAccount;
		$result_postBack = $trackApi->triggerPlayerPostBack($type, $player, $platform_setting, $tracking_extra_info);
		$this->utils->debug_log('============result_processWithdrawalFailed============', $result_postBack);
        $record_extra_info = [];
        $record_extra_info['token'] = !empty($tracking_info) ? $tracking_info['token'] : null;
        $record_extra_info['walletAccount'] = $walletAccount;
        $this->insertPostbackReport($type, $recid, $playerId, $record_extra_info, $result_postBack, $walletAccount['transactionCode']);
    }

    public function processSentMessage ($recid, $params, $playerId, $player) {
        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS;
		$platform_setting = $this->getPlatformSetting($recid);

		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);

        if(empty($trackApi)) {
            return;
        }

		$result_postBack = $trackApi->triggerPlayerPostBack($eventCode, $player, $platform_setting, $params);
		$this->utils->debug_log('============result_processSentMessage============', $result_postBack);
       
        $this->insertPostbackReport($eventCode, $recid, $playerId, $params, $result_postBack);
        $source_type = 'TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS';
        return $this->createNotify($playerId, $source_type, $params);
    }

    public function processAddAnnouncementMessage ($recid, $params, $adminUserId, $adminUser) {
        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_ADD_ANNOUNCEMENT_MESSAGE;
		$platform_setting = $this->getPlatformSetting($recid);

		if(!$platform_setting) {
			return;
		}
		$platform = $this->utils->safeGetArray($platform_setting, 'platform');
        $trackApi = $this->getTrackApi($platform);

        if(empty($trackApi)) {
            return;
        }

		$result_postBack = $trackApi->triggerPlayerPostBack($eventCode, $adminUser, $platform_setting, $params);
		$this->utils->debug_log('============result_processAddAnnouncementMessage============', $result_postBack);
       
        $this->insertPostbackReport($eventCode, $recid, $adminUserId, $params, $result_postBack);
    }
    
    public function insertPostbackReport($eventCode, $recid, $playerId=null, $record_extra_info, $result_postBack, $external_id=null)
    {   
        $this->player_trackingevent->insertReportRecord($eventCode, $recid, $playerId, $record_extra_info, array('contetn'=> $result_postBack), $external_id);
    }
    public function getPlatformSetting($recid, $current_event = null){
        $third_party_tracking_platform_list = $this->utils->getConfig('third_party_tracking_platform_list');

        if(empty($third_party_tracking_platform_list)){
            return false;
        }

        $settings = $this->utils->safeGetArray($third_party_tracking_platform_list, $recid);
        return $settings;
    }

    public function getTrackApi($platform){
        if(!$this->utils->getConfig($platform)){
            return;
        }
        $apiName = $platform.'_api';
		$classExists = file_exists(strtolower(APPPATH . 'libraries/cpa_api/' . $apiName . ".php"));
		if(!$classExists){
			return;
		}
		$this->load->library('cpa_api/'.$apiName);

        if(class_exists($apiName, false)){
            $trackApi=new $apiName();
        }else{
            //load failed
            $this->utils->error_log('load class file failed', $apiName);
        }

		// $trackApi = $this->CI->$apiName;
        return $trackApi;
    }
    
}