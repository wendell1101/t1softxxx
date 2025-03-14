<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * player session files relay
 * include,
 * session_id
 * player_id
 * last_activity_of_updated
 *
 *
 * @category player_session_files_relay
 * @version 1.0.0
 * @copyright tot
 */
class Player_session_files_relay extends BaseModel {

	protected $tableName = 'player_session_files_relay';
	protected $idField = 'id';
	public $sess_store_filepath = null;
    public $sess_file_list_filepath = null;
    public $sess_file_list_filename = null;
    public $file_not_exists_index = 0;

    // syncMode
    const SYNCMODE_INSERT = 1;
    const SYNCMODE_UPDATE = 2;
    const SYNCMODE_UPDATE_CANCEL = 3;
    const SYNCMODE_INSERT_CANCEL_BY_NO_PLAYER = 4;
    const SYNCMODE_DELETE = 5;
    const SYNCMODE_FILE_NOT_EXISTS = 6;


	public function __construct() {
		parent::__construct();
		$this->load->model(['Player', 'player_model', 'operatorglobalsettings', 'transactions']);
		// $this->load->library(['player_manager']);


		// $this->defaultData = $this->getDefaultData();
        $session_of_player = config_item('session_of_player');
        if( !empty($session_of_player['sess_store_filepath']) ){
            $this->sess_store_filepath = $session_of_player['sess_store_filepath'];
        }
        if( !empty($session_of_player['sess_file_list_filepath']) ){
            $this->sess_file_list_filepath = $session_of_player['sess_file_list_filepath'];
        }
        if( !empty($session_of_player['sess_file_list_filename']) ){
            $this->sess_file_list_filename = $session_of_player['sess_file_list_filename'];
        }

        // for Test
        if( !empty($session_of_player['file_not_exists_index']) ){
            $this->file_not_exists_index = $session_of_player['file_not_exists_index'];
        }
    }

    public function get_session_id_list_by_player_ids_from_timestamp($player_ids = [], $fromTimestamp = -1, $db = null){
        if( empty($db) ){
            $db = $this->db;
        }

        $session_id_list = [];
        $db->select('session_id')
            ->from($this->tableName);
        if($fromTimestamp > 0){
            $db->where('last_activity_of_updated >= '. $fromTimestamp, null, false);
        }
        if( ! empty($player_ids) ){
            $db->where_in('player_id', $player_ids);
        }
        $rows = $this->runMultipleRowArray($db);
        if(!empty($rows) ){
            $session_id_list = array_column($rows, 'session_id');
        }
        return  $session_id_list;
    }

    /**
     * Get a session, that had not yet timeout by player_id.
     *
     * @param integer $player_id
     * @param integer $tiemoutSeconds
     * @param CI_DB_driver $db for mdb used.
     * @return array $_json The array of session content, after json decode.
     */
    public function get_session_by_player_id_tiemout_seconds($player_id, $tiemoutSeconds, $db = null){
        $this->load->library(['lib_session_of_player']);
        if( empty($db) ){
            $db = $this->db;
        }
        $_json = [];
        $db->select('session_id')
            ->from($this->tableName)
            ->where('player_id', $player_id)
            ->where("UNIX_TIMESTAMP() <= (last_activity_of_updated + $tiemoutSeconds)", null, false)
            ->limit(1);

        $row = $this->runOneRowArray($db);
        if(!empty($row) ){
            $filepath = $this->lib_session_of_player
                            ->getSessionStoreFilepathForFile(   $row['session_id'] /// #1
                                                            , true /// #2, $readonly
                                                            , $this->sess_store_filepath /// #3, $sess_store_filepath
                                                        );
            if( file_exists($filepath) ){
                $_contents = file_get_contents($filepath);
                $_json = json_decode($_contents, true);
            }
        }
        return $_json;
    }
    public function get_player_id_list_by_from_timestamp($fromTimestamp = -1, $db = null){
        if( empty($db) ){
            $db = $this->db;
        }
        $player_ids = [];
        $db->distinct()
        ->select('player_id')
        ->from($this->tableName);
        if($fromTimestamp > 0){
            $db->where('last_activity_of_updated >= '. $fromTimestamp, null, false);
        }
        $rows = $this->runMultipleRowArray($db);
        if(!empty($rows) ){
            $player_ids = array_column($rows, 'player_id');
        }
        return  $player_id;
    }

