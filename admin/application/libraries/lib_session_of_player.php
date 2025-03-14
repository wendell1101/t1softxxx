<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}


class Lib_session_of_player {

    const SCAN_SESSION_DISABLED = 0;
    const SCAN_SESSION_WITH_FILE = 1;
    const SCAN_SESSION_WITH_RELAY_TABLE = 2;

	public function __construct() {
        $_session_of_player = config_item('session_of_player');
        $this->_extractConfigFromParams( $_session_of_player, true );
        if( ! empty($_session_of_player['scan_session_with']) ){
            $this->scan_session_with = $_session_of_player['scan_session_with'];
        }else{
            $this->scan_session_with = self::SCAN_SESSION_DISABLED;
        }

		$this->CI = &get_instance();
        $this->utils = &$this->CI->utils;
	}

    public function _extractConfigFromParams($params, $assign2self = false)
    {
        $CI = &get_instance();
        $utils = &$CI->utils;

        $config_list = [];
        // Cloned from CI_Session::__construct().
        foreach (array('sess_encrypt_cookie'
                    , 'sess_use_database'
                    , 'sess_table_name'
                    , 'sess_expiration'
                    , 'sess_expiration_time_on_redis_when_expire_on_close'
                    , 'sess_expire_on_close'
                    , 'sess_expire_append_on_ajax_request'
                    , 'sess_match_ip'
                    , 'sess_match_useragent'
                    , 'sess_match_hmac'
                    , 'sess_cookie_name'
                    , 'sess_expiration_use_custom_setting'
                    , 'sess_use_redis'
                    , 'sess_use_file'
                    , 'sess_store_filepath'
                    , 'cookie_path'
                    , 'cookie_domain'
                    , 'cookie_secure'
                    , 'sess_time_to_update'
                    , 'time_reference'
                    , 'cookie_prefix'
                    , 'encryption_key') as $key
        ) {
            $config_list[$key] = (isset($params[$key])) ? $params[$key] : $CI->config->item($key);
            if($assign2self){
                $this->$key = $config_list[$key];
            }
		}

        return $config_list;
    } // EOF _extractConfigFromParams

    /**
     * scan session on file
     *
     * @param callable $callback callback(array $jsonArr, timestamp $lastActivity): bool
     * @param string $queryKeyword, The json contains keyword.
     * @return void
     */
    public function _scanSessionOnFile(callable $callback
                                        , $queryKeyword = 'player_id'
                                        , $queryKeyword4prefixVal = '[[:alnum:]]'
                                        , $sess_store_filepath = null
                                        , $session_id = null
    ){
        if( empty($sess_store_filepath) ){
            $sess_store_filepath = $this->sess_store_filepath;
        }

        if( ! is_null($session_id) ){
            $sess_store_path_sess_file = $this->getSessionStoreFilepathForFile($session_id);
            if (file_exists($sess_store_path_sess_file)) {
                $sess_store_filepath = $sess_store_path_sess_file;
            }
        }

        /// ex: grep -ir -h '"player_id":"*[[:alnum:]]' /home/vagrant/Code/og/tmp/session
        // "*[[:alnum:]] : allow string type and integer type, ex:"player_id":"1234" and "player_id":1234
        $cmd_formater = 'grep -ir -h \'"%s":"*%s\' %s'; // 3 param: $queryKeyword, $queryKeyword4prefixVal, $sess_store_filepath
        $cmd = sprintf($cmd_formater, $queryKeyword, $queryKeyword4prefixVal, $sess_store_filepath);

        $_output = $this->utils->catchOutputWithShellCmd($cmd);
        $_output = explode(PHP_EOL, $_output);
        $_output = array_filter($_output, function($value) {
                        return !is_null($value) && $value !== '';
                    });
        $total_count = count($_output);
        $count=0;
        foreach($_output as $lineIndex => $jsonStr){
            $jsonArr=json_decode($jsonStr, true);
            $session_id = $jsonArr['session_id'];

            $lastActivity = $jsonArr['last_activity'];
            $continue = $callback($jsonArr, $lastActivity);
            if(!$continue){
                $this->utils->debug_log('stop on', $session_id, $lastActivity);
                break;
            }
            $count++;
        }
        $this->utils->debug_log('finish scan', $count, 'total_count:', $total_count);
        $this->utils->debug_log('finish scan with cmd:', $cmd);

    } // EOF _scanSessionOnFile

