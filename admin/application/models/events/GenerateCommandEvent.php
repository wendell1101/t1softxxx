<?php

require_once dirname(__FILE__) . "/BaseEvent.php";

class GenerateCommandEvent extends BaseEvent{

    protected $command;
    protected $command_params;
    protected $is_blocked;
    protected $og_target_db;

    public function extractData($data){
        $this->og_target_db= null;

        $this->command=$data['command'];
        $this->command_params=$data['command_params'];
        $this->is_blocked=$data['is_blocked'];
        if( !empty($data['og_target_db']) ){
            // for mdb
            $this->og_target_db=$data['og_target_db']; // __OG_TARGET_DB
        }
    }

    public function getCommand(){
        return $this->command;
    }

    public function getCommandParams(){
        return $this->command_params;
    }

    public function getIsBlocked(){
        return $this->is_blocked;
    }

    /**
     * Get __OG_TARGET_DB
     *
     * @param bool $isEnabledMDB Usually be the return of utils::isEnabledMDB().
     * @param array $multiple_databases_of_config For confrm the database is exists.
     * Usually be the return of utils::getConfig('multiple_databases').
     *
     * @return null|string If its null, the database does Not exist Or Disabled MDB.
     */
    public function getOgTargetDb($isEnabledMDB, $multiple_databases_of_config){
        $og_target_db = null;
        if ( $isEnabledMDB ) {
            if( isset($multiple_databases_of_config[$this->og_target_db]['default']) ){
                $og_target_db = $this->og_target_db;
            }
        }

        return $og_target_db;
    } // EOF getOgTargetDb()


}