    public function get_session_id_list_by_player_id($player_id, $db=null){
        if( empty($db) ){
            $db = $this->db;
        }
        $sessions = [];
        $db->distinct()
        ->select('session_id')
        ->from($this->tableName)
        ->where('player_id', $player_id);
        $rows = $this->runMultipleRowArray($db);
        if(!empty($rows) ){
            $sessions = array_column($rows, 'session_id');
        }
        return $sessions;
    }




    /// =====
    /**
     * new a filename into the attr., "sess_file_list_filename".
     *
     * @return void
     */
    public function new_sess_file_list_filename($set2attr = true){
        $_filename = 'sess_file_list_'. $this->utils->createTempFileName();
        if($set2attr){
            $this->sess_file_list_filename = $_filename;
        }
        return $_filename;
    }
    /**
     * Get the file path with name
     * That's contains a the file list
     *
     * @param string $filepath The file path, ex: "/var/tmp/sess_player"
     * @param string $filename The file name, ex: sess_file_list_XXOO
     * @param boolean $newWhenNotExists If its true, the function will check the following,
     * New a filename in sess_file_list_filename attr., when the file is not exists.
     * @return string The path and file name combined.
     * - The attr., sess_file_list_filepath as filepath
     * - The attr., sess_file_list_filename as filename
     */
    public function get_sess_file_list_filename($filepath = null, $filename = null, $newWhenNotExists = false){
        $sess_file_list_filepath_with_name = null;
        if( empty($filepath) ){
            $filepath = $this->sess_file_list_filepath;
        }
        if( empty($filename) ){
            $filename = $this->sess_file_list_filename;
        }

        $sess_file_list_filepath_with_name = $filepath. '/'. $filename;
        if($newWhenNotExists){
            if( ! file_exists($sess_file_list_filepath_with_name) ){
                $this->utils->debug_log('will new_sess_file_list_filename.sess_file_list_filepath_with_name:', $sess_file_list_filepath_with_name);
                $this->new_sess_file_list_filename();
                $this->utils->debug_log('done new_sess_file_list_filename.sess_file_list_filename:', $this->sess_file_list_filename);
                $filename = $this->sess_file_list_filename;
                $sess_file_list_filepath_with_name = $filepath. '/'. $filename;
            }
        }
        return $sess_file_list_filepath_with_name;
    } // EOF get_sess_file_list_filename


    public function gen_file_list_into_file($find_path = null, $output_file = null){
        if( empty($find_path) ){
            $find_path = $this->sess_store_filepath;
        }
        if( empty($output_file) ){
            $output_file = $this->get_sess_file_list_filename($this->sess_file_list_filepath, null, true);
        }
        $results = [];
        $results['bool'] = null;
        $results['msg'] = null;

        $cmd_formater = 'find %s -maxdepth 2 -type f > %s'; // 2 param: $sess_store_filepath, $sess_file_list_filepath_with_name
        $cmd = sprintf($cmd_formater, $find_path, $output_file);
        $results['cmd'] = $cmd;
        $this->utils->debug_log('gen_file_list_into_file.cmd:', $cmd);
        $this->benchmark->mark('catchOutputWithShellCmd_start');
        // $_output = $this->utils->catchOutputWithShellCmd($cmd);
        $_output=$this->utils->runCmd($cmd)==0;
        $results['output'] = $_output;
        $this->benchmark->mark('catchOutputWithShellCmd_stop');
        $elapsed_time = $this->benchmark->elapsed_time('catchOutputWithShellCmd_start', 'catchOutputWithShellCmd_stop');
        $results['elapsed_time'] = $elapsed_time;
        $this->utils->debug_log('gen_file_list_into_file._output:', $_output
                                , 'elapsed_time:', $elapsed_time
                            );
        if( !empty($_output) ){
            $results['bool'] = true;
        }else{
            $results['bool'] = false;
        }
        return $results;
    }
    public function read_file_list_from_file($sess_file_list_filepath_with_name = null){
        $_contents = '';
        if( empty($sess_file_list_filepath_with_name) ){
            $sess_file_list_filepath_with_name = $this->get_sess_file_list_filename($this->sess_file_list_filepath, null);
            $_contents = file_get_contents($sess_file_list_filepath_with_name);
        }
        return $_contents;
    }

