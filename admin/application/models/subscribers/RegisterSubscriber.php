<?php

require_once dirname(__FILE__) . "/../events/RegisterEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegisterSubscriber extends AbstractSubscriber implements EventSubscriberInterface{
    const ACTION_REGISTERSUBSCRIBER_TITLE = 'Register Subscriber';
    const SUCCESS_CODE = '0';

    public function __construct(){
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_REGISTER_AFTER_DB_TRANS => 'afterRegisterDBTrans',
        );
    }

    public function afterRegisterDBTrans(RegisterEvent $event){

        $playerId=$event->getPlayerId();
        $player=null;
        $playerDetial=null;

        $this->load->model(array('player_model','player_api_verify_status'));
        $this->load->library('xinyanapi');

        $isEnabledMDB = $this->utils->isEnabledMDB();
        if ( $isEnabledMDB ) {
            $file_list = [];
            $multiple_databases = $this->utils->getConfig('multiple_databases');
            $og_target_db = $event->getOgTargetDb($isEnabledMDB, $multiple_databases);
            if( empty($og_target_db) ){
                $this->utils->error_log('The database does not exist, og_target_db:', $og_target_db);
            }else{
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($og_target_db);
                $sourceDB=$this->utils->getActiveTargetDB();
                $this->utils->debug_log('===================Deposit Event getActiveTargetDB', $sourceDB);
            }
        }

        if(!empty($playerId)){
            $player = $this->player_model->getPlayerArrayById($playerId);
            $playerDetial = $this->player_model->getPlayerDetails($playerId);
        }

        $this->utils->debug_log('start process Register event', $event, $playerId, $player, $playerDetial);

        $messagesDetails = $this->utils->getConfig('enabled_automation_batch_send_internal_msg');

        $this->utils->debug_log(__METHOD__, 'messagesDetails', $messagesDetails);

        if ($messagesDetails) {
            $username = $player['username'];
            $this->automation_batch_send_internal_msg_for_OGP24283($playerId, $messagesDetails, $username);
        }

        if (!empty($this->utils->getConfig('telesale_api_list')) && is_array($this->utils->getConfig('telesale_api_list'))) {
            $this->utils->debug_log('============start telesale apis============');
            $this->utils->debug_log('============get playerdetails============', $playerDetial);
            $telesaleApis = $this->utils->getConfig('telesale_api_list');
            foreach ($telesaleApis as $telesaleApi) {
                $apiName = $telesaleApi.'_api';
                $classExists = file_exists(strtolower(APPPATH . 'libraries/telesale_api/' . $apiName . ".php"));
                if(!$classExists){
                    return;
                }
                $this->load->library(array('telesale_api/'.$apiName));
                $telesaleApi = $this->CI->$apiName;
                $result = $telesaleApi->postSaveCustomerData($playerId, $playerDetial);
                $this->utils->debug_log('============result_post_telesale_api============', $result);
            }
        }
        $this->processTrackingCallback($playerId, $player);

        // if($this->utils->isEnabledFeature('enable_registered_triggerRegisterEvent_for_xinyan_api')) {
        //     #check Register payment xinyan api
        //     $response = $this->xinyanapi->submitToXinyanApi($playerId,$player,$playerDetial);
        //     $register_options = $this->getConfig('register_event_xinyan_api');
        //     $new_dispatch_account_level = $register_options['assign_members_in_specific_dispatc_level'];
        //     $username = $player['username'];

        //     if(!isset($response['success'])){
        //         $this->player_api_verify_status->add($playerId, player_api_verify_status::API_UNKNOWN);
        //         $this->CI->utils->debug_log('==============submitToXinyanApi no response');
        //     }else if($response['success'] && $response['data']['code'] == self::SUCCESS_CODE){
        //         $this->player_api_verify_status->add($playerId, player_api_verify_status::API_RESPOSE_SUCCESS);
        //         $this->CI->utils->debug_log('==============submitToXinyanApi response verify success', $response);
        //     }else{
        //         $this->player_api_verify_status->add($playerId, player_api_verify_status::API_RESPOSE_FAIL);
        //         $assignToDispatchAccount = $this->xinyanapi->assignToDispatchAccount($playerId, $username, $new_dispatch_account_level);
        //         if(!$assignToDispatchAccount['success']){
        //             $get_notify_params = $this->getXinyanNotifyParams($response, $username, $playerId);
        //             $url = $this->getConfig('register_xinyan_notify_url');
        //             $user = $this->getConfig('register_xinyan_notify_user');
        //             $channel = $this->getConfig('register_xinyan_notify_channel');
        //             $this->utils->sendMessageService($get_notify_params['msg'], $url, $user, $channel);
        //             $this->CI->utils->debug_log('==============submitToXinyanApi response verify failed and assing to new Dispatch Account failed ,Will be notified by Mattermost', $response, $assignToDispatchAccount);
        //         }else{
        //             $this->CI->utils->debug_log('==============submitToXinyanApi response verify failed and assing to new Dispatch Account group', $response, $assignToDispatchAccount);
        //         }
        //     }
        // }//
        $this->utils->debug_log('end process Register event', $event);
    }

    public function automation_batch_send_internal_msg_for_OGP24283($playerId, $messagesDetails, $username){

        $messages_details_key = 'OGP24283';
        $messages_details = !empty($messagesDetails[$messages_details_key]) ? $messagesDetails[$messages_details_key] : false;

        $this->utils->debug_log(__METHOD__, 'start send msg', $playerId, $messages_details);

        if(!$messages_details){
            return $this->utils->debug_log(__METHOD__, 'the config are missing.');
        }

        if(empty($playerId)){
            return $this->utils->debug_log(__METHOD__, lang('No eligible players found.'));
        }

        $this->load->library(['player_message_library','authentication']);
        $this->load->model(array('player_model', 'internal_message','users'));

        $group      = isset($messages_details['group']) ? $messages_details['group'] : null;
        $min_level  = isset($messages_details['min_level']) ? $messages_details['min_level'] : null;
        $max_level  = isset($messages_details['max_level'])? $messages_details['max_level'] : null;
        $subject    = isset($messages_details['subject'])? $messages_details['subject'] : null;
        $message    = isset($messages_details['message'])? $this->formatMessage($messages_details['message'], [$username]) : null;
        $disabled_reply = true;
        $currentDateTime = new DateTime();
        $today      = $this->utils->getTodayForMysql();
        $this_month = !empty($this_month) ? $this_month : $currentDateTime->format('m');
        $userId     = 1;
        $sender     = $this->users->getUsernameById($userId);

        $this->utils->debug_log(__METHOD__, 'params', $group, $min_level, $max_level, $subject, $message, $disabled_reply, $today, $this_month, $userId, $sender);

        $this->startTrans();
        $res = $this->internal_message->addNewMessageAdmin($userId, $playerId, $sender, $subject, $message, TRUE, $disabled_reply);
        $this->utils->info_log(__METHOD__, 'result', $res, 'player', $playerId);
        $succ = $this->endTransWithSucc();

        if (!$succ) {
            return $this->utils->info_log(__METHOD__, lang('sys.ga.erroccured'));
        } else {
            return $this->utils->info_log(__METHOD__, lang('mess.19'));
        }
    }

    public function formatMessage($messageKey, $params = []) {
        $messageTemplate = lang($messageKey);

        $placeholderCount = substr_count($messageTemplate, '%s');

        if ($placeholderCount === count($params)) {
            return vsprintf($messageTemplate, $params);
        }

        return $messageTemplate;
    }

    public function getXinyanNotifyParams($response, $username, $playerId){
        if (!empty($playerId)) {
            $this->load->model(array('player_model'));

            if($response['data'] == null || empty($response['data'])){
                $desc = $response['errorMsg'].', errorCode =>'.$response['errorCode'];
                $trans_id = 'null';
            }else{
                $desc = $response['data']['desc'];
                $trans_id = $response['data']['trans_id'];
            }

            $dispatchaccountlevel = $this->CI->player_model->getPlayerDispatchAccountLevel($playerId);
            $result_msg =
                "register Xinyan playerId: " .$playerId. " | \n".
                "register Xinyan username: " .$username. " | \n".
                "register Xinyan desc: "     .$desc. " | \n".
                "register Xinyan trans_id: " .$trans_id. " | \n".
                "current Player Dispatch Account Group: " .$dispatchaccountlevel['dispatch_account_level_id'];

                $this->utils->debug_log('============getXinyanNotifyParams true, '.$result_msg);

                $result_msg = "=============== Mismatch member info responded from Xinyan API ===============\nMismatch member not added to Xinyan Mismatch group \n".$result_msg;

            return array('success' => true, 'msg' => $result_msg);
        }
        $this->utils->debug_log('============getXinyanNotifyParams false playerId, '.$playerId);
        return array('success' => false, 'msg' => 'Mattermost notify failed.');
    }

    public function processTrackingCallback($playerId, $player) {
        $this->load->library('player_trackingevent_library');
        $this->load->model('player_trackingevent');
        $eventCode = Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM;
        if(!$this->player_trackingevent->checkRecordExist($eventCode, null, $playerId)){
            $trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM';
            $this->utils->playerTrackingEvent($playerId, $trackingevent_source_type, array());
        }
        $tracking_info = $this->player_trackingevent_library->getTrackingInfoByPlayerId($playerId);
        $this->utils->debug_log('============processTrackingCallback============ ', $playerId, $tracking_info);
        if($tracking_info){
            $recid = $tracking_info['platform_id'];
            $this->player_trackingevent_library->processRegSuccess($recid, $tracking_info, $playerId, $player);
        }
        $this->utils->debug_log('============processTrackingCallback third_party_tracking_platform_list ============ ', $this->utils->getConfig('third_party_tracking_platform_list'));

        if($this->utils->getConfig('third_party_tracking_platform_list')){
            $tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
            foreach($tracking_list as $key => $val){
                if(isset($val['always_tracking'])){
                    $recid = $key;
                    $this->player_trackingevent_library->processRegSuccess($recid, $tracking_info, $playerId, $player);
                }
            }
        }
    }
}
