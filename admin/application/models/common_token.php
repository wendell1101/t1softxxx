<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * player_id
 * admin_user_id
 * affiliate_id
 * agent_id
 * token
 * timout
 * created_at
 * updated_at
 * status
 *
 */
class Common_token extends BaseModel {

	function __construct() {
		parent::__construct();
		$this->load->helper('string');
	}

	protected $tableName = "common_tokens";

	const BACKEND_USER_ID=-1;

	/**
	 * get player id token
	 *
	 * @param 	int playerId
	 * @return 	int last insert id
	 */
	public function getPlayerToken($playerId, $timeout = null) {
		if(empty($playerId)){
			return null;
		}
		//check available token
		// $token = $this->getAvailableToken($playerId, 'player_id');
		// if (empty($token)) {
		// 	$token = $this->createTokenBy($playerId, 'player_id');
		// }

		list($token, $sign_key) = $this->getAvailableTokenWithSignKey($playerId, 'player_id');
		if (empty($token) || empty($sign_key)) {
			list($token, $sign_key) = $this->createTokenWithSignKeyBy($playerId, 'player_id', $timeout);
		}

		// $this->utils->debug_log('get player token ', $playerId, $token);
		$this->utils->debug_log('player token/sign_key', [ 'playerId' => $playerId, 'token' => $token, 'sign_key' => $sign_key ]);
		return $token;
	}

	/**
	 * Get Player Token by game username
	 *
	 * @param 	string $gameUsername
	 * @return  string $token
	 */
	public function getPlayerCommonTokenByGameUsername($gameUsername) {
		if(empty($gameUsername)){
			return null;
		}
		$playerId = null;
		list($token, $sign_key) = $this->getAvailableTokenWithSignKeyUsingGameUsername($gameUsername,$playerId);
		if (empty($token) || empty($sign_key)) {
			list($token, $sign_key) = $this->createTokenWithSignKeyBy($playerId, 'player_id');
		}

		$this->utils->debug_log('player token/sign_key', [ 'gameUsername' => $gameUsername, 'token' => $token, 'sign_key' => $sign_key,'playerId',$playerId]);
		return $token;
	}

////////////////////////////////////////////////
	public function getValidPlayerToken($playerId) {
		//check available token
		$token = $this->getAvailableToken($playerId, 'player_id');

		return $token;
	}

	public function isTokenValid($playerId, $token){

		$this->db->select('COUNT(id) as count');
		$this->db->from($this->tableName);
		$this->db->where('player_id', $playerId);
		$this->db->where('token', $token);
		$this->db->where('status', self::STATUS_NORMAL);
		$this->db->where('timeout_at >=', $this->getNowForMysql());
		$count = $this->runOneRowOneField('count');
		$result = $count > 0;

		// $this->utils->printLastSQL();

		if($result){
			$this->updatePlayerToken($playerId, $token);
		}

		return $result;
	}

	public function updatePlayerToken($playerId, $token, $timeout = null){
		$timeout = $timeout ?: $this->utils->getConfig('token_timeout');
		$d = new DateTime();
		$d->modify('+' . $timeout . ' seconds');

		$update_data = array(
			'updated_at' => $this->getNowForMysql(),
			'timeout_at' => $this->utils->formatDateTimeForMysql($d),
		);

		$this->db->where('player_id', $playerId);
		$this->db->where('token', $token);
//		$this->db->where('timeout_at >=', $this->getNowForMysql());

		$this->db->update($this->tableName, $update_data);

		return $this->db->affected_rows() > 0;
	}

////////////////////////////////////////////////

	public function getAdminUserToken($adminUserId, $db=null) {
		if(empty($adminUserId)){
			return null;
		}
		$field = 'admin_user_id';
		//check available token
		//getAvailableToken($id, $field, &$timeout_datetime=null, $db=null)
		$timeout_datetime=null;
		$token = $this->getAvailableToken($adminUserId, $field, $timeout_datetime, $db);
		if (empty($token)) {
			//createTokenBy($id, $field, $timeout = null, &$timeout_datetime=null, $db=null) {
			$token = $this->createTokenBy($adminUserId, $field, null, $timeout_datetime, $db);
		}
		return $token;
	}