    /**
     * Get the relay row and update the related fields form the data of session file
     *
     * @param array $data The session file data, the required fields as following
     * - session_id, required
     * - player_id
     * - last_activity
     * @return void
     */
    public function syncFile2table($data, $db=null){
        if( empty($db) ){
            $db = $this->db;
        }
        $this->benchmark->mark('syncFile2table_start');
        $result = [];
        $result['bool'] = null;
        $result['msg'] = null;
        $affected = null;
        // getOneRowArrayByField($tableName, $fieldName, $val, $db=null)
        $row = $this->getOneRowArrayByField($this->tableName, 'session_id', $data['session_id'], $db);
        $result['session_id'] = $data['session_id']; // for debug
        $result['params_data'] = $data;
        if(empty($row)){
            if( ! empty($data['player_id']) ){
                // insert
                if( !empty($data['last_activity']) ){ // re-assign in the col
                    $data['last_activity_of_updated'] = $data['last_activity'];
                    unset($data['last_activity']); //clear
                }
                $affected = $this->insertRow($data);
                $this->utils->debug_log('syncFile2table.insertRow.last_query:', $db->last_query());
                // reload
                $row = $this->getOneRowArrayByField($this->tableName, 'session_id', $data['session_id'], $db);
                $result['bool'] = empty($affected)? false: true;
                $result['msg'] = 'sync with insert';
                $result['syncMode'] = self::SYNCMODE_INSERT;

            }else{
                $result['bool'] = true;
                $result['msg'] = 'sync with No player_id';
                $result['syncMode'] = self::SYNCMODE_INSERT_CANCEL_BY_NO_PLAYER;
            }

        }else{
            $isUpdatedWithDeletedAt = false; // for mark the row will be deleted.
            $_nowForMysql = $this->utils->getNowForMysql();
            $_data_diff = [];
            if(empty($data['player_id']) ){ // for remove player_id of session
                /// to mark the row will be deleted.
                $isUpdatedWithDeletedAt = true;
            }else if( $data['player_id'] != $row['player_id'] ){
                // Not found the trigger behavior
                $_data_diff['player_id'] = $data['player_id'];
            }
            if( ! empty($data['last_activity']) ){
                if($row['last_activity_of_updated'] != $data['last_activity']){
                    $_data_diff['player_id'] = $data['player_id']; // keep orig
                    $_data_diff['last_activity_of_updated'] = $data['last_activity'];
                }
            }
            if(!empty($data['deleted_at']) ){
                /// to mark the row will be deleted.
                $isUpdatedWithDeletedAt = true;
            }
             // for update with player
            $isUpdatedWithPlayer = ! empty($_data_diff) && !empty($_data_diff['player_id']);

            $result['isUpdatedWithPlayer'] = $isUpdatedWithPlayer;
            $result['isUpdatedWithDeletedAt'] = $isUpdatedWithDeletedAt;

            if($isUpdatedWithDeletedAt){
                $affected = $this->deleteById($row['id'], $db);
                $result['bool'] = empty($affected)? false: true;
                $result['msg'] = 'sync with delete';
                $result['syncMode'] = self::SYNCMODE_DELETE;
            }else if( $isUpdatedWithPlayer ){
                $_data_diff['latest_sync_at'] = $_nowForMysql;
                // update
                $affected = $this->updateRow($row['id'], $_data_diff, $db);
                $result['last_query'] = $db->last_query();
                $this->utils->debug_log('syncFile2table.updateRow.last_query:', $db->last_query());
                $result['bool'] = empty($affected)? false: true;
                $result['msg'] = 'sync with update';
                $result['syncMode'] = self::SYNCMODE_UPDATE;
            }else{
                $result['bool'] = false;
                $result['msg'] = 'other case in update';
                $result['dataOfSessionFile'] = $data;
                $result['rowOfRelay'] = $row;
                $result['syncMode'] = self::SYNCMODE_UPDATE_CANCEL;
            }
        } // EOF if(empty($row)){...
        $this->benchmark->mark('syncFile2table_stop');
        $result['elapsed_time'] = $this->benchmark->elapsed_time('syncFile2table_start', 'syncFile2table_stop' );
        return $result;
    } // EOF syncFile2table