    // ===file=================
    public function getSessionBySessionIdOnFile($sessionId){
        $session=null;

        $queryKeyword = 'player_id';
        $queryKeyword4prefixVal = '[[:alnum:]]';
        $sess_store_filepath = null;
        $this->_scanSessionOnFile(function($jsonArr, $lastActivity)
        use ( &$session, $sessionId ) {
            if($jsonArr['session_id'] == $sessionId){
                $session = $jsonArr;
                return false; // break
            }
            return true; // continue
        }, $queryKeyword
        , $queryKeyword4prefixVal
        , $sess_store_filepath
        , $sessionId );

        return $session;
    } // EOF getSessionBySessionIdOnFile

    /**
     * count session id
     *
     * @param int  $fromTimestamp
     * @param array $objectIdList aka. player id list, default is null
     * @return int $count
     */
    public function countSessionIdByObjectIdOnFile( $fromTimestamp, $objectIdList=null){

        switch($this->scan_session_with){
            case self::SCAN_SESSION_DISABLED:
                $count = 0;
                break;
            case self::SCAN_SESSION_WITH_FILE:
                $count = $this->__scanSessionOnFileWithFileByFromTimestampAndObjectIdList($fromTimestamp, $objectIdList);
                break;
            case self::SCAN_SESSION_WITH_RELAY_TABLE:
                $count = $this->__scanSessionOnFileWithRelayTableByFromTimestampAndObjectIdList($fromTimestamp, $objectIdList);
                break;
        }
        return $count;

    } // EOF countSessionIdByObjectIdOnFile
    /**
     * Count the players limit before $fromTimestamp
     *
     * @param integer $fromTimestamp
     * @param array $objectIdList The player_id list
     * @return integer The count number.
     */
    public function __scanSessionOnFileWithFileByFromTimestampAndObjectIdList($fromTimestamp, $objectIdList=null){
        $count = 0;
        $queryKeyword = 'player_id';
        $this->_scanSessionOnFile(function($jsonArr, $lastActivity)
        use (&$count, $fromTimestamp, $objectIdList) {
            $user_data = $jsonArr['user_data'];
            $this->utils->debug_log('countSessionIdByObjectIdOnFile.fromTimestamp:', $fromTimestamp, 'lastActivity:', $lastActivity, "jsonArr", $jsonArr );

            if( empty($objectIdList) ){
                if( ! empty($user_data['player_id']) ){
                    if($fromTimestamp<0 || $lastActivity>=$fromTimestamp){
                        $count++;
                    }
                }
            }else{
                if( ! empty($user_data)
                    && in_array($user_data['player_id'], $objectIdList)
                ){
                    //no limit or >= from time
                    if($fromTimestamp<0 || $lastActivity>=$fromTimestamp){
                        $count++;
                    }
                }
            }

            return true; // continue
        }, $queryKeyword);

        $this->utils->debug_log('countSessionIdByObjectIdOnFile.count:', $count);

        return $count;
    } // EOF __scanSessionOnFileWithFileByFromTimestampAndObjectIdList
    //
    public function __scanSessionOnFileWithRelayTableByFromTimestampAndObjectIdList($fromTimestamp, $objectIdList=null){
        $this->CI->load->model(['player_session_files_relay']);
        // $fromTimestamp, $objectIdList
        // get_session_by_player_ids_from_timestamp
        $_session_id_list = $this->CI->player_session_files_relay->get_session_id_list_by_player_ids_from_timestamp($objectIdList, $fromTimestamp);
        $count = empty($_session_id_list)? 0: count($_session_id_list);
        return $count;
    } // EOF __scanSessionOnFileWithRelayTableByFromTimestampAndObjectIdList