	public function getAffiliateToken($affiliateId) {
		if(empty($affiliateId)){
			return null;
		}
		$field = 'affiliate_id';
		//check available token
		$token = $this->getAvailableToken($affiliateId, $field);
		if (empty($token)) {
			$token = $this->createTokenBy($affiliateId, $field);
		}
		return $token;
	}

	public function getAgencyToken($agentId, &$timeout_datetime=null) {
		if(empty($agentId)){
			return null;
		}
		$field = 'agent_id';
		//check available token
		$token = $this->getAvailableToken($agentId, $field, $timeout_datetime);
		if (empty($token)) {
			$token = $this->createTokenBy($agentId, $field, null , $timeout_datetime);
		}
		return $token;
	}

	public function getPlayerIdByToken($token) {
		return $this->getIdByAvailableToken('player_id', $token);
	}

	/**
	 * get player info by token
	 *
	 * Addition: update timeout_at in certain time condition
	 *
	 * @param string $token
	 * @param string $timeComparison
	 * @param boolean|false $refreshToken
	 * @param string $newTokenValidity
	 * @param boolean|true $checkTokenValidityTimeRange
	 *
	 * @return array player info from table
	 */
	public function getPlayerInfoByToken($token,$refreshToken=false,$checkTokenValidityTimeRange=true,$timeComparison='-10 minutes',$newTokenValidity='+2 hours') {

		$tokenDetails = $this->getMultipleFieldsByAvailableToken('*',$token,$checkTokenValidityTimeRange);
		$timeOutAt = isset($tokenDetails['timeout_at']) ? $tokenDetails['timeout_at'] : null;
		$playerId = isset($tokenDetails['player_id']) ? $tokenDetails['player_id'] : null;

		if(! empty($tokenDetails)){

			# check if need to refresh token, default to false
			if($refreshToken){
				$timeOutAtObject = new DateTime($timeOutAt);
				$now = new DateTime();

				if($timeOutAtObject->modify($timeComparison) < $now){
					# update timeout_at
					$timeOutAtObject->modify($newTokenValidity);
					$formattedNewTimeout = $this->utils->formatDateTimeForMysql($timeOutAtObject);
					$nowForMysql = $this->getNowForMysql();

					$this->db->set('updated_at',$nowForMysql)
						->set('timeout_at',$formattedNewTimeout)
						->where('player_id', $playerId)
						->where('token', $token);

					$updateResult = $this->runAnyUpdateWithResult($this->tableName);

					$this->utils->debug_log(__METHOD__." update token details: updatedRow result: ",$updateResult,'playerId',$playerId,'before timeout_at',$timeOutAt,'after timeout_at',$formattedNewTimeout,'token',$token,'timeComparison',$timeComparison,'newTokenValidity',$newTokenValidity);
				}
			}
		}else{
			$tokenDetails = null;
		}

		return $tokenDetails;
	}

	public function disableToken($token) {
		$this->db->set('status', self::STATUS_DISABLED);
		$this->db->where('token', $token);
		$this->db->update($this->tableName);
		return $this->db->affected_rows() > 0;
	}

	public function getAdminUserIdByToken($token) {
		return $this->getIdByAvailableToken('admin_user_id', $token);
	}

	public function getAffiliateIdByToken($token) {
		return $this->getIdByAvailableToken('affiliate_id', $token);
	}

	public function getMerchantIdByToken($token) {
		return $this->getIdByAvailableToken('merchant_id', $token);
	}

    public function getAgentIdByToken($token) {
        return $this->getIdByAvailableToken('agent_id', $token);
	}

	public function getTimeoutAtByToken($token) {
        return $this->getIdByAvailableToken('timeout_at', $token);
	}

	public function getIdByAvailableToken($field, $token) {
		$this->db->from($this->tableName)->where('token', $token)
			->where('timeout_at >=', $this->getNowForMysql())
			->where('status', self::STATUS_NORMAL);
		$oneRowField = $this->runOneRowOneField($field);

		return $oneRowField;
	}