    /**
     * Check the session files exist,
     * If file is Not exists, delete the row of relay table.
     *
     * @param CI_DB_drvier $db
     * @return array $results The items as,
     * - $results['bool'] It will be true, when deleted_count + file_exists_count equial to total_count
     * - $results['total_count'] The row is too older than now
     * - $results['deleted_count']
     * - $results['file_exists_count']
     */
    public function cron4syncTableByfileExists($db = null){
        if(empty($db)){
            $db = $this->db;
        }
        $this->load->library(['lib_session_of_player']);

        // defaults
        $results = [];
        $results['bool'] = null;
        $results['total_count'] = 0;
        $results['deleted_count'] = 0;
        $results['file_exists_count'] = 0;

        $db->select('id')
            ->select('session_id')
            ->select('player_id')
            ->select('latest_sync_at')
            ->select('last_activity_of_updated') // last_activity of session, captured at updated
            ->from($this->tableName)
            ->where('updated_at < CURRENT_TIMESTAMP()', null, false);
        $rows = $this->runMultipleRowArray($db);
        $results['total_count'] = empty($rows)? 0: count($rows);

        $this->utils->debug_log('syncTable2file.check.total_count:', $results['total_count'] );
        if( ! empty($rows) ){
            foreach($rows as $index => $row){
                $filepath = $this->lib_session_of_player
                                ->getSessionStoreFilepathForFile(   $row['session_id'] /// #1
                                                                    , true /// #2, $readonly
                                                                    , $this->sess_store_filepath /// #3, $sess_store_filepath
                                                                );
                if( ! file_exists($filepath) ){
                    $affected = $this->deleteById($row['id'], $db);
                    if(!empty($affected)){
                        $results['deleted_count']++;
                    }
                }else{
                    $results['file_exists_count']++;
                }
                $this->utils->debug_log('cron4syncTableByfileExists.syncTable2file.check.index:', $index, 'total_count:', $results['total_count'] );
            }
        }

        $results['bool'] = ($results['deleted_count'] + $results['file_exists_count']) == $results['total_count'];
        return $results;
    }

    public function deleteById($id, $db = null){
        if(empty($db)){
            $db = $this->db;
        }
        $db->where('id', $id);
        return $this->runRealDelete($this->tableName, $db);
    }
    public function deleteBySessionId($session_id, $db = null){
        if(empty($db)){
            $db = $this->db;
        }
        $db->where('session_id', $session_id);
        return $this->runRealDelete($this->tableName, $db);
    }
    public function deleteByPlayerId($player_id, $db = null){
        if(empty($db)){
            $db = $this->db;
        }
        $db->where('player_id', $player_id);
        return $this->runRealDelete($this->tableName, $db);
    }

    public function cron4genSessionFileList($fixFilename = null){
        $sess_file_list_filepath_with_name = null;
        $results = [];

        if( ! empty($this->sess_store_filepath)
            && ! empty($this->sess_file_list_filepath)
        ){
            $sess_file_list_filepath_with_name = $this->get_sess_file_list_filename($this->sess_file_list_filepath, $fixFilename);
        }
        if( ! empty($sess_file_list_filepath_with_name)
            // && ! file_exists($sess_file_list_filepath_with_name)
        ){
            $results = $this->gen_file_list_into_file($this->sess_store_filepath, $sess_file_list_filepath_with_name);
        }
        return $results;
    }


