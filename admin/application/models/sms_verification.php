<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * SMS Verification
 */
class Sms_verification extends BaseModel {
	protected $tableName = 'sms_verification';
	private $sms_valid_duration = 86400;
	private $smsValidTime;
	private $passwordRecoveryValidTime;

	private $restrictArea = [
		'sms_api_security_setting', // player center password verification
	];

	const USAGE_DEFAULT						= 'default';
	const USAGE_COMAPI_MOBILE_REG			= 'comapi_mobile_reg';
	const USAGE_COMAPI_SMS_VALIDATE 		= 'comapi_sms_validate';
	const USAGE_COMAPI_PASSWORD_RECOVERY	= 'comapi_password_recovery';
	const USAGE_NEW_PLAYERAPI_PASSWORD_RECOVERY = 'player_api_password_recovery';
	const USAGE_NEW_PLAYERAPI_CHANGE_PASSWORD = 'player_api_change_password';
	const USAGE_PASSWORD_RECOVERY			= 'password_recovery';//Equivalent to USAGE_SMSAPI_FORGOTPASSWORD

	const USAGE_SMSAPI_REGISTER 			= 'sms_api_register_setting';
	const USAGE_SMSAPI_LOGIN 				= 'sms_api_login_setting';
	const USAGE_SMSAPI_BANKINFO 			= 'sms_api_bankinfo_setting';
	const USAGE_SMSAPI_SENDMESSAGE 			= 'sms_api_sendmessage_setting';
	const USAGE_SMSAPI_FORGOTPASSWORD 		= 'sms_api_forgotpassword_setting';
	const USAGE_SMSAPI_SECURITY 			= 'sms_api_security_setting';
	const USAGE_SMSAPI_ACCOUNTINFO 			= 'sms_api_accountinfo_setting';

	const SESSION_ID_DEFAULT		= '0badface';

	const DAYS_OLD_SMS_EXPIRY		= 30;

	function __construct() {
		parent::__construct();
		// $this->smsValidTime = strtotime('-'.$this->config->item('sms_valid_time').' minutes');
		$sms_valid_duration_config = (int) $this->config->item('sms_valid_time');
		$this->sms_valid_duration = empty($sms_valid_duration_config) ? $this->sms_valid_duration : $sms_valid_duration_config;
		$this->smsValidTime = time() - $this->sms_valid_duration;
		$password_recovery_valid_duration_config = (int) $this->config->item('password_reset_code_expire_mins');
		$this->password_recovery_valid_duration = empty($password_recovery_valid_duration_config) ? $this->password_recovery_valid_duration : $password_recovery_valid_duration_config;
		$this->passwordRecoveryValidTime = time() - ($this->password_recovery_valid_duration)*60;
	}

	# Returns the number of verification codes generated during the past minute
	public function getVerificationCodeCountPastMinute() {
		$this->db->where('create_time > ', date('Y-m-d H:i:s', time() - 60));
		$this->db->where('is_reset',self::DB_FALSE);
		$query = $this->db->get($this->tableName);
		$this->utils->debug_log($this->db->last_query());
		return $query->num_rows();
	}

	// public function getVerificationCode($playerId, $sessionId, $mobileNumber, $restrictArea = null) {
	// 	$code = mt_rand(100000, 999999);	# a random 6-digit number
	// 	$entry = array(
	// 		'session_id' => $sessionId,
	// 		'mobile_number' => $mobileNumber,
	// 		'code' => $code,
	// 		'verified' => 0,
	// 		'ip' => $this->utils->getIP(),
	// 		'playerId' => ($playerId) ? $playerId : null
	// 	);

	// 	if (!empty($restrictArea) && $this->isRestrictArea($restrictArea)) {
	// 		$entry['restrict_area'] = $restrictArea;
	// 	}

	// 	$this->db->set('create_time', 'NOW()', FALSE);
	// 	$this->db->insert($this->tableName, $entry);
	// 	return $code;
	// }

