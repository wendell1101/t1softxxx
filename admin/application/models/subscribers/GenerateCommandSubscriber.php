<?php

require_once dirname(__FILE__) . "/../events/GenerateCommandEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GenerateCommandSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_GENERATE_COMMAND => 'generateCommand',
        );
    }

    public function generateCommand(GenerateCommandEvent $event) {
        $this->utils->debug_log('===================GenerateCommand Event start', $event);

        $command=$event->getCommand();
        $command_params=$event->getCommandParams();
        $is_blocked=$event->getIsBlocked();
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
            raw_debug_log($cmd, pclose(popen($cmd, 'r')));
        }

        $this->utils->debug_log('===================GenerateCommand Event end', $event);
    }// EOF generateCommand()
}