	/**
	 * Get Multiple Fields by Token
	 *
	 * @param string $fields cc.* = common_tokens and p.* = player table
	 * @param string $token
	 * @param boolean|true $checkTokenValidityTimeRange
	 * @param object|null $db
	 *
	 * @return array
	*/
	public function getMultipleFieldsByAvailableToken($fields="*", $token,$checkTokenValidityTimeRange=true,$db=null) {

		if(empty($db)){
			$db=$this->db;
		}

		$this->db->select($fields)
			->from($this->tableName." cc")
			->join('player p','p.playerId = cc.player_id')
			->where('cc.token', $token)
			->where('cc.status', self::STATUS_NORMAL)
			->order_by('cc.id','desc')
			->limit(1);

		if($checkTokenValidityTimeRange){
			$this->db->where('timeout_at >=', $this->getNowForMysql());
		}

		$rows = $this->runOneRowArray($db);

		return $rows;
	}

	public function getAvailableToken($id, $field, &$timeout_datetime=null, $db=null) {
		if(empty($db)){
			$db=$this->db;
		}
		// at least one minute available
		$dt=new DateTime('-1 minute');

		$db->select('timeout_at, token')->from($this->tableName)->where($field, $id)
			->where('timeout_at >=', $this->utils->formatDateTimeForMysql($dt))
			->where('status', self::STATUS_NORMAL)
			->order_by('id', 'desc')
			->limit(1);

		$row=$this->runOneRowArray($db);

		if(!empty($row)){
			$timeout_datetime=$row['timeout_at'];
			return $row['token'];
		}

		return null;

	}

	public function createTokenBy($id, $field, $timeout = null, &$timeout_datetime=null, $db=null) {
		if(empty($db)){
			$db=$this->db;
		}
		$token = random_string('unique');
		$timeout = $timeout ?: $this->utils->getConfig('token_timeout');
		$d = new DateTime();
		$d->modify('+' . $timeout . ' seconds');

		$timeout_datetime=$this->utils->formatDateTimeForMysql($d);

		$data=[$field => $id,
			"token" => $token,
			"created_at" => $this->getNowForMysql(),
			'updated_at' => $this->getNowForMysql(),
			'timeout_at' => $timeout_datetime,
			'timeout' => $timeout,
			'status' => self::STATUS_NORMAL,];
		$id=$this->runInsertData($this->tableName, $data, $db);

		if(empty($id)){
			$this->utils->error_log('create token failed', $this->tableName, $data);
			return null;
		}else{
			return $token;
		}
	}

	public function getPlayerIdByOldToken($token) {
		return $this->getIdByOldToken('player_id', $token);
	}

	public function getIdByOldToken($field, $token){
		$this->db->from($this->tableName)->where('token', $token)
			->where('status', self::STATUS_NORMAL);
		return $this->runOneRowOneField($field);
	}

	public function getPlayerInfoByOldToken($token){
		$playerInfo=null;
		$playerId=$this->getPlayerIdByOldToken($token);
		if(!empty($playerId)){
			$this->load->model(['player_model']);
			$playerInfo=$this->player_model->getPlayerArrayById($playerId);
		}
		return $playerInfo;
	}

	public function getSignKeyByCode($merchant_code){
		if(empty($merchant_code)){
			return null;
		}

		return $this->getSignKeyFrom($this->getMerchantInfoByCode($merchant_code));
	}

	public function getSignKeyFrom($merchantInfo){
		if(!empty($merchantInfo)){
			if($merchantInfo['live_mode']){
				return $merchantInfo['live_sign_key'];
			}else{
				return $merchantInfo['staging_sign_key'];
			}
		}

		return null;
	}

	public function getSecureKeyFrom($merchantInfo){
		if(!empty($merchantInfo)){
			if($merchantInfo['live_mode']){
				return $merchantInfo['live_secure_key'];
			}else{
				return $merchantInfo['staging_secure_key'];
			}
		}

		return null;
	}

	public function generateSign($fields, $signKey, $except=['sign'], $boolean_to_string_on_sign=false){

		$signString=$this->getSignString($fields, $except, $boolean_to_string_on_sign);
		if(empty($signString)){
			return ['',''];
		}

		$sign=strtolower(sha1($signString.$signKey));

		return [$sign, $signString];
	}

	public function getSignString($fields, $except=['sign'], $boolean_to_string_on_sign=false){
		$params=[];
		foreach ($fields as $key => $value) {
			if( in_array($key, $except) || is_array($value)){
				continue;
			}
			if($boolean_to_string_on_sign && is_bool($value)){
				$value=$value ? 'true' : 'false';
			}
			$params[$key]=$value;
		}

		if(empty($params)){
			return '';
		}

		ksort($params);

		return implode('', array_values($params));
	}

