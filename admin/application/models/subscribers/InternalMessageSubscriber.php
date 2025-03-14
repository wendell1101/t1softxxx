<?php

require_once dirname(__FILE__) . "/../events/InternalMessageEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InternalMessageSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_ON_GOT_MESSAGES => 'onGotMessages',
            Queue_result::EVENT_ON_ADDED_NEW_MESSAGE => 'onAddedNewMessage',
            Queue_result::EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READ => 'onUpdatedMessageStatusToRead',
            Queue_result::EVENT_ON_SENT_MESSAGE_FROM_ADMIN => 'onSentMessageFromAdmin',
        );
    }

    public function onSentMessageFromAdmin(InternalMessageEvent $event){
        $this->utils->debug_log('=================== InternalMessageSubscriber::onSentMessageFromAdmin() Event start', $event);
        $player_id = $event->getPlayerId();
        $source_method = $event->getSourceMethod();

        $extra_info = $event->getExtraInfo();
        // combine to $player_mapping_id {player_id_0}-{msg_id_0}_{player_id_1}-{msg_id_1}_{player_id_2}-{msg_id_2}_...
        if( !empty($extra_info) ){
            $_mapping_str_list = [];
            foreach($extra_info as $_player_id => $_message_id){
                $_mapping_str_list[] = $_player_id.'-'. $_message_id;
            }
            $_mapping_str = implode('_', $_mapping_str_list);
        }

        $command='do_notify_send_with_mapping_cmd';
        $command_params=[$_mapping_str, $source_method];
        $rlt = $this->runCommandLine($command, $command_params, $event);

        $this->utils->debug_log('=================== InternalMessageSubscriber::onSentMessageFromAdmin() Event end', $event, 'rlt:', $rlt);
    }

    public function onGotMessages(InternalMessageEvent $event){
        $this->utils->debug_log('=================== InternalMessageSubscriber::onGotMessages() Event start', $event);
        $player_id = $event->getPlayerId();
        $source_method = $event->getSourceMethod();
        $command='do_notify_send_cmd';
        $command_params=[$player_id, $source_method];
        $rlt = $this->runCommandLine($command, $command_params, $event);

        $this->utils->debug_log('=================== InternalMessageSubscriber::onGotMessages() Event end', $event, 'rlt:', $rlt);
    }

    public function onAddedNewMessage(InternalMessageEvent $event){
        $player_id = $event->getPlayerId();
        $this->utils->debug_log('=================== InternalMessageSubscriber::onAddedNewMessage() Event start', $event);
        $source_method = $event->getSourceMethod();
        // $source_method = __METHOD__; // InternalMessageSubscriber::onAddedNewMessage
        $command='do_notify_send_cmd';
        $command_params=[$player_id, $source_method];
        $rlt = $this->runCommandLine($command, $command_params, $event);
        $this->utils->debug_log('=================== InternalMessageSubscriber::onAddedNewMessage() Event end', $event, 'rlt:', $rlt);
    }

    public function onUpdatedMessageStatusToRead(InternalMessageEvent $event){
        $player_id = $event->getPlayerId();
        $this->utils->debug_log('=================== InternalMessageSubscriber::onUpdatedMessageStatusToRead() Event start', $event);
        $source_method = $event->getSourceMethod();
        // $source_method = __METHOD__; // InternalMessageSubscriber::onAddedNewMessage
        $command='do_notify_send_cmd';
        $command_params=[$player_id, $source_method];
        $rlt = $this->runCommandLine($command, $command_params, $event);
        $this->utils->debug_log('=================== InternalMessageSubscriber::onUpdatedMessageStatusToRead() Event end', $event, 'rlt:', $rlt);
    }


    public function runCommandLine($command, $command_params, InternalMessageEvent $event){
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