    /**
     * get Any Available Session By ObjectId(aka. player_id) in sess_use_file=true
     *
     * @param integer $objectId the player_id
     * @param integer $tiemoutSeconds
     * @return void
     */
    public function getAnyAvailableSessionByObjectIdOnFile($objectId, $tiemoutSeconds){

        switch($this->scan_session_with){
            case self::SCAN_SESSION_DISABLED:
                $session = [];
                break;
            case self::SCAN_SESSION_WITH_FILE:
                $session = $this->__scanSessionOnFileWithFileByObjectIdAndTiemoutSeconds($objectId, $tiemoutSeconds);
                break;
            case self::SCAN_SESSION_WITH_RELAY_TABLE:
                $session = $this->__scanSessionOnFileWithRelayTableByObjectIdAndTiemoutSeconds($objectId, $tiemoutSeconds);
                break;
        }
        return $session;
    } // EOF getAnyAvailableSessionByObjectIdOnFile
    //
    public function __scanSessionOnFileWithFileByObjectIdAndTiemoutSeconds($objectId, $tiemoutSeconds){
        $session=null;
        $queryKeyword4prefixVal = $objectId. '"*'; // add suffix,'"*' in pattern of grep
        $queryKeyword = 'player_id';
        $this->_scanSessionOnFile(function($jsonArr, $lastActivity)
        use ( &$session, $tiemoutSeconds ) {
            $continue=true;
            $last_activity = $lastActivity;
            $is_timeout = time() > $last_activity + $tiemoutSeconds;
            // if is_timeout = true, it means the session had timeout.
            if(!$is_timeout){
                //found , break
                $session=$jsonArr;
                $continue=false;
            }
            return $continue; // continue
        }, $queryKeyword, $queryKeyword4prefixVal);
        return $session;
    }
    public function __scanSessionOnFileWithRelayTableByObjectIdAndTiemoutSeconds($objectId, $tiemoutSeconds){
        $this->CI->load->model(['player_session_files_relay']);
        return $this->CI->player_session_files_relay->get_session_by_player_id_tiemout_seconds($objectId, $tiemoutSeconds);
    }

    /**
     * Get the session_id list by player_id
     *
     * @param integer $objectId It aka. player_id.
     * @return array The session_id list
     */
    public function searchSessionIdByObjectIdOnFile( $objectId ){
        switch($this->scan_session_with){
            case self::SCAN_SESSION_DISABLED:
                $sessions = [];
                break;
            case self::SCAN_SESSION_WITH_FILE:
                $sessions = $this->__scanSessionOnFileWithFile($objectId);
                break;
            case self::SCAN_SESSION_WITH_RELAY_TABLE:
                $sessions = $this->__scanSessionOnFileWithRelayTable($objectId);
                break;
        }
        return $sessions;
    } // EOF searchSessionIdByObjectIdOnFile

    /**
     * Return all object id( aka. player_id), from $fromTimestamp to now
     * @param int $fromTimestamp
     * @return array $objectIdList unique
     */
    public function searchAllObjectIdOnFile($fromTimestamp=-1){
        switch($this->scan_session_with){
            case self::SCAN_SESSION_DISABLED:
                $objectIdList = [];
                break;
            case self::SCAN_SESSION_WITH_FILE:
                $objectIdList = $this->__scanSessionOnFileWithFileByFromTimestamp($fromTimestamp);
                break;
            case self::SCAN_SESSION_WITH_RELAY_TABLE:
                $objectIdList = $this->__scanSessionOnFileWithRelayTableByFromTimestamp($fromTimestamp);
                break;
        }
        return $objectIdList;
    } // EOF searchAllObjectIdOnFile
    /**
     * Get player_id list limit before $fromTimestamp
     *
     * @param integer $fromTimestamp The timestamp.
     * @return array The player_id list
     */
    public function __scanSessionOnFileWithFileByFromTimestamp($fromTimestamp=-1){
        $objectIdList=[];
        $queryKeyword = 'player_id';
        $this->_scanSessionOnFile(function($jsonArr, $lastActivity)
        use ( &$objectIdList, $fromTimestamp ) {
            $continue=true;
            $user_data = $jsonArr['user_data'];
            if( ! empty($user_data['player_id']) ){
                if($fromTimestamp<0 || $lastActivity>=$fromTimestamp){
                    $objectIdList[] = $user_data['player_id'];
                }
            }
            return $continue; // continue
        }, $queryKeyword);

        return array_unique($objectIdList, SORT_NUMERIC);
    } // EOF __scanSessionOnFileWithFileByFromTimestamp
    public function __scanSessionOnFileWithRelayTableByFromTimestamp($fromTimestamp=-1){
        $this->CI->load->model(['player_session_files_relay']);
        return $this->CI->player_session_files_relay->get_player_id_list_by_from_timestamp($fromTimestamp);
    } // EOF __scanSessionOnFileWithRelayTableByFromTimestamp