	public function isValidSecureKey($agent, $secure_key){
		return $this->getSecureKeyFrom($agent)==$secure_key;
	}

	public function isValidAuthToken($agent, $auth_token){
		//query token
        $agent_id=$this->getAgentIdByToken($auth_token);
		return $agent_id==$agent['agent_id'];
	}

	public function generateAuthKeyBySecureKey($agent, $secure_key, $always_new=false){
		if($this->isValidSecureKey($agent, $secure_key)){

			$timeout_datetime=null;
			$token = $always_new ? null : $this->getAvailableToken($agent['agent_id'], 'agent_id', $timeout_datetime);
			if (empty($token)) {
				$token = $this->createTokenBy($agent['agent_id'], 'agent_id', null, $timeout_datetime);
			}

			return [$token, $timeout_datetime];

		}

		return [null, null];
	}

	public function deleteTimeoutTokens(\DateTime $timeout_datetime){

		//will delete timeout tokens
		$this->db->where('timeout_at <', $this->utils->formatDateTimeForMysql($timeout_datetime));

		return $this->runRealDelete('common_tokens');

	}

	public function getPlayerIdFromAuthToken($auth_token){
		//query player token
        return $this->getPlayerIdByToken($auth_token);
	}

	public function generateAuthKeyByPlayerUsernamePassword($username, $password,
			$always_new=false, $allow_clear_session=true, $updateOnlineStatus=true, $sendNotification=false){
		$this->load->model(['player_model']);
		list($playerId, $errorCode)=$this->player_model->loginBy($username, $password,
			$allow_clear_session, $updateOnlineStatus, $sendNotification);
		if(!empty($playerId)){
			//clear token
			if($allow_clear_session && $always_new){
				//clear token
				$this->kickoutByPlayerId($playerId);
			}
			$timeout_datetime=null;
			$token=null;
			$signKey=null;
			if(!$always_new){
				list($token, $signKey) = $this->getAvailableTokenWithSignKey($playerId, 'player_id', $timeout_datetime);
			}
			if (empty($token)) {
				list($token, $signKey) = $this->createTokenWithSignKeyBy($playerId, 'player_id', null, $timeout_datetime);
			}
			return [$playerId, $token, $signKey, $timeout_datetime, $errorCode];
		}
		return [null, null, null, null, $errorCode];
	}

	public function kickoutByPlayerId($playerId){
		$this->db->where('player_id', $playerId);
		return $this->runRealDelete('common_tokens');
	}

	public function getPlayerIdAndSignKeyByPlayerAuthToken($auth_token){
		//query token
		$this->db->select('player_id, sign_key')->from($this->tableName)->where('token', $auth_token)
			->where('timeout_at >=', $this->getNowForMysql())
			->where('status', self::STATUS_NORMAL);

		$row=$this->runOneRowArray();
		$playerId=null;
		$signKey=null;
		if(!empty($row)){
			$playerId=$row['player_id'];
			$signKey=$row['sign_key'];
		}

		return [$playerId, $signKey];
	}

	public function getAvailableTokenWithSignKey($id, $field, &$timeout_datetime=null, $db=null) {
		if(empty($db)){
			$db=$this->db;
		}
		// at least one minute available
		$dt=new DateTime('-1 minute');

		$db->select('timeout_at, token, sign_key')->from($this->tableName)->where($field, $id)
			->where('timeout_at >=', $this->utils->formatDateTimeForMysql($dt))
			->where('status', self::STATUS_NORMAL)
			->limit(1);

		$row=$this->runOneRowArray($db);

		if(!empty($row)){
			$timeout_datetime=$row['timeout_at'];
			return [$row['token'], $row['sign_key']];
		}

		return [null, null];

	}

	/**
	 * Get Token with sign key using game username
	*/
	public function getAvailableTokenWithSignKeyUsingGameUsername($gameUsername,&$playerId, &$timeout_datetime=null, $db=null) {
		if(empty($db)){
			$db=$this->db;
		}
		// at least one minute available
		$dt=new DateTime('-1 minute');

		$db->select('ct.timeout_at, ct.token, ct.sign_key,ct.player_id')
			->from($this->tableName.' ct')
			->join('game_provider_auth gpa','gpa.player_id = ct.player_id','left')
			->where('ct.timeout_at >=', $this->utils->formatDateTimeForMysql($dt))
			->where('ct.status', self::STATUS_NORMAL)
			->where('gpa.login_name',$gameUsername)
			->limit(1);

		$row=$this->runOneRowArray($db);

		if(!empty($row)){
			$timeout_datetime=$row['timeout_at'];
			$playerId = $row['player_id'];
			return [$row['token'], $row['sign_key']];
		}

		return [null, null];

	}

