<?php

require_once dirname(__FILE__) . "/../events/PlayerLoginEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_AFTER_PLAYER_LOGIN => 'afterPlayerLogin',
        );
    }

    public function afterPlayerLogin(PlayerLoginEvent $event){
        $this->load->library(["notify_in_app_library"]);
        $this->load->model(['player_model']);

        $this->utils->debug_log('===================Player Login Event afterPlayerLogin', $event);

        $this->utils->debug_log('start process player login event', $event);

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

        $this->utils->debug_log('====checkPlayerLoginMultipleIPForNotification', $event->getPlayerId(), $event->getLoginIp(), $event->getLoginInfo());
        $this->checkPlayerLoginMultipleIPForNotification($event->getPlayerId(), $event->getLoginIp(), $event->getLoginInfo());

        $this->processTrackingCallback($event->getPlayerId(), $event->getLoginIp(), $event->getLoginInfo());

        $this->notify_in_app_library->do_notify_send($event->getPlayerId(), $event->getSourceMethod() );

        $this->utils->debug_log('end process player login event', $event);
    }

    public function checkPlayerLoginMultipleIPForNotification($player_id, $login_ip, $info) {
        $mutiple_login_notify_setting = array_replace_recursive($this->config->item('mutiple_login_notify_setting'), $this->config->item('mutiple_login_notify_setting_override'));
        $get_check_player_login_ip_result = $this->player_model->checkPlayerMultipleLoginIp($player_id, $login_ip, $info);

        if (isset($get_check_player_login_ip_result['success'])
            && $get_check_player_login_ip_result['success']
            && !empty( $this->utils->getConfig('enable_mutiple_login_notify') )
        ) {
            $url = $mutiple_login_notify_setting['notify_url'];
            $user = $mutiple_login_notify_setting['notify_user'];
            $channel = $mutiple_login_notify_setting['notify_channel'];
            $this->utils->sendMessageService($get_check_player_login_ip_result['msg'], $url, $user, $channel);
        }
    }

    public function processTrackingCallback($playerId, $login_ip, $info) {
        $this->load->library(['player_trackingevent_library']);
        if(!empty($playerId)){
            $player = $this->player_model->getPlayerArrayById($playerId);
            // $playerDetial = $this->player_model->getPlayerDetails($playerId);
        }
        $tracking_info = $this->player_trackingevent_library->getTrackingInfoByPlayerId($playerId);
        $this->utils->debug_log('============processTrackingCallback============ ', $playerId, $tracking_info);
        if($tracking_info){
            $recid = $tracking_info['platform_id'];
            $this->player_trackingevent_library->processLogin($recid, $tracking_info, $playerId, $player);
        }
        if($this->utils->getConfig('third_party_tracking_platform_list')){
            $tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
            foreach($tracking_list as $key => $val){
                if(isset($val['always_tracking'])){
                    $recid = $key;
                    $this->player_trackingevent_library->processLogin($recid, $tracking_info, $playerId, $player);
                }
            }
        }
    }
}