    /**
     * delete sessions by object id(, player_id)
     * @param  int $objectId (player_id)
     * @return int $count
     */
    public function deleteSessionsByObjectIdOnFile($objectId){
        if($this->scan_session_with == self::SCAN_SESSION_WITH_RELAY_TABLE){
            $this->CI->load->model(['player_session_files_relay']);
        }
        $count = 0;
        switch($this->scan_session_with){
            case self::SCAN_SESSION_DISABLED:
                $sessions = [];
                break;
            case self::SCAN_SESSION_WITH_FILE:
                $sessions = $this->__scanSessionOnFileWithFile($objectId);
                break;
            case self::SCAN_SESSION_WITH_RELAY_TABLE:
                $sessions = $this->__scanSessionOnFileWithRelayTable($objectId);
                break;
        }

        foreach($sessions as $session_id){
            $is_unlink = null;
            // cloned from CI_Session::deleteBySessionIdFromFile()
            $sessFile = $this->getSessionStoreFilepathForFile($session_id);
            if(file_exists($sessFile)){
                $this->utils->debug_log('deleteSessionsByObjectIdOnFile '.$sessFile, ['session_id'=>$session_id]);
                $count++;
                $is_unlink = unlink($sessFile);
            }

            if( $is_unlink === true // the file had deleted
                && $this->scan_session_with == self::SCAN_SESSION_WITH_RELAY_TABLE
            ){
                $this->CI->player_session_files_relay->deleteBySessionId($session_id);
            }
        } // EOF foreach($sessions as $session_id){...
        return $count;
    }// EOF deleteSessionsByObjectIdOnFile
    /**
     * Get session_id list By $objectId (, aka. player_id )
     *
     * @param integer $objectId Its aka. player_id
     * @return array The session_id list
     */
    public function __scanSessionOnFileWithFile($objectId){
        $sessions = [];
        $queryKeyword4prefixVal = $objectId. '"*'; // add suffix,'"*' in pattern of grep
        $queryKeyword = 'player_id';
        $this->_scanSessionOnFile(function($jsonArr, $lastActivity)
        use ( &$sessions, $objectId ) {
            $continue=true;
            $user_data = $jsonArr['user_data'];
            if( ! empty($user_data)
                && $user_data['player_id'] == $objectId
            ){
                $sessions[]=$jsonArr['session_id'];
            }
            return $continue; // continue
        }, $queryKeyword, $queryKeyword4prefixVal);
        return $sessions;
    }// EOF __scanSessionOnFileWithFile
    public function __scanSessionOnFileWithRelayTable($objectId){
        $this->CI->load->model(['player_session_files_relay']);
        return $this->CI->player_session_files_relay->get_session_id_list_by_player_id($objectId);
    }// EOF __scanSessionOnFileWithRelayTable

    public function getSessionStoreFilepathForFile($session_id, $readonly = true, $sess_store_filepath = null){
        if(empty($sess_store_filepath)){
            $sess_store_filepath = $this->sess_store_filepath;
        }

        $sessDir = $sess_store_filepath.'/'.substr($session_id, 0, 2);

        if(!file_exists($sessDir) && !$readonly ){
            @mkdir($sessDir, 0777, true);
            //chmod
            @chmod($sessDir, 0777);
        }

        $sessFile=$sessDir.'/'.$session_id.'.json';
        return $sessFile;
    } // EOF getSessionStoreFilepathForFile

}