	public function createTokenWithSignKeyBy($id, $field, $timeout = null, &$timeout_datetime=null, $db=null) {
		if(empty($db)){
			$db=$this->db;
		}
		$token = random_string('unique');
		$signKey= random_string('unique');
		$timeout = $timeout ?: $this->utils->getConfig('token_timeout');
		$d = new DateTime();
		$d->modify('+' . $timeout . ' seconds');

		$timeout_datetime=$this->utils->formatDateTimeForMysql($d);

		$data=[$field => $id,
			'token' => $token,
			'sign_key' => $signKey,
			"created_at" => $this->getNowForMysql(),
			'updated_at' => $this->getNowForMysql(),
			'timeout_at' => $timeout_datetime,
			'timeout' => $timeout,
			'status' => self::STATUS_NORMAL,];
		$id=$this->runInsertData($this->tableName, $data, $db);

		if(empty($id)){
			$this->utils->error_log('create token failed', $this->tableName, $data);
			return [null, null];
		}else{
			return [$token, $signKey];
		}
	}

	public function cancelToken($token, $id, $field) {
		$this->db->set('status', self::STATUS_DISABLED);
		$this->db->where('token', $token)->where($field, $id);
		$this->utils->debug_log('cancel token', $token, $id, $field);
		return $this->runAnyUpdate('common_tokens');
	}

	public function cancelPlayerToken($token, $id) {
		return $this->cancelToken($token, $id, 'player_id');
	}

	public function cancelAdminUserToken($token, $id) {
		return $this->cancelToken($token, $id, 'admin_user_id');
	}

	public function cancelAffiliateToken($token, $id) {
		return $this->cancelToken($token, $id, 'affiliate_id');
	}

	public function cancelAgencyToken($token, $id) {
		return $this->cancelToken($token, $id, 'agent_id');
	}

    public function cancelAllPlayerToken($id) {
    	if (!is_numeric($id) || !preg_match("/^[0-9]+$/", $id)) {
            return false;
        }
        $this->db->set('status', self::STATUS_DISABLED);
        $this->db->where('player_id', $id);
        $this->utils->debug_log('cancel all player token', $id);
        return $this->runAnyUpdate('common_tokens');
    }

	/**
	 * delete token
	 * @param  string $token
	 * @return boolean
	 */
	public function deleteToken($token) {
		$this->db->where('token', $token);
		return $this->runRealDelete($this->tableName);
	}

	public function generateBackendAuthKeyByUserId($adminUserId, $always_new=false){
		// if($backend_key_info['secure_key']!=$secure_key){
		// 	return [null, null];
		// }

		$timeout_datetime=null;
		$token = $always_new ? null : $this->getAvailableToken($adminUserId, 'admin_user_id', $timeout_datetime);
		if (empty($token)) {
			$token = $this->createTokenBy($adminUserId, 'admin_user_id', null, $timeout_datetime);
		}

		return [$token, $timeout_datetime];
	}

	public function isValidBackendAuthToken($auth_token){
		$success=false;
		if(empty($auth_token)){
			return $success;
		}
		//get id from auth token
		$arr=explode('-', $auth_token);
		if(empty($arr) || count($arr)!=2){
			return $success;
		}
		$adminUserId=$arr[0];
		$token=$arr[1];
		if(!empty($adminUserId) && !empty($token)){
			// $this->load->model(['users']);
			// $keyInfo=$this->users->getKeysByUserId($adminUserId);

			$userIdInDB=$this->getAdminUserIdByToken($token);
			$this->utils->debug_log('compare user id by token', $userIdInDB, $adminUserId, $token, $auth_token);
			if(!empty($userIdInDB) && $userIdInDB==$adminUserId){
				$success=true;
			}
		}

		return $success;
	}

