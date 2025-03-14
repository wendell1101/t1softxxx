<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Queue_result extends BaseModel {

	function __construct() {
		parent::__construct();
		$this->load->helper('string');
	}

	protected $tableName = "queue_results";

	const STATUS_NEW_JOB = 2;
	const STATUS_DONE = 3;
	const STATUS_READ = 4;
	const STATUS_ERROR = 5;
	const STATUS_STOPPED = 6;

	//1=admin, 2=player, 3=system
	const CALLER_TYPE_ADMIN = 1;
	const CALLER_TYPE_PLAYER = 2;
	const CALLER_TYPE_SYSTEM = 3;
	const CALLER_TYPE_AFFILIATE = 4;
	const CALLER_TYPE_AGENCY = 5;

	const SYSTEM_UNKNOWN = 0;

	const EVENT_DEBUG='EVENT_DEBUG';

	const EVENT_VIPSETTING_UPDATED_AFTER_DB_TRANS='EVENT_VIPSETTING_UPDATED_AFTER_DB_TRANS';
	const EVENT_DEPOSIT_AFTER_DB_TRANS='EVENT_DEPOSIT_AFTER_DB_TRANS';
	const EVENT_WITHDRAWAL_AFTER_DB_TRANS='EVENT_WITHDRAWAL_AFTER_DB_TRANS';
	const EVENT_WITHDRAW_CONDITION_BEFORE_CHECK='EVENT_WITHDRAW_CONDITION_BEFORE_CHECK';
	const EVENT_WITHDRAW_CONDITION_AFTER_CHECK='EVENT_WITHDRAW_CONDITION_AFTER_CHECK';
	const EVENT_TRANSFER_REQUEST_AFTER_DB_TRANS='EVENT_TRANSFER_REQUEST_AFTER_DB_TRANS';
	const EVENT_REGISTER_AFTER_DB_TRANS='EVENT_REGISTER_AFTER_DB_TRANS';
	const EVENT_GENERATE_COMMAND='EVENT_GENERATE_COMMAND';
    const EVENT_ON_GOT_MESSAGES='EVENT_ON_GOT_MESSAGES';
    const EVENT_ON_ADDED_NEW_MESSAGE='EVENT_ON_ADDED_NEW_MESSAGE';
    const EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READ='EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READ';
    const EVENT_ON_SENT_MESSAGE_FROM_ADMIN='EVENT_ON_SENT_MESSAGE_FROM_ADMIN';
    const EVENT_ON_GOT_PROFILE_VIA_API='EVENT_ON_GOT_PROFILE_VIA_API';


	const EVENT_SYNC_MDB='EVENT_SYNC_MDB';
	//do not auto finish
	const EVENT_EXPORT_CSV='EVENT_EXPORT_CSV';
	const EVENT_MONITOR_HEART_BEAT='EVENT_MONITOR_HEART_BEAT';
    const EVENT_AFTER_PLAYER_LOGIN='EVENT_AFTER_PLAYER_LOGIN';


	const AUTO_FINISH_EVENTS=[
		self::EVENT_DEBUG,
		self::EVENT_VIPSETTING_UPDATED_AFTER_DB_TRANS,
		self::EVENT_DEPOSIT_AFTER_DB_TRANS,
		self::EVENT_WITHDRAWAL_AFTER_DB_TRANS,
		self::EVENT_WITHDRAW_CONDITION_BEFORE_CHECK,
		self::EVENT_WITHDRAW_CONDITION_AFTER_CHECK,
		self::EVENT_TRANSFER_REQUEST_AFTER_DB_TRANS,
		self::EVENT_REGISTER_AFTER_DB_TRANS,
		self::EVENT_GENERATE_COMMAND,
		self::EVENT_SYNC_MDB,
		self::EVENT_AFTER_PLAYER_LOGIN,
        self::EVENT_ON_GOT_MESSAGES,
        self::EVENT_ON_ADDED_NEW_MESSAGE,
        self::EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READ,
        self::EVENT_ON_SENT_MESSAGE_FROM_ADMIN,
        self::EVENT_ON_GOT_PROFILE_VIA_API,
	];

	/**
	 *
	 * @param int systemId
	 * @param string funcName
	 * @param array params
	 * @param string callerType
	 * @param string caller
	 * @param string state
	 * @param integer $lang ref. to Language_function::getCurrentLanguage()
	 *
	 * @return string token
	 */
	public function newResult($systemId, $funcName, $params, $callerType, $caller, $state, $lang=null, $token=null) {
		if(empty($token)){
			$token = random_string('unique');
		}
		if(empty($lang)){
			$this->load->library(['language_function']);
			$lang=$this->language_function->getCurrentLanguage();
		}
		$_json_params = null;
		if($funcName == 'remote_processPreChecker'){
			$walletAccountId = $params['walletAccountId'];
			$_params = [];
			$_params['walletAccountId'] = $walletAccountId;
			$_json_params = json_encode($_params);
		}

		/// $token replace into @.event.data.command_params
		// for update the result into the queue form command.
		if( !empty($params['event']['data']['command_params']) ){
			$command_params = $params['event']['data']['command_params'];
			// replace '_replace_to_queue_token_' to real token string
			array_walk($command_params, function (&$value, $key) use ($token){
				if($value == '_replace_to_queue_token_'){
					$value = $token;
				}
			});
			$params['event']['data']['command_params'] = $command_params; // replace
		}


		$success=!!$this->insertData($this->tableName,
			[
				'system_id' => $systemId,
                'func_name' => $funcName,
				'params' => $_json_params, //json_encode($params), //The column is stop using. 20171124
                'token' => $token,
                'status' => self::STATUS_NEW_JOB,
				'created_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
				'caller_type' => $callerType,
                'caller' => $caller,
                'state' => $state,
				'full_params' => json_encode($params),
				'lang'=> $lang,
			]
		);
		if(!$success){
			$token=null;
		}
		return $token;
	}

	public function updateResult($token, $result) {
		$this->db->where('token', $token);
		return $this->db->update($this->tableName, array('result' => json_encode($result), 'status' => self::STATUS_DONE,
			'updated_at' => $this->utils->getNowForMysql()));

	}

	public function readResult($token) {
		$this->db->where('token', $token)->where('status', self::STATUS_DONE);
		return $this->db->update($this->tableName, array('status' => self::STATUS_READ,
			'updated_at' => $this->utils->getNowForMysql()));
	}

	public function unreadResult($token) {
		$this->db->where('token', $token)->where('status', self::STATUS_READ);
		return $this->db->update($this->tableName, array('status' => self::STATUS_DONE,
			'updated_at' => $this->utils->getNowForMysql()));
	}

	public function failedResult($token, $result = null) {
		$this->db->where('token', $token)->where('status', self::STATUS_NEW_JOB);
		return $this->db->update($this->tableName, array('result' => json_encode($result), 'status' => self::STATUS_ERROR,
			'updated_at' => $this->utils->getNowForMysql()));
	}

	public function updateResultRunning($token, $result,$state=null) {
		$this->db->where('token', $token);
		return $this->db->update($this->tableName, array('result' => json_encode($result), 'status' => self::STATUS_NEW_JOB,
			'updated_at' => $this->utils->getNowForMysql(), 'state' => json_encode($state)));

	}

	public function updateResultWithCustomStatus($token, $result, $done = false, $error = false) {

		$this->db->set(['result' => json_encode($result)]);
		$this->db->set(['updated_at' => $this->utils->getNowForMysql()]);

		if($error){
			$this->db->set(['status' => self::STATUS_ERROR]);
		}elseif($done){
			$this->db->set(['status' => self::STATUS_DONE]);
		}

		$this->db->where('token', $token);
		return $this->runAnyUpdate('queue_results');
	}

	public function updateResultStopped($token) {
		$this->db->where('token', $token);
		return $this->db->update($this->tableName, array('status' => self::STATUS_STOPPED,
			'updated_at' => $this->utils->getNowForMysql()));

	}


	public function updateToReadByCaller($callerType, $caller) {
		if ($callerType && $caller) {
			$this->db->where('caller_type', $callerType)->where('caller', $caller)->where('status', self::STATUS_DONE);
			$this->db->update($this->tableName, array('status' => self::STATUS_READ));
			return true;
		}
		return false;
	}



	/**
	 * get the Result List by func_name and full_params
	 *
	 * @param string $func_name remote_processPreChecker
	 * @param string $full_params The where condition with like.
	 * For example, if need search  {"walletAccountId":350551} , then %walletAccountId%:350551}
	 * @param array $created_at_range The created_at range append into where condition.
	 * - $created_at_range[0] string The begin datetime for created_at field, ex:"2021-03-17 09:10:11"
	 * - $created_at_range[0] string The end datetime for created_at field, ex:"2021-03-17 09:10:11"
	 * @param array $order_by The list sort type,
	 * - $order_by['field'] string The field name for sort.
	 * - $order_by['by'] string There are only two values, ASC or DESC.
	 * @param string $params  The where condition with like.
	 * @param string|array $like_side The wildcard of like function in sql query,
	 * ex: none, before, after and both.
	 * for: like 'something', like '%something', like 'something%' and like '%something%'.
	 * If the param type is array, the key-vale should be the following,
	 * - $like_side[full_params] string The value will be applied for $full_params.
	 * - $like_side[params] string The value will be applied for $params.
	 * @return array
	 */
	public function getResultListByFuncNameAndFullParamsOrParams($func_name, $full_params=null, $result = null, $created_at_range = null, $order_by = null, $params = null, $like_side = 'both'){
		$rows = [];
		if ( ! empty($func_name)
			&& ( ! empty($full_params) || ! empty($params))
		){
			$this->db->select('token,func_name,params,full_params,result,status,created_at,updated_at');
			$this->db->where('func_name', $func_name);
			$this->db->from($this->tableName);

			if( is_string($like_side) ){
				$full_params_like_side = $like_side;
				$params_like_side = $like_side;
			}else if( is_array($like_side) ){
				$full_params_like_side = $like_side['full_params'];
				$params_like_side = $like_side['params'];
			}

			if( ! empty($full_params) ){
				$this->db->like('full_params', $full_params, $full_params_like_side);
			}
			if( ! empty($params) ){
				$this->db->like('params', $params, $params_like_side);
			}

			if( ! empty($result) ){
				if( strtolower($result) == 'null' ){
					$this->db->where('result IS NULL');
				}else{
					$this->db->like('result', $result);
				}
			}

			if( ! empty($created_at_range) ){
				$whereCreatedAtBeginStr = sprintf('created_at >= "%s"', $created_at_range[0]);
				$whereCreatedAtEndStr = sprintf('created_at <= "%s"', $created_at_range[1]);
				$this->db->where($whereCreatedAtBeginStr);
				$this->db->where($whereCreatedAtEndStr);
			}

			if( ! empty($order_by) ){
				if( ! empty($order_by['field']) && ! empty($order_by['by']) ){
					$this->db->order_by($order_by['field'], $order_by['by']);
				}else if( ! empty($order_by['field']) ){
					$this->db->order_by($order_by['field']);
				}
			}
			$rows = $this->runMultipleRowArray($this->db);
			// $last_query = $this->db->last_query();
			// $this->utils->debug_log('getResultListByFunc_nameAndFull_params.last_query:', $last_query);
		}
		return $rows;
	}// EOF getResultListByFunc_nameAndFull_params

	/**
	 * for done job, unread or both
	 *
	 */
	public function getResultListByCaller($callerType, $caller, $onlyUnread = true, $updateToRead = false) {
		if ($callerType && $caller) {
			$this->db->select('token,system_id,func_name,params,result,status,created_at,updated_at');
			$this->db->where('caller_type', $callerType)->where('caller', $caller);
			if ($onlyUnread) {
				$this->db->where('status', self::STATUS_DONE);
			} else {
				$this->db->where_in('status', array(self::STATUS_DONE, self::STATUS_READ));
			}
			$qry = $this->db->get($this->tableName);
			if ($qry) {
				if ($updateToRead) {
					$this->updateToReadByCaller($callerType, $caller);
				}
				return $qry->result();
			}

		}
		return null;

	}

	/**
	 * Get an array-row from queue_results by token
	 *
	 * @param string $token
	 * @param CI_DB_driver $db
	 * @return array A key-valye array-row.
	 */
	public function getResult($token, $db=null) {
		if(empty($db) || !is_object($db)){
            $db=$this->db;
        }
		$db->from('queue_results')->where('token', $token);
		$row = $this->runOneRowArray($db);
		return $row;
	} // EOF getResult

	public function appendResult($token, $result, $done=false, $error=false) {

		$q=$this->getResult($token);
		if(empty($q)){
			return false;
		}

		$rlt=$q['result'];
		if(!empty($rlt)){
			$rlt=$this->utils->decodeJson($rlt);
		}
		if(empty($rlt)){
			$rlt=[];
		}
		if(!is_array($rlt)){
			$rlt=[$rlt];
		}

		$rlt[]=$result;

		// $this->utils->debug_log('append to', $token, $rlt);

		$this->db->where('id', $q['id'])->set(['result' => json_encode($rlt), 'updated_at' => $this->utils->getNowForMysql()]);

		if($error){
			$this->db->set(['status' => self::STATUS_ERROR]);
		}elseif($done){
			$this->db->set(['status' => self::STATUS_DONE]);
		}

		return $this->runAnyUpdate('queue_results');
	}

	public function runTaskAgain($oldResult, $oldToken){

		$token = random_string('unique');

		$_params = [];
		$_params['old_token'] = $oldToken;

		if($oldResult['func_name'] == 'remote_processPreChecker') {
			$oldTask_full_params = json_decode($oldResult['full_params'], true);
			$_params['walletAccountId'] = $oldTask_full_params['walletAccountId'];
		}
		$_json_params = json_encode($_params);

		$success=!!$this->insertData($this->tableName,
			[
				'system_id' => $oldResult['system_id'],
                'func_name' => $oldResult['func_name'],
				'params' => $_json_params, //json_encode($params), //The column is stop using. 20171124
                'token' => $token,
                'status' => self::STATUS_NEW_JOB,
				'created_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
				'caller_type' => $oldResult['caller_type'],
                'caller' => $oldResult['caller'],
                'state' => $oldResult['state'],
				'full_params' => $oldResult['full_params'],
				'lang'=> $oldResult['lang'],
			]
		);

		if(!$success){
			$token=null;
		}

		return $token;

	}

	/**
	 * update final result
	 * @param  string  $token
	 * @param  boolean  $success
	 * @param  string $message
	 * @param  int $progress
	 * @param  int $total
	 * @param  boolean $done
	 * @param  array $extra
	 * @return boolean $success
	 */
	public function updateFinalResult($token, $success, $message,
			$progress, $total, $done, $download_filelink=null, array $extra=[]){

		$this->db->select('id')->from('queue_results')->where('token', $token);
		$id=$this->runOneRowOneField('id');
		if(empty($id)){
			$this->utils->error_log('cannot find queue job by token:'.$token);
			return false;
		}
		$error=!$success;
		$finalResult=[
			'success'=>$success,
			'progress'=>$progress,
			'total'=>$total,
			'download_filelink'=>$download_filelink,
			'extra'=>$extra,
		];
		$this->db->where('id', $id)->set(['final_result' => json_encode($finalResult),
			'updated_at' => $this->utils->getNowForMysql()]);

		if($error){
			$this->db->set(['status' => self::STATUS_ERROR]);
		}elseif($done){
			$this->db->set(['status' => self::STATUS_DONE]);
		}

		return $this->runAnyUpdate('queue_results');
	}

	public function getIdByToken($token){
		$this->db->select('id')->from('queue_results')->where('token', $token);
		return $this->runOneRowOneField('id');
	}

	public function getFinalResultById($id){
		$this->db->select('final_result')->from('queue_results')->where('id', $id);
		$finalResult =$this->runOneRowOneField('final_result');
		if(!empty($finalResult)){
			$finalResult=$this->utils->decodeJson($finalResult);
		}

		return $finalResult;
	}

	public function updateLogFileByToken($token, $logFile){
		$this->db->select('id, full_params')->from('queue_results')->where('token', $token);
		// $id=$this->runOneRowOneField('id');
		$row=$this->runOneRowArray();
		if(empty($row)){
			$this->utils->error_log('cannot find queue job by token:'.$token);
			return false;
		}
		$id=$row['id'];
		$fullParams=json_decode($row['full_params'], true);
		if(empty($fullParams)){
			$fullParams=[];
		}
		$fullParams['_log_file']=$logFile;

		$this->db->where('id', $id)->set(['full_params' => json_encode($fullParams),
			'updated_at' => $this->utils->getNowForMysql()]);

		return $this->runAnyUpdate('queue_results');
	}

}

///END OF FILE///////
