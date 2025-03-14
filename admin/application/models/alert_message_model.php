<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * save alert message for all admin users
 *
 */
class Alert_message_model extends BaseModel {

	protected $tableName = 'alert_messages';

	protected $idField = 'id';

	const ALERT_TYPE_T1T_BROADCAST = 1;
	const ALERT_TYPE_SUSPICIOUS_PLAYER_LOGIN = 2;
	const ALERT_TYPE_SUSPICIOUS_TRANSFER_OUT = 3;
	const ALERT_TYPE_BIG_TRANSFER_OUT = 4;

	const FROM_TYPE_T1T=1;
	const FROM_TYPE_CRONJOB=2;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * saveAlert
	 * @param  int $alertType
	 * @param  int $fromType
	 * @param  string $message
	 * @param  array $context
	 * @param  int $playerId
	 * @return int id
	 */
	public function saveAlert($fromType, $alertType, $message, $context, $playerId=null){
		//save to alert_messages
		$data=[
			'alert_type'=>$alertType,
			'context'=>$this->utils->encodeJson($context),
			'from_type'=>$fromType,
			'player_id'=>$playerId,
			'message'=>$message,
			'created_at'=>$this->utils->getNowForMysql(),
		];
		return $this->runInsertData($this->tableName, $data);
	}

	/**
	 * sendAllToMattermost
	 * @param  array $idList, array of id
	 * @param  string $channel
	 * @return boolean
	 */
	public function sendAllToMattermost($idList, $channel=null){
		$success=true;
		$this->db->select('message')->from($this->tableName)->where_in('id', $idList);
		$rows=$this->runMultipleRowArray();
		if(!empty($rows)){
			$message='';
			foreach ($rows as $row) {
				$message.=$row['message']."\n";
			}
			if(empty($channel)){
				$channel=$this->utils->getConfig('common_channel_for_alert_message');
			}
			$title=null;
			$success=$this->utils->sendMessageToMattermostChannel($channel, 'warning', $title, $message);
		}else{
			$this->utils->debug_log('no any alert message');
		}

		return $success;
	}

}
