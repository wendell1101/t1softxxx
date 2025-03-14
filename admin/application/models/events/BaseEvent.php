<?php

use Symfony\Component\EventDispatcher\Event;

/**
 *
 * abstract event
 *
 */
class BaseEvent extends Event{

    protected $data;
    protected $eventName;
    protected $token;
    protected $queueResult=null;
    protected $error=false;

    public function __construct($token, $eventName, array $data){
        $this->token=$token;
        $this->data=$data;
        $this->eventName=$eventName;
        $this->extractData($data);
    }

    public function getToken(){
        return $this->token;
    }

    public function getData(){
        return $this->data;
    }

    public function getEventName(){
        return $this->eventName;
    }

    public function extractData($data){
        //convert $this->data to business data
    }

    /**
     * append this result to queue_result, it's optional
     * @param array $queueResult
     */
    public function setQueueResult(array $queueResult){
        $this->queueResult=$queueResult;
    }

    public function getQueueResult(){
        return $this->queueResult;
    }

    public function setError($error){
        $this->error=$error;
    }

    public function isError(){
        return $this->error;
    }

    public function toJson(){
        return ['token'=>$this->token, 'data'=>$this->data, 'event_name'=>$this->eventName];
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
