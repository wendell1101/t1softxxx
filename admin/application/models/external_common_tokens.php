<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class External_common_tokens extends BaseModel {

	const T_ACTIVE = "ACTIVE";
	const T_INACTIVE = "INACTIVE";
	const T_ERROR = "ERROR";

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "external_common_tokens";

	public function getExternalToken($player_id, $game_platform_id) {
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('game_platform_id', $game_platform_id);
		return $this->runOneRowOneField('token');
	}

	public function addPlayerToken($player_id, $token, $gamePlatformId, $currency=null, $by_token_only = false) {
		$this->db->select('id')->from($this->tableName);

        if ($by_token_only) {
            $this->db->where('token', $token);
        } else {
            $this->db->where('player_id', $player_id);
            $this->db->where('game_platform_id', $gamePlatformId);
        }

		$id = $this->runOneRowOneField('id');

		$data = array(
			"player_id" => $player_id,
			"token" => $token,
			"game_platform_id" => $gamePlatformId,
			"created_at" => $this->getNowForMysql(),
			"status" => self::T_ACTIVE,
			"currency" => $currency,
			"updated_at" => $this->getNowForMysql()
		);
		if (empty($id)) {
			$this->db->insert($this->tableName, $data);
		} else {
			unset($data['created_at']);
			$this->db->where('id', $id);
			$this->db->update($this->tableName, $data);
		}
	}

	public function getPlayerIdByExternalToken($token, $game_platform_id) {
		$this->db->from($this->tableName);
		$this->db->where('token', $token);
		$this->db->where('game_platform_id', $game_platform_id);
		return $this->runOneRowOneField('player_id');
	}

	public function setPlayerToken($player_id, $token, $gamePlatformId) {

		$this->db->from($this->tableName)->where('player_id', $player_id)->where('game_platform_id', $gamePlatformId);
		$exists = $this->runExistsResult();

		if (!$exists) {
			$data = array(
				"player_id" => $player_id,
				"token" => $token,
				"game_platform_id" => $gamePlatformId,
				"created_at" => $this->getNowForMysql()
			);
			$this->db->insert($this->tableName, $data);
		} else {
			$this->db->set('token', $token);
			$this->db->where('player_id', $player_id);
			$this->db->where('game_platform_id', $gamePlatformId);
			$this->db->update($this->tableName);
		}
	}

	public function updatePlayerExternalTokenStatus($playerId, $token, $status){

		$update_data = array(
			'status' => $status
		);

		$this->db->where('player_id', $playerId);
		$this->db->where('token', $token);

		$this->db->update($this->tableName, $update_data);

		return $this->db->affected_rows() > 0;
	}

	public function checkExpiredExternalToken($player_id, $game_platform_id, $session_id) {
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('game_platform_id', $game_platform_id);
		$this->db->where('token', $session_id);
		$this->db->where('status', self::T_INACTIVE);
		return $this->runOneRowOneField('token');
	}

	public function checkActiveExternalToken($player_id, $game_platform_id, $session_id) {
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('game_platform_id', $game_platform_id);
		$this->db->where('token', $session_id);
		$this->db->where('status', self::T_ACTIVE);
		return $this->runOneRowOneField('token');
	}

	public function getCurrencyByExternalToken($player_id, $game_platform_id, $session_id) {
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('game_platform_id', $game_platform_id);
		$this->db->where('token', $session_id);
		$this->db->where('status', self::T_ACTIVE);
		return $this->runOneRowOneField('currency');
	}

	public function getExternalTokenInfo($player_id, $session_id) {
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('token', $session_id);
		return $this->runOneRowArray();
	}
	// This is for the deletion of data on external_common_token
	public function batchDeleteOfExternalCommonToken($dry_run,$limit,$date_field) {
		if(!empty($limit)){

            $affected_rows = 1;//trigger while loop
            $deletedNumber = 0;

            while($affected_rows > 0){

    $sql = <<<EOD
DELETE FROM {$this->tableName} where
updated_at <= '{$date_field}' LIMIT {$limit}
EOD;

                if($dry_run){
                //ignore
                }else{
                    $q=$this->db->query($sql);
                    $affected_rows = $this->db->affected_rows();
                }
                $deletedNumber = $deletedNumber+$affected_rows;
                $this->utils->debug_log('deleted count: '. $affected_rows. ' at '. $date_field. ' sql '.$sql);

                if($affected_rows < $limit){ //prevent another offset or last page
                    $affected_rows=0;//stop while loop
                }
            }

        }else{

        $sql = <<<EOD
DELETE FROM {$this->tableName} where
updated_at <= '{$date_field}'
EOD;
          if($dry_run){
            //ignore
          }else{
            $q=$this->db->query($sql);
            $deletedNumber = $affected_rows = $this->db->affected_rows();
            $this->utils->debug_log('deleted count: '. $affected_rows. ' at '. $date_field. ' sql '.$sql);
        }

    }

	    $this->utils->debug_log('after exec sql, affected_rows', $deletedNumber);
	    return $deletedNumber;
    }

    public function addPlayerTokenWithExtraInfo($player_id, $token, $extra, $gamePlatformId, $currency=null) {
		$this->db->select('id')
			->from($this->tableName)
				->where('player_id', $player_id)
						->where('game_platform_id', $gamePlatformId);
							if($gamePlatformId == PARIPLAY_SEAMLESS_API){
								$this->db->where('token', $token);
							}
		$id = $this->runOneRowOneField('id');

		$data = array(
			"player_id" => $player_id,
			"token" => $token,
			"game_platform_id" => $gamePlatformId,
			"created_at" => $this->getNowForMysql(),
			"status" => self::T_ACTIVE,
			"currency" => $currency,
			"updated_at" => $this->getNowForMysql(),
			"extra_info" => $extra
		);
		if($gamePlatformId == PARIPLAY_SEAMLESS_API){ #need to insert multiple token, and will be update during round ended
			if (empty($id)){
				return $this->db->insert($this->tableName, $data);
			}
			return $id;
		}
		if (empty($id)) {
			$this->db->insert($this->tableName, $data);
		} else {
			unset($data['created_at']);
			$this->db->where('id', $id);
			$this->db->update($this->tableName, $data);
		}
	}

	public function getTokenInfoByExtras($extra, $game_platform_id) {
		$this->db->from($this->tableName);
		$this->db->where('extra_info', $extra);
		$this->db->where('game_platform_id', $game_platform_id);
		return $this->runOneRowOneField('player_id');
	}

	public function getPlayerActiveExternalTokens($player_id, $game_platform_id) {
		$this->db->select('token');
		$this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$this->db->where('game_platform_id', $game_platform_id);
		$this->db->where('status', self::T_ACTIVE);
		$tokens = $this->runMultipleRowArray();
		return array_column($tokens, 'token');
	}

	public function cancelAllPlayerToken($id) {
			if (!is_numeric($id) || !preg_match("/^[0-9]+$/", $id)) {
        return false;
      }
      $this->db->set('status', self::T_INACTIVE);
      $this->db->where('player_id', $id);
      $this->utils->debug_log('cancel all player token', $id);
      return $this->runAnyUpdate($this->tableName);
    }

    public function getExternalCommonTokenInfoByToken($token) {
        $this->db->from($this->tableName)->where('token', $token);
        return $this->runOneRowArray();
    }

	public function getPlayerCompleteDetailsByToken($token, $gamePlatformId) {
		$where = 'ect.token=? and game_platform_id=?';
		$params=[$gamePlatformId,(string)$token, $gamePlatformId];

        $sql = <<<EOD
SELECT

ect.token,
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

FROM external_common_tokens AS ect
JOIN game_provider_auth AS gpa ON gpa.player_id = ect.player_id AND game_provider_id=?
JOIN player AS p ON p.playerId = gpa.player_id


WHERE $where;

EOD;
			;
			$qry = $this->db->query($sql, $params);
			$result = $this->getOneRow($qry);
			return  $result;
	}


	public function getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $gamePlatformId) {
		$params=[$gamePlatformId,(string)$token, $gameUsername, $gamePlatformId, self::T_ACTIVE];

		$where = 'ect.token = ? AND gpa.login_name = ? AND gpa.game_provider_id = ? AND ect.status = ?';

        $sql = <<<EOD
SELECT

ect.token,
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
gpa.is_demo_flag

FROM external_common_tokens AS ect
JOIN game_provider_auth AS gpa ON gpa.player_id = ect.player_id AND game_provider_id=?
JOIN player AS p ON p.playerId = gpa.player_id


WHERE $where;

EOD;

			$qry = $this->db->query($sql, $params);
			$result = $this->getOneRow($qry);
			return  $result;
	}

	public function getPlayerCompleteDetailsByGameUsername($gameUsername, $gamePlatformId) {
		$params=[$gamePlatformId,(string)$gameUsername, $gamePlatformId];

		$where = 'gpa.login_name = ? AND gpa.game_provider_id = ?;';

        $sql = <<<EOD
SELECT

ect.token,
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
gpa.is_demo_flag

FROM external_common_tokens AS ect
JOIN game_provider_auth AS gpa ON gpa.player_id = ect.player_id AND game_provider_id=?
JOIN player AS p ON p.playerId = gpa.player_id


WHERE $where;

EOD;

			$qry = $this->db->query($sql, $params);
			$result = $this->getOneRow($qry);
			return  $result;
	}

	public function getPlayerToken($playerId){
		$this->db->from($this->tableName)->where('player_id', $playerId);
        $result = $this->runOneRowArray();
		return $result['token'];
	}


	public function updatePlayerExternalExtraInfo($playerId, $token, $game_platform_id, $extra_info){

		$update_data = array(
			'extra_info' => $extra_info
		);

		$this->db->where('player_id', $playerId);
		$this->db->where('token', $token);
		$this->db->where('game_platform_id', $game_platform_id);

		$this->db->update($this->tableName, $update_data);

		return $this->db->affected_rows() > 0;
	}
	
}




///END OF FILE///////