	/**
	 * delete token for admin user
	 * @param  string $auth_token
	 * @return boolean
	 */
	public function deleteTokenForAdminUser($auth_token) {
		$success=false;
		if(empty($auth_token)){
			return $success;
		}
		//get id from auth token
		$arr=explode('-', $auth_token);
		if(empty($arr) || count($arr)!=2){
			return $success;
		}
		$adminUserId=$arr[0];
		$token=$arr[1];
		if(!empty($adminUserId) && !empty($token)){
			$this->db->where('token', $token)->where('admin_user_id', $adminUserId);
			return $this->runRealDelete($this->tableName);
		}

		return $success;
	}

	public function getPlayerCompleteDetailsByToken($token, $gamePlatformId, $refreshTimout = true, $minSpanAllowed=10, $minutesToAdd=120) {

		if($refreshTimout){
			$params=[$token, Common_token::STATUS_NORMAL, $gamePlatformId];
			$where = 'ct.token = ? AND ct.status = ? AND gpa.game_provider_id = ?';
		}else{
			$params=[$token, Common_token::STATUS_NORMAL, $gamePlatformId, $this->CI->utils->getNowForMysql()];
			$where = 'ct.token = ? AND ct.status = ? AND gpa.game_provider_id = ? AND ct.timeout_at >= ?';
		}


        $sql = <<<EOD
SELECT

ct.token,
ct.timeout_at,
p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.external_category,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag,
gpa.external_account_id

FROM common_tokens AS ct
JOIN game_provider_auth AS gpa ON gpa.player_id = ct.player_id
JOIN player AS p ON p.playerId = gpa.player_id


WHERE $where;

EOD;

        $qry = $this->db->query($sql, $params);
		$result = $this->getOneRow($qry);

		if(!empty($result) && $refreshTimout){
			$timeOutAt = new DateTime($result->timeout_at);
			$now = new DateTime();
			if($timeOutAt->modify('-'.$minSpanAllowed.' minutes') < $now){
				$timeOutAt->modify('+'.$minutesToAdd.' minutes');
				$formattedNewTimeout = $this->utils->formatDateTimeForMysql($timeOutAt);
				$nowForMysql = $this->getNowForMysql();
				$this->db->set('updated_at',$nowForMysql)
					->set('timeout_at',$formattedNewTimeout)
					->where('player_id', $result->player_id)
					->where('token', $token);

				$updateResult = $this->runAnyUpdateWithResult($this->tableName);

				$this->utils->debug_log(__METHOD__." update token details: updatedRow result: ",$updateResult,'playerId',$result->player_id,'before timeout_at',$result->timeout_at,'after timeout_at',$formattedNewTimeout,'token',$token,'timeComparison',$minSpanAllowed,'newTokenValidity',$minutesToAdd);

			}
		}

        return  $result;
	}