    public function cron4syncTableFromFiles(){

        $results = [];
        $results['total_count'] = 0;
        $results['sync_done_count'] = 0;
        $results['sync_insert_failed_count'] = 0;
        $results['sync_insert_cancel_by_no_player_count'] = 0;
        $results['sync_update_done_count'] = 0;
        $results['sync_update_cancel_count'] = 0;
        $results['sync_unknown_failed_count'] = 0;
        $results['sync_file_not_exists_count'] = 0;


        $sess_file_list_filepath_with_name = $this->get_sess_file_list_filename($this->sess_file_list_filepath, null);
        $_contents = file_get_contents($sess_file_list_filepath_with_name);
        $lines = preg_split('/\n|\r\n?/', $_contents);
        $lines = array_filter($lines, function($value) {
                    return !is_null($value) && trim($value) !== '';
                });
        $lines = array_values($lines);
        $results['total_count'] = empty($lines)? 0: count($lines);

        $_max_elapsed_time_of_result = [];
        foreach($lines as $index => $line){
            $session_id = basename($line, ".json"); // get session id from filename

            if($index < 3){
                $this->utils->debug_log('cron4syncTableFromFiles.line:', $line);
            }

            $line_content = '';
            $is_file_exists = file_exists($line);
            if( ! empty($this->file_not_exists_index)
                && $index == $this->file_not_exists_index
            ){ /// Not empty for test the file not exsits
                $is_file_exists = false;
            }
            if( $is_file_exists ){
                $line_content .= file_get_contents($line); // No such file or directory ,檔案清單產生後，被系統移除，迴圈裡需要處理。
            }else{
                $line_content .= '{}';
            }
            $_user_data = [];
            $_json = json_decode($line_content, true);
            // $_json['session_id']
            // $_json['last_activity']
            if(!empty($_json['user_data'])){
                $_user_data = $_json['user_data'];
            }
            if(!empty($_user_data['player_id'])){
                $_user_data['player_id'];
            }
            if($index < 3 ){
                $this->utils->debug_log('cron4syncTableFromFiles._json:', $_json);
            }

            $_data = [];
            if( ! empty($_json['session_id']) ){
                $_data['session_id'] = $_json['session_id'];
            }
            if( ! empty($_json['last_activity']) ){ // @.last_activity
                $_data['last_activity'] = $_json['last_activity'];
            }
            if( ! empty($_user_data['LAST_ACTIVITY']) ){ // @.user_data.LAST_ACTIVITY
                // date time string, "Y-m-d H:i:s" to timestame
                $_dt = DateTime::createFromFormat('Y-m-d H:i:s', $_user_data['LAST_ACTIVITY']);
                $_data['last_activity'] = $_dt->getTimestamp();
                unset($_dt);// free
            }
            if(!empty($_user_data['player_id'])){
                $_data['player_id'] = $_user_data['player_id'];
            }

            $_result = [];
            if( !empty($_data) ){
                $_result = $this->syncFile2table($_data);
                if( empty($_max_elapsed_time_of_result) ){
                    $_max_elapsed_time_of_result = $_result;
                }else{
                    if( !empty($_result['elapsed_time']) ){
                        // $this->utils->debug_log('elapsed_time. _result:', $_result, '_max_elapsed_time_of_result:', $_max_elapsed_time_of_result);
                        if( $_result['elapsed_time'] > $_max_elapsed_time_of_result['elapsed_time'] ){
                            $_max_elapsed_time_of_result = $_result;
                        }
                    }
                }
            }else{
                $_result['bool'] = false;
                if(!$is_file_exists){
                    $this->deleteBySessionId($session_id);
                    $_result['syncMode'] = self::SYNCMODE_FILE_NOT_EXISTS;
                }
            }

            // handle the counters
            if($_result['bool'] == true){
                $results['sync_done_count']++;
            }else{
                switch($_result['syncMode']){
                    case self::SYNCMODE_INSERT:
                        $results['sync_insert_failed_count']++;
                        break;
                    case self::SYNCMODE_INSERT_CANCEL_BY_NO_PLAYER:
                        $results['sync_insert_cancel_by_no_player_count']++;
                        break;
                    case self::SYNCMODE_UPDATE:
                        $results['sync_update_done_count']++;
                        break;
                    case self::SYNCMODE_UPDATE_CANCEL:
                        $results['sync_update_cancel_count']++;
                        break;
                    case self::SYNCMODE_FILE_NOT_EXISTS:
                        $results['sync_file_not_exists_count']++;
                        break;
                    default:
                        $results['sync_unknown_failed_count']++;
                        break;
                }
            }
            if( ! empty($_result['session_id']) && $_result['session_id'] == 'add65e08093a32af6e17cb11d86c9999'){
                $results['dbg'][] = $_result;
            }
            // break;
            $this->utils->debug_log('cron4syncTableFromFiles.lines.index:', $index, 'total_count:', $results['total_count'] );
        }
        $executed_count = 0;
        $executed_count += $results['sync_done_count'];
        $executed_count += $results['sync_insert_failed_count'];
        $executed_count += $results['sync_insert_cancel_by_no_player_count'];
        $executed_count += $results['sync_update_done_count'];
        $executed_count += $results['sync_update_cancel_count'];
        $executed_count += $results['sync_file_not_exists_count'];
        $executed_count += $results['sync_unknown_failed_count'];

        $results['bool'] = ($executed_count == $results['total_count'])? true: false;
        $results['max_elapsed_time_result'] = $_max_elapsed_time_of_result;
        return $results;
    } // EOF cron4syncTableFromFiles

} // EOF Player_session_files_relay