	# Returns true if the validation is successful, and code is validated
	// public function validateVerificationCode($playerId, $sessionId, $mobileNumber, $code, $restrictArea = null) {
	// 	$this->utils->debug_log("========= smsValidTime ============",date('Y-m-d H:i:s', $this->smsValidTime));

	// 	$recode = $this->gettLastVcode($playerId, $sessionId, $mobileNumber, $restrictArea);

	// 	if ($recode && ($code == $recode['code'])) {
	// 		$this->db->where('id', $recode['id']);
	// 		$this->db->update($this->tableName, array('verified' => 1));
	// 		return $this->db->affected_rows() > 0;
	// 	}
	// 	return false;
	// }

	/**
	 * Get first unvalidated verification code for given session and number.  If not available, generate one.
	 * Vulnerability: order for the query is not given.  But not sure about the usage scenario.
	 * @param	string	$sessionId		The session ID
	 * @param	numeric	$mobileNumber	The phone number
	 * @param	int		$usage 			Usage, see defined USAGE_* constants above
	 * @return	string	The verification in effect now
	 */
	public function getVerificationCode($playerId, $sessionId, $mobileNumber, $usage = self::USAGE_DEFAULT) {
		# Query whether there is an un-verified verification code for this session created within an hour
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		$this->db->where('session_id', $sessionId);
		$this->db->where('mobile_number', $mobileNumber);
		$this->db->where('usage', $usage);
		if($usage == self::USAGE_PASSWORD_RECOVERY || $usage == self::USAGE_SMSAPI_FORGOTPASSWORD){
			$passwordRecoveryValidTime = time() - ($this->password_recovery_valid_duration)*60;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $passwordRecoveryValidTime));
		}else{
			$smsValidTime = time() - $this->sms_valid_duration;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime));
		}
		$this->db->where('verified', 0);
		$query = $this->db->get($this->tableName);

		if ($query->num_rows() > 0) {
			$row = $query->row();
			return $row->code;
		}
		else {
			return $this->createVerificationCode($sessionId, $mobileNumber, $playerId, $usage);
		}
	}

	/**
	 * Compare SMS Verification Code
	 *
	 * @param integer $playerId
	 * @param string $sessionId
	 * @param integer $mobileNumber
	 * @param string $code
	 * @return void
	 */
	public function compareSmsVerificationCode($playerId = null, $sessionId, $mobileNumber, $code) {
		$return = [];
		$return['boolean'] = false;
		$return['message'] = 'Not found.';
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		if (empty($sessionId)) { $sessionId = self::SESSION_ID_DEFAULT; }
		if($sessionId != self::SESSION_ID_DEFAULT) {
			$this->db->where('session_id', $sessionId);
		}
		$this->db->where('mobile_number', $mobileNumber);

		if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
			$usage = self::USAGE_SMSAPI_REGISTER;
		}else{
			$usage = self::USAGE_DEFAULT;
		}

		$this->db->where('usage', $usage);

		$smsValidTime = time() - $this->sms_valid_duration;
		$this->db->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime));
		$this->db->where('code', $code);
		$this->db->where('verified', self::DB_FALSE);

		$query = $this->db->get($this->tableName);
		$row = $this->getOneRowArray($query);
		if( !empty($row) ){
			if($row['code'] == $code){
				$return['boolean'] = true;
				$return['message'] = 'Match';
			}else{
				$return['boolean'] = false;
				$return['message'] = 'Does not match';
			}
		}
		return $return;
	} // EOF compareSmsVerificationCode

	/**
	 * Attempt to verify the code by given code/session/mobile number.
	 * Sets verified = 1 for the row that matches given code/session/mobile number tuple, or do nothing if no row matches.
	 * @param	string	$sessionId	The session ID
	 * @param	numeric	$mobileNumber	The phone number
	 * @param	string	$code		The validation code
	 * @param	int		$playerId	== player.playerId
	 * @param	int		$usage 		Usage, see defined USAGE_* constants above.
	 *
	 * @return	bool	true if one or more matching codes are verified, false if no successful verification.
	 */
	public function validateVerificationCode($playerId = null, $sessionId, $mobileNumber, $code, $usage = self::USAGE_DEFAULT) {
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		if (empty($sessionId)) { $sessionId = self::SESSION_ID_DEFAULT; }
		if (empty($usage)) { $usage = self::USAGE_DEFAULT; }
		if($sessionId != self::SESSION_ID_DEFAULT) {
			$this->db->where('session_id', $sessionId);
		}
		$this->db->where('mobile_number', $mobileNumber);
		$this->db->where('usage', $usage);
		if($usage == self::USAGE_PASSWORD_RECOVERY || $usage == self::USAGE_SMSAPI_FORGOTPASSWORD){
			$passwordRecoveryValidTime = time() - ($this->password_recovery_valid_duration)*60;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $passwordRecoveryValidTime));
		}else{
			$smsValidTime = time() - $this->sms_valid_duration;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime));
		}
		$this->db->where('code', $code);
		$this->db->update($this->tableName, array('verified' => 1));
		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $this->db->affected_rows() > 0;
	}

	public function validateVerificationCode_2($playerId = null, $sessionId, $mobileNumber, $code, $usage = self::USAGE_DEFAULT) {
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		if (empty($sessionId)) { $sessionId = self::SESSION_ID_DEFAULT; }

		$code_stat = $this->verificationCodeStatusDebug($playerId, $sessionId, $mobileNumber, $code, $usage);
		$this->utils->debug_log(__METHOD__, 'before verification', $code_stat);

		if($sessionId != self::SESSION_ID_DEFAULT) {
			$this->db->where('session_id', $sessionId);
		}
		$this->db->where('mobile_number', $mobileNumber);
		$this->db->where('usage', $usage);
		if($usage == self::USAGE_PASSWORD_RECOVERY || $usage == self::USAGE_SMSAPI_FORGOTPASSWORD){
			$passwordRecoveryValidTime = time() - ($this->password_recovery_valid_duration)*60;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $passwordRecoveryValidTime));
		}else{
			$smsValidTime = time() - $this->sms_valid_duration;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime));
		}
		$this->db->where('code', $code);
		$this->db->update($this->tableName, array('verified' => 1));
		// $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
		$res_update = $this->db->affected_rows() > 0;

		$code_stat = $this->verificationCodeStatusDebug($playerId, $sessionId, $mobileNumber, $code, $usage);
		$this->utils->debug_log(__METHOD__, 'after verification', $code_stat);

		return $res_update;
	}

	public function verificationCodeStatusDebug($playerId = null, $session_id, $mobile_number, $code, $usage = self::USAGE_DEFAULT) {
		$this->db->from($this->tableName)
			->select('*')
			->where('mobile_number', $mobile_number)
			->where('code', $code)
			->where('usage', $usage)
		;
		if ($session_id != self::SESSION_ID_DEFAULT) {
			$this->db->where('session_id', $session_id);
		}
		if (!empty($playerId)) {
			$this->db->where('playerId', $playerId);
		}

		$res = $this->runMultipleRowArray();

		// $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $res;
	}

	/**
	 * Attempt to verify the code by given code/session/mobile number.
	 * if form validate failed and check sms verified status = 1, will update verified status to 0
	 * @param	string	$sessionId	The session ID
	 * @param	numeric	$mobileNumber	The phone number
	 * @param	string	$code		The validation code
	 * @param	int		$playerId	== player.playerId
	 * @param	int		$usage 		Usage, see defined USAGE_* constants above.
	 *
	 * @return	bool	true if one or more matching codes are verified, false if no successful verification.
	 */
	public function validateSmsVerifiedStatus($playerId = null, $sessionId, $mobileNumber, $code, $usage = self::USAGE_DEFAULT) {
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		if (empty($sessionId)) { $sessionId = self::SESSION_ID_DEFAULT; }
		if($sessionId != self::SESSION_ID_DEFAULT) {
			$this->db->where('session_id', $sessionId);
		}
		$this->db->where('mobile_number', $mobileNumber);
		$this->db->where('usage', $usage);
		if($usage == self::USAGE_PASSWORD_RECOVERY || $usage == self::USAGE_SMSAPI_FORGOTPASSWORD){
			$passwordRecoveryValidTime = time() - ($this->password_recovery_valid_duration)*60;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $passwordRecoveryValidTime));
		}else{
			$smsValidTime = time() - $this->sms_valid_duration;
			$this->db->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime));
		}
		$this->db->where('code', $code);
		$this->db->where('verified', 1);
		$this->db->update($this->tableName, array('verified' => 0));

		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $this->db->affected_rows() > 0;
	}

	/**
	 * Returns number of un-validated codes for given session/mobile number/player
	 * @param	string	$sessionId	The session ID
	 * @param	int		$playerId	== player.playerId
	 * @param	numeric	$mobileNumber	The phone number
	 * @param	int		$usage 		Usage, see defined USAGE_* constants above.
	 *
	 * @return	int		Count of pending validation codes
	 */
	public function playerValidationStatus($sessionId, $mobileNumber, $playerId = null, $usage = self::USAGE_DEFAULT) {
		if (!empty($playerId)) { $this->db->where('playerId', $playerId); }
		if (empty($sessionId)) { $sessionId = self::SESSION_ID_DEFAULT; }
		$smsValidTime = time() - $this->sms_valid_duration;
		$this->db	//->from($this->table)
			->where('session_id', $sessionId)
			->where('mobile_number', $mobileNumber)
			->where('usage', $usage)
			->where('create_time > ', date('Y-m-d H:i:s', $smsValidTime))
			->where('verified', 0)
		;

		$res = $this->db->count_all_results($this->tableName);

		$this->utils->debug_log(__METHOD__, 'valid sql', $this->db->last_query());

		$this->utils->debug_log(__METHOD__, 'result', $res);

		return intval($res);
	}

	/**
	 * Creates verification code for given session/mobile number tuple.
	 * @param	string	$sessionId	The session ID
	 * @param	numeric	$mobileNumber	The phone number
	 * @param	int		$usage 		Usage, see defined USAGE_* constants above.
	 * @return	string	The just-generated validation code.
	 */
	private function createVerificationCode($sessionId, $mobileNumber, $playerId = null, $usage = self::USAGE_DEFAULT) {
		$code = mt_rand(100000, 999999);	# a random 6-digit number
		$entry = array(
			'session_id'	=> $sessionId,
			'playerId'		=> $playerId,
			'mobile_number'	=> $mobileNumber,
			'code'			=> $code,
			'verified'		=> 0,
			'ip'			=> $this->utils->getIP(),
			'usage'			=> $usage
		);

		$this->db->set('create_time', $this->utils->getNowForMysql());
		$this->db->insert($this->tableName, $entry);
		return $code;
	}

	private function gettLastVcode($playerId, $sessionId, $mobileNumber, $restrictArea = null) {
		$smsValidTime = time() - $this->sms_valid_duration;
		$condition = [
			"session_id" => $sessionId,
			"mobile_number" => $mobileNumber,
			"create_time >" => date('Y-m-d H:i:s', $smsValidTime),
			"playerId" => ($playerId) ? $playerId : null
		];

		$this->db->order_by("id", "desc");
		$qry = $this->db->get_where($this->tableName, $condition);
		$res = $qry->row_array();

		return $res;
	}

	# Returns a list of verification codes sent, joined with the player info
	# This join is 1-1 if player contactNumber is unique
	public function listVerificationCodes($request,$is_export = false) {
		$this->load->library('data_tables');

		$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');

		$loggedUserId = $this->authentication->getUserId();
        $user = $this->users->getUserById($loggedUserId);
        $allow_checking_verification_code = $this->users->isAuthorizedauthorizedViewVerificationCode($user['username'], 'authorized_view_sms_verification_code');

		$usage = !empty($use_new_sms_api_setting) ? self::USAGE_SMSAPI_FORGOTPASSWORD : self::USAGE_PASSWORD_RECOVERY;
		$i = 0;
		$columns = array(
			array(
				'alias' => 'username',
				'select' => 'player.username',
				'name' => lang('Username'),
				'dt' => $i++,
			),
			array(
				'alias' => 'session_id',
				'select' => $this->tableName.".session_id",
				'name' => lang('Session'),
				'dt' => $i++,
			),
			array(
				'alias' => 'mobile_number',
				'select' => $this->tableName.".mobile_number",
				'name' => lang('Mobile Number'),
				'dt' => $i++,
				'formatter'=> function($d){
                    if($this->utils->isEnabledFeature('display_all_numbers_of_mobile')){
                        return $d;
                    }
                        return $this->utils->keepOnlyString($d, 6);
                }
			),
			array(
				'alias' => 'code',
				'select' => $this->tableName.".code",
				'name' => lang('Verification Code'),
				'dt' => $i++,
				'formatter' => function($d) use ($allow_checking_verification_code){
					if(!$allow_checking_verification_code){
						return '******';
					}
					return $d;
				}
			),
			array(
				'alias' => 'verified',
				'select' => $this->tableName.".verified",
				'name' => lang('Verified'),
				'dt' => $i++,
			),
			array(
				'alias' => 'create_time',
				'select' => $this->tableName.".create_time",
				'name' => lang('Created'),
				'dt' => $i++,
			),
			array(
				'alias' => 'timeout',
				'select' => 'case '.$this->tableName.'.usage when "'.$usage.'" then DATE_ADD('.$this->tableName.'.create_time, INTERVAL '.$this->config->item('password_reset_code_expire_mins').' minute) else '.'DATE_ADD('.$this->tableName.'.create_time, INTERVAL '.$this->config->item('sms_valid_time').' second) end',
				'name' => lang('Timeout'),
				'dt' => $i++,
			)
		);
		$input = $this->data_tables->extra_search($request);
		$where = array("mobile_number <> ''");
		$values = array();
		$joins = array();
		$joins['playerdetails'] = ' '.$this->tableName.'.mobile_number = playerdetails.contactNumber';
		$joins['player'] = 'playerdetails.playerId = player.playerId';
		$group_by = array();
		$having = array();

		if (isset($input['date_from'], $input['date_to'])) {
			$where[] = "create_time BETWEEN ? AND ?";
			$values[] = $input['date_from'];
			$values[] = $input['date_to'];
		}

		if (isset($input['username'])) {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['username'] . '%';
		}

		if (isset($input['mobileNumber'])) {
			$where[] = $this->tableName.'.mobile_number LIKE ?';
			$values[] = '%' . $input['mobileNumber'] . '%';
		}

		return $this->data_tables->get_data($request,$columns,$this->tableName,$where,$values,$joins,$group_by,$having,false);
	}

	public function isRestrictArea($num) {
		return in_array($num, $this->restrictArea);
	}

	public function sendNumDaily($playerId, $restrictArea) {
		$condition = [
			'playerId' => $playerId,
			'create_time >=' => date("Y-m-d 00:00:00"),
			'create_time <=' => date("Y-m-d 23:59:59"),
			'restrict_area' => $restrictArea
		];

		$this->db->where($condition);
		$query = $this->db->get($this->tableName);
		return $query->num_rows();
	}

	public function getTodaySMSCountFor($mobileNumber) {
		$condition = [
			'create_time >=' => date("Y-m-d 00:00:00"),
			'create_time <=' => date("Y-m-d 23:59:59"),
			'mobile_number' => $mobileNumber,
			'is_reset' => self::DB_FALSE
		];

		$this->db->where($condition);
		$query = $this->db->get($this->tableName);
		return $query->num_rows();
	}

	public function checkIPAndMobileLastTime($smsCooldownTime, $mobileNumber) {
		$currentIP = $this->db->escape($this->utils->getIP());
		$mobileNumber = $this->db->escape($mobileNumber);
		$this->db->where("(ip = $currentIP OR mobile_number = $mobileNumber)");
		$this->db->where('create_time > ', date('Y-m-d H:i:s', time() - $smsCooldownTime));
		$qry = $this->db->get($this->tableName);
		return ($qry->num_rows()) ? true : false;
	}

	/**
	 * Delete expired SMS message.  Expiry time determined by class constant.
	 * Used by Command::clearTimeoutSms().  OGP-12075.
	 * @see		Sms_verification::DAYS_OLD_SMS_EXPIRY
	 * @see		Command::clearTimeoutSms()
	 * @return	array 	Combined message for deletion operation
	 */
	public function deleteTimeoutSms() {
		$days_old_sms_expiry = self::DAYS_OLD_SMS_EXPIRY;
		$offset_days = "-{$days_old_sms_expiry} days";
		$date_before = date('c', strtotime( $offset_days ));
		$this->db->where('create_time <=', $date_before)
			->delete($this->tableName);
		$rows_deleted = $this->db->affected_rows();
		$mesg = "Keeping only SMS in last {$days_old_sms_expiry} days.  Deleted {$rows_deleted} rows before {$date_before} from sms_verification.";

		return $mesg;
	}

	public function resetSMSVerification($playerId) {

		$condition = [
			'create_time >=' => date("Y-m-d 00:00:00"),
			'create_time <=' => date("Y-m-d 23:59:59"),
			'playerId' => $playerId,
		];

		$this->db->set('is_reset', self::TRUE);
		$this->db->where($condition);

		return $this->runAnyUpdateWithResult($this->tableName);
	}

	public function addSendSmsRecord($smsRecordData){
		$this->db->insert('sms_api_sending_record', $smsRecordData);
	}

	public function getAvailableRotationSmsApi($params, $apiName){
		$this->utils->debug_log(__METHOD__, 'params',$params, $apiName);

		$period 	= !empty($params['period']) ? $params['period']: 'minutes';
		$check_time = !empty($params['check_time']) ? $params['check_time']: 0;
		$date_base  = !empty($params['date_base']) ? date('Y-m-d', strtotime($params['date_base'])) : $this->utils->getNowForMysql();
        $date_from  = date('Y-m-d H:i:s', strtotime("{$date_base} -{$check_time} {$period}"));
        $date_to    = $date_base;
        $playerId   = $params['playerId'];
        $recipientNumber = $params['mobileNumber'];

        if (preg_match("/\|/", $recipientNumber)) {
			$convertRecipientNumber = explode('|',$recipientNumber);
            $dialingCode = $convertRecipientNumber[0];
            $mobileNumber = $convertRecipientNumber[1];
		}
		else{
			$mobileNumber = $recipientNumber;
			$dialingCode = null;
		}

		$this->db->select('sms_api_sending_record.*')
			->from('sms_api_sending_record')
			->where('contactNumber', $mobileNumber)
			->where('createTime >= ', $date_from)
			->where('createTime <= ', $date_to)
			->where('smsApiName', $apiName);

		if (!empty($playerId)) {
			$this->db->where('playerId', $playerId);
		}

		if (!empty($params['times'])) {
			$this->db->group_by('contactNumber');
			$this->db->having('count(id) >= '.$params['times']);
		}

		$res = $this->runMultipleRowArray();

		$this->utils->debug_log(__METHOD__, '--------sql', $this->db->last_query());

		return $res;
	}
}