	public function getPlayerCompleteDetailsByUsername($username, $gamePlatformId) {
        $sql = <<<EOD
SELECT
ct.token,
p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag,
gpa.external_account_id

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
LEFT JOIN common_tokens AS ct ON p.playerId = ct.player_id AND ct.status = ?
WHERE p.username = ? AND gpa.game_provider_id = ?;

EOD;

        $params=[Common_token::STATUS_NORMAL, (string)$username, $gamePlatformId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}

	public function getPlayerCompleteDetailsByGameUsername($gameUsername, $gamePlatformId) {
        $sql = <<<EOD
SELECT
ct.token,
p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag,
gpa.external_account_id

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
LEFT JOIN common_tokens AS ct ON p.playerId = ct.player_id AND ct.status = ?
WHERE gpa.login_name = ? AND gpa.game_provider_id = ?;

EOD;

        $params=[Common_token::STATUS_NORMAL, (string)$gameUsername, $gamePlatformId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}

	public function getPlayerCompleteDetailsByPlayerId($playerId, $gamePlatformId) {
        $sql = <<<EOD
SELECT
ct.token,
p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
LEFT JOIN common_tokens AS ct ON p.playerId = ct.player_id AND ct.status = ?
WHERE gpa.player_id = ? AND gpa.game_provider_id = ?;

EOD;


        $params=[Common_token::STATUS_NORMAL, (string)$playerId, $gamePlatformId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}

	public function getPlayerCompleteDetailsByExternalAccountId($gameUsername, $gamePlatformId) {
        $sql = <<<EOD
SELECT

p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
WHERE gpa.external_account_id = ? AND gpa.game_provider_id = ?;

EOD;


        $params=[(string)$gameUsername, $gamePlatformId];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}

	public function getPlayerCompleteDetailsByExternalAccountIdAndLoginName($gameUsername, $gamePlatformId, $loginName) {
		$this->CI->utils->debug_log("getPlayerCompleteDetailsByExternalAccountIdAndLoginName parameters", [$gameUsername, $gamePlatformId, $loginName]);
        $sql = <<<EOD
SELECT

p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.status game_status

FROM player as p
JOIN game_provider_auth as gpa ON p.playerId = gpa.player_id
WHERE gpa.external_account_id = ? AND gpa.game_provider_id = ? and gpa.login_name=?;

EOD;


        $params=[(string)$gameUsername, $gamePlatformId, $loginName];
        $qry = $this->db->query($sql, $params);
        $result = $this->getOneRow($qry);
        return  $result;
	}
	/**
	 * Find player's available token and disable it, ported from xcyl, OGP-23302
	 * Usage: when player (1) time out or self excl request being approved
	 * @param	int		$player_id	==player.playerId
	 * @return	bool
	 */
	public function disablePlayerAvailableToken($player_id) {
		$token = $this->getAvailableToken($player_id, 'player_id');

		if (!$token) {
			return true;
		}

		$res = $this->disableToken($token);

		return $res;
	}

	public function refreshToken($token, $playerId, $minutesToAdd=30) {

		$timeOutAt = new DateTime();
				$timeOutAt->modify('+'.$minutesToAdd.' minutes');
				$formattedNewTimeout = $this->utils->formatDateTimeForMysql($timeOutAt);
				$nowForMysql = $this->getNowForMysql();
				$this->db->set('updated_at',$nowForMysql)
					->set('timeout_at',$formattedNewTimeout)
					->where('player_id', $playerId)
					->where('token', $token);

        return  $token;
	}

	public function getTokenTimeout($token){
		$query = $this->db->select('timeout_at')
                    ->from($this->tableName)
                    ->where("token",$token)
                    ->get();

        return $this->getOneRowOneField($query, 'timeout_at');
	}

	public function getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $gamePlatformId, $refreshTimout = true, $minSpanAllowed=10, $minutesToAdd=120) {

		$params=[$token, $gameUsername, $gamePlatformId, Common_token::STATUS_NORMAL];
		$where = 'ct.token = ? AND gpa.login_name = ? AND gpa.game_provider_id = ? AND ct.status = ?';

        $sql = <<<EOD
SELECT

ct.token,
ct.timeout_at,
p.playerId as player_id,
p.username,
p.password,
p.active,
p.blocked,
p.frozen,
p.createdOn as created_at,
gpa.game_provider_id,
gpa.login_name game_username,
gpa.password game_password,
gpa.register game_isregister,
gpa.external_category,
gpa.status game_status,
gpa.is_blocked as game_blocked,
gpa.is_demo_flag,
gpa.external_account_id

FROM common_tokens AS ct
JOIN game_provider_auth AS gpa ON gpa.player_id = ct.player_id
JOIN player AS p ON p.playerId = gpa.player_id


WHERE $where;

EOD;

        $qry = $this->db->query($sql, $params);
		$result = $this->getOneRow($qry);

		if(!empty($result) && $refreshTimout){
			$timeOutAt = new DateTime($result->timeout_at);
			$now = new DateTime();
			if($timeOutAt->modify('-'.$minSpanAllowed.' minutes') < $now){
				$timeOutAt->modify('+'.$minutesToAdd.' minutes');
				$formattedNewTimeout = $this->utils->formatDateTimeForMysql($timeOutAt);
				$nowForMysql = $this->getNowForMysql();
				$this->db->set('updated_at',$nowForMysql)
					->set('timeout_at',$formattedNewTimeout)
					->set('status',Common_token::STATUS_NORMAL)
					->where('player_id', $result->player_id)
					->where('token', $token);

				$updateResult = $this->runAnyUpdateWithResult($this->tableName);

				$this->utils->debug_log(__METHOD__." update token details: updatedRow result: ",$updateResult,'playerId',$result->player_id,'before timeout_at',$result->timeout_at,'after timeout_at',$formattedNewTimeout,'token',$token,'timeComparison',$minSpanAllowed,'newTokenValidity',$minutesToAdd);

			}
		}

        return  $result;
	}

} // End class Common_token

///END OF FILE///////