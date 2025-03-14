<?php

require_once dirname(__FILE__) . "/../events/PlayerProfileEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlayerSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_ON_GOT_PROFILE_VIA_API => 'onGotProfileViaAPI',
        );
    }


    public function onGotProfileViaAPI(PlayerProfileEvent $event){
        $this->utils->debug_log('=================== PlayerSubscriber::onGotProfileViaAPI() Event start', $event);
        $player_id = $event->getPlayerId();
        $source_method = $event->getSourceMethod();
        $command='do_notify_send_cmd';
        $command_params=[$player_id, $source_method];
        $rlt = $this->runCommandLine($command, $command_params, $event);

        $this->utils->debug_log('=================== PlayerSubscriber::onGotProfileViaAPI() Event end', $event, 'rlt:', $rlt);
    }

    public function runCommandLine($command, $command_params, PlayerProfileEvent $event){
        // $command=$event->getCommand();
        // $command_params=$event->getCommandParams();
        // $is_blocked=$event->getIsBlocked();
        $is_blocked=false;
        $cmd = null;
        $isEnabledMDB = $this->utils->isEnabledMDB();
        if ( $isEnabledMDB ) {
            $file_list = [];
            $multiple_databases = $this->utils->getConfig('multiple_databases');
            $og_target_db = $event->getOgTargetDb($isEnabledMDB, $multiple_databases);
            if( empty($og_target_db) ){
                $this->utils->error_log('The database does not exist, og_target_db:', $og_target_db);
            }else{
                /// The params,$file_list, $og_target_db for mdb.
                // please checkout mdb branch for check.
                $cmd=$this->utils->generateCommandLine($command, $command_params, $is_blocked, $file_list, $og_target_db);
            }
        }else{
            $cmd=$this->utils->generateCommandLine($command, $command_params, $is_blocked);
        }

        if( ! empty($cmd) ){
            $this->utils->runCmd($cmd);
            // raw_debug_log($cmd, pclose(popen($cmd, 'r')));
        }
    }
}
